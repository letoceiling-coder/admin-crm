<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DeployController extends Controller
{
    protected $phpPath;
    protected $phpVersion;
    protected $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    /**
     * Выполнить деплой на сервере
     */
    public function deploy(Request $request)
    {
        $startTime = microtime(true);
        Log::info('🚀 Начало деплоя', [
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
        ];

        try {
            // Определяем PHP путь
            $this->phpPath = $this->getPhpPath();
            $this->phpVersion = $this->getPhpVersion();

            Log::info("Используется PHP: {$this->phpPath} (версия: {$this->phpVersion})");

            // 0. Очистка файлов разработки в начале
            $this->cleanDevelopmentFiles();

            // Получаем ветку из запроса или используем текущую ветку сервера
            $requestedBranch = $request->input('branch');
            if (!$requestedBranch) {
                // Пытаемся определить текущую ветку на сервере
                $currentBranchProcess = Process::path($this->basePath)
                    ->run('git rev-parse --abbrev-ref HEAD 2>&1');
                $requestedBranch = trim($currentBranchProcess->output()) ?: 'main';
            }
            
            Log::info("🌿 Используется ветка для деплоя: {$requestedBranch}");

            // Получаем текущий commit hash ДО git pull
            $oldCommitHash = $this->getCurrentCommitHash();
            Log::info("📦 Commit до обновления: " . ($oldCommitHash ?: 'не определен'));

            // 1. Git pull
            $gitPullResult = $this->handleGitPull($requestedBranch);
            $result['data']['git_pull'] = $gitPullResult['status'];
            $result['data']['branch'] = $gitPullResult['branch'] ?? 'unknown';
            if (!$gitPullResult['success']) {
                throw new \Exception("Ошибка git pull: {$gitPullResult['error']}");
            }
            
            // Получаем commit hash после git pull (если он был возвращен)
            $commitAfterPull = $gitPullResult['after_commit_hash'] ?? null;

            // 1.5. Проверка наличия собранных файлов фронтенда
            $frontendCheck = $this->checkFrontendFiles();
            $result['data']['frontend_files'] = $frontendCheck;
            if (!$frontendCheck['manifest_exists']) {
                Log::warning('⚠️ Manifest.json не найден после git pull. Убедитесь, что файлы собраны локально и закоммичены в git.');
            }

            // 1.6. Установка npm и сборка админки (если npm доступен и не пропущено)
            $skipNpm = $request->input('skip_npm', false);
            if (!$skipNpm) {
                $npmResult = $this->handleNpmInstall();
                $result['data']['npm_install'] = $npmResult;
                if (!$npmResult['success'] && $npmResult['status'] !== 'skipped') {
                    Log::warning('⚠️ npm install не выполнен: ' . ($npmResult['error'] ?? 'неизвестная ошибка'));
                }
                // Если npm install успешен — собираем админку (Vite)
                if ($npmResult['success'] && ($npmResult['status'] ?? '') === 'success') {
                    $buildResult = $this->handleNpmBuild();
                    $result['data']['npm_build'] = $buildResult;
                    if (!$buildResult['success']) {
                        Log::warning('⚠️ npm run build не выполнен: ' . ($buildResult['error'] ?? 'неизвестная ошибка'));
                    }
                } else {
                    $result['data']['npm_build'] = [
                        'status' => 'skipped',
                        'message' => 'npm run build пропущен (npm install не выполнен)',
                    ];
                }
            } else {
                $result['data']['npm_install'] = [
                    'status' => 'skipped',
                    'message' => 'npm install пропущен (skip_npm=true)',
                ];
                $result['data']['npm_build'] = [
                    'status' => 'skipped',
                    'message' => 'Соберите админку локально: npm run build, затем закоммитьте public/build и сделайте деплой.',
                ];
                Log::info('npm пропущен по запросу');
            }

            // 2. Composer install
            $composerResult = $this->handleComposerInstall();
            $result['data']['composer_install'] = $composerResult['status'];
            if (!$composerResult['success']) {
                throw new \Exception("Ошибка composer install: {$composerResult['error']}");
            }

            // 2.5. Очистка кешей после composer install
            $this->clearPackageDiscoveryCache();

            // 3. Миграции
            $migrationsResult = $this->runMigrations();
            $result['data']['migrations'] = $migrationsResult;
            if ($migrationsResult['status'] !== 'success') {
                throw new \Exception("Ошибка миграций: {$migrationsResult['error']}");
            }

            // 3.5. Выполнение seeders (только если явно запрошено)
            $runSeeders = $request->input('run_seeders', false);
            if ($runSeeders) {
                $seedersResult = $this->runSeeders();
                $result['data']['seeders'] = $seedersResult;
                Log::info('Seeders выполнены по запросу');
            } else {
                $result['data']['seeders'] = [
                    'status' => 'skipped',
                    'message' => 'Seeders пропущены (используйте --with-seed для выполнения)',
                ];
                Log::info('Seeders пропущены (не указан флаг run_seeders)');
            }

            // 4. Очистка временных файлов разработки
            $this->cleanDevelopmentFiles();

            // 5. Очистка кешей
            $cacheResult = $this->clearAllCaches();
            $result['data']['cache_cleared'] = $cacheResult['success'];

            // 6. Оптимизация
            $optimizeResult = $this->optimizeApplication();
            $result['data']['optimized'] = $optimizeResult['success'];

            // 7. Финальная очистка файлов разработки
            $this->cleanDevelopmentFiles();

            // Получаем новый commit hash (используем из git pull или получаем заново)
            $newCommitHash = $commitAfterPull ?? $this->getCurrentCommitHash();

            // Формируем успешный ответ
            $result['success'] = true;
            $result['message'] = 'Деплой успешно завершен';
            $result['data'] = array_merge($result['data'], [
                'php_version' => $this->phpVersion,
                'php_path' => $this->phpPath,
                'branch' => $requestedBranch,
                'old_commit_hash' => $oldCommitHash,
                'new_commit_hash' => $newCommitHash,
                'commit_changed' => $oldCommitHash && $newCommitHash && $oldCommitHash !== $newCommitHash,
                'deployed_at' => now()->toDateTimeString(),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
            ]);

            Log::info('✅ Деплой успешно завершен', $result['data']);

        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['data']['error'] = $e->getMessage();
            $result['data']['trace'] = config('app.debug') ? $e->getTraceAsString() : null;
            $result['data']['deployed_at'] = now()->toDateTimeString();
            $result['data']['duration_seconds'] = round(microtime(true) - $startTime, 2);
            
            // Добавляем информацию о ветке даже при ошибке
            if (isset($requestedBranch)) {
                $result['data']['branch'] = $requestedBranch;
            }

            Log::error('❌ Ошибка деплоя', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'branch' => $requestedBranch ?? 'unknown',
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Определить путь к PHP
     */
    protected function getPhpPath(): string
    {
        // 1. Проверить явно указанный путь в .env
        $phpPath = env('PHP_PATH');
        if ($phpPath && $this->isPhpExecutable($phpPath)) {
            return $phpPath;
        }

        // 2. Попробовать автоматически найти PHP
        $possiblePaths = ['php8.2', 'php8.3', 'php8.1', 'php'];
        foreach ($possiblePaths as $path) {
            if ($this->isPhpExecutable($path)) {
                return $path;
            }
        }

        // 3. Fallback на 'php'
        return 'php';
    }

    /**
     * Проверить доступность PHP
     */
    protected function isPhpExecutable(string $path): bool
    {
        try {
            // Проверка через which (Unix-like)
            $result = shell_exec("which {$path} 2>/dev/null");
            if ($result && trim($result)) {
                return true;
            }

            // Проверка через exec (версия PHP)
            exec("{$path} --version 2>&1", $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить версию PHP
     */
    protected function getPhpVersion(): string
    {
        try {
            exec("{$this->phpPath} --version 2>&1", $output, $returnCode);
            if ($returnCode === 0 && isset($output[0])) {
                preg_match('/PHP\s+(\d+\.\d+\.\d+)/', $output[0], $matches);
                return $matches[1] ?? 'unknown';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return 'unknown';
    }

    /**
     * Выполнить git pull
     */
    protected function handleGitPull(string $branch = 'main'): array
    {
        try {
            Log::info("🔍 Базовая директория проекта: {$this->basePath}");
            Log::info("🔍 Проверка существования .git: " . (is_dir($this->basePath . '/.git') ? 'ДА' : 'НЕТ'));
            
            // Проверяем, что это git репозиторий
            $gitDir = $this->basePath . '/.git';
            if (!is_dir($gitDir)) {
                $error = "Директория не является git репозиторием. Путь: {$this->basePath}";
                Log::error($error);
                return [
                    'success' => false,
                    'status' => 'error',
                    'error' => $error,
                ];
            }

            // Настройка безопасной директории для git
            $this->ensureGitSafeDirectory();
            
            $safeDirectoryPath = escapeshellarg($this->basePath);
            $gitEnv = [
                'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
            ];
            $gitBaseCmd = 'git -c safe.directory=' . $safeDirectoryPath;

            // Проверяем статус git перед pull
            $statusProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' status --porcelain 2>&1');

            $hasChanges = !empty(trim($statusProcess->output()));

            // Если есть локальные изменения, сохраняем их в stash
            if ($hasChanges) {
                Log::info('Обнаружены локальные изменения, сохраняем в stash...');
                $stashMessage = 'Auto-stash before deploy ' . now()->toDateTimeString();
                $stashProcess = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' stash push -m ' . escapeshellarg($stashMessage) . ' 2>&1');

                if (!$stashProcess->successful()) {
                    Log::warning('Не удалось сохранить изменения в stash', [
                        'error' => $stashProcess->errorOutput(),
                    ]);
                }
            }

            // Получаем текущий commit перед обновлением
            $beforeCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit до обновления: " . ($beforeCommit ?: 'не определен'));
            Log::info("🌿 Обновляем ветку: {$branch}");

            // 1. Получаем последние изменения из репозитория
            Log::info("📥 Выполняем git fetch origin {$branch}...");
            $fetchProcess = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' fetch origin ' . escapeshellarg($branch) . ' 2>&1');

            if (!$fetchProcess->successful()) {
                Log::warning('⚠️ Не удалось выполнить git fetch', [
                    'output' => $fetchProcess->output(),
                    'error' => $fetchProcess->errorOutput(),
                ]);
            } else {
                Log::info('✅ Git fetch выполнен успешно');
            }

            // 2. Сбрасываем локальную ветку на origin/main
            Log::info("🔄 Выполняем git reset --hard origin/{$branch}...");
            $process = Process::path($this->basePath)
                ->env($gitEnv)
                ->run($gitBaseCmd . ' reset --hard origin/' . escapeshellarg($branch) . ' 2>&1');

            if (!$process->successful()) {
                Log::warning('Git reset --hard не удался, пробуем git pull', [
                    'error' => $process->errorOutput(),
                ]);

                // Если reset не удался, пробуем обычный pull
                $process = Process::path($this->basePath)
                    ->env($gitEnv)
                    ->run($gitBaseCmd . ' pull origin ' . escapeshellarg($branch) . ' --no-rebase --force 2>&1');
            }

            // 3. Получаем новый commit после обновления
            $afterCommit = $this->getCurrentCommitHash();
            Log::info("📦 Commit после обновления: " . ($afterCommit ?: 'не определен'));

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                    'had_local_changes' => $hasChanges,
                    'branch' => $branch,
                    'after_commit_hash' => $afterCommit, // Возвращаем хеш после обновления
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
                'branch' => $branch,
            ];
        } catch (\Exception $e) {
            Log::error('Исключение в handleGitPull', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Настроить безопасную директорию для git
     */
    protected function ensureGitSafeDirectory(): void
    {
        try {
            $escapedPath = escapeshellarg($this->basePath);
            $process = Process::path($this->basePath)
                ->run("git config --global --add safe.directory {$escapedPath} 2>&1");

            if (!$process->successful()) {
                $processLocal = Process::path($this->basePath)
                    ->run("git config --local --add safe.directory {$escapedPath} 2>&1");

                if (!$processLocal->successful()) {
                    putenv("GIT_CEILING_DIRECTORIES=" . dirname($this->basePath));
                }
            }
        } catch (\Exception $e) {
            Log::warning('Не удалось настроить safe.directory для git', [
                'path' => $this->basePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выполнить composer install
     */
    protected function handleComposerInstall(): array
    {
        try {
            $composerPath = $this->getComposerPath();
            Log::info("🔍 Путь к composer: {$composerPath}");

            $homeDir = dirname(dirname($this->basePath));
            Log::info("🔍 HOME директория: {$homeDir}");

            if (!empty($composerPath) && $composerPath !== 'composer' && strpos($composerPath, '/') !== false) {
                $escapedPath = escapeshellarg($composerPath);
                
                if (strpos($composerPath, 'composer.phar') !== false) {
                    $command = "{$this->phpPath} {$escapedPath} install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
                } else {
                    $command = "{$this->phpPath} {$escapedPath} install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
                }
            } else {
                $command = "composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>&1";
            }
            Log::info("🔍 Команда composer: {$command}");

            $env = [
                'HOME' => $homeDir,
                'COMPOSER_HOME' => $homeDir . '/.composer',
                'COMPOSER_DISABLE_XDEBUG_WARN' => '1',
            ];
            
            $process = Process::path($this->basePath)
                ->timeout(600) // 10 минут
                ->env($env)
                ->run($command);

            if ($process->successful()) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'output' => $process->output(),
                ];
            }

            return [
                'success' => false,
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить путь к composer
     */
    protected function getComposerPath(): string
    {
        // 1. Проверить локальный composer
        $localComposer = $this->basePath . '/bin/composer';
        try {
            $testProcess = Process::run("test -f " . escapeshellarg($localComposer) . " && echo 'exists' 2>&1");
            if ($testProcess->successful() && trim($testProcess->output()) === 'exists') {
                Log::info("Composer найден локально в проекте: {$localComposer}");
                return $localComposer;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }

        // 2. Проверить явно указанный путь в .env
        $composerPath = env('COMPOSER_PATH');
        if ($composerPath && $composerPath !== '' && $composerPath !== 'composer') {
            $composerPath = trim($composerPath);
            $composerPath = trim($composerPath, '"\'');
            if ($composerPath) {
                Log::info("Composer путь из .env: {$composerPath}");
                return $composerPath;
            }
        }

        // 3. Fallback на 'composer'
        return 'composer';
    }

    /**
     * Очистить кеш package discovery
     */
    protected function clearPackageDiscoveryCache(): void
    {
        try {
            $packagesCachePath = $this->basePath . '/bootstrap/cache/packages.php';
            if (file_exists($packagesCachePath)) {
                unlink($packagesCachePath);
                Log::info('Кеш package discovery удален');
            }

            $servicesCachePath = $this->basePath . '/bootstrap/cache/services.php';
            if (file_exists($servicesCachePath)) {
                unlink($servicesCachePath);
                Log::info('Кеш сервис-провайдеров удален');
            }

            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan config:clear");

            if ($process->successful()) {
                Log::info('Кеш конфигурации очищен');
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке кеша package discovery', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выполнить миграции
     */
    protected function runMigrations(): array
    {
        try {
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan migrate --force");

            if ($process->successful()) {
                $output = $process->output();
                preg_match_all('/Migrating:\s+(\d{4}_\d{2}_\d{2}_\d{6}_[\w_]+)/', $output, $matches);
                $migrationsRun = count($matches[0]);

                return [
                    'status' => 'success',
                    'migrations_run' => $migrationsRun,
                    'message' => $migrationsRun > 0
                        ? "Выполнено миграций: {$migrationsRun}"
                        : 'Новых миграций не обнаружено',
                    'output' => $output,
                ];
            }

            return [
                'status' => 'error',
                'error' => $process->errorOutput() ?: $process->output(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Выполнить seeders
     */
    protected function runSeeders(?string $specificSeeder = null, bool $all = false): array
    {
        try {
            if (!$this->phpPath) {
                $this->phpPath = $this->getPhpPath();
            }

            $seeders = [];
            
            if ($specificSeeder) {
                $seeders = [$specificSeeder];
            } elseif ($all) {
                $process = Process::path($this->basePath)
                    ->timeout(600)
                    ->run("{$this->phpPath} artisan db:seed --force");

                if ($process->successful()) {
                    Log::info("✅ Все seeders выполнены успешно");
                    return [
                        'status' => 'success',
                        'total' => 1,
                        'success' => 1,
                        'failed' => 0,
                        'results' => ['all' => 'success'],
                        'message' => 'Все seeders выполнены успешно',
                    ];
                } else {
                    $error = $process->errorOutput() ?: $process->output();
                    Log::error("❌ Ошибка выполнения всех seeders", [
                        'error' => $error,
                    ]);
                    return [
                        'status' => 'error',
                        'error' => substr($error, 0, 500),
                    ];
                }
            } else {
                // По умолчанию - пустой список (можно настроить под проект)
                $seeders = [];
            }

            $results = [];
            $totalSuccess = 0;
            $totalFailed = 0;

            foreach ($seeders as $seeder) {
                try {
                    Log::info("Выполнение seeder: {$seeder}");
                    $process = Process::path($this->basePath)
                        ->timeout(300)
                        ->run("{$this->phpPath} artisan db:seed --class={$seeder} --force");

                    if ($process->successful()) {
                        $results[$seeder] = 'success';
                        $totalSuccess++;
                        Log::info("✅ Seeder выполнен успешно: {$seeder}");
                    } else {
                        $error = $process->errorOutput() ?: $process->output();
                        $results[$seeder] = 'error: ' . substr($error, 0, 200);
                        $totalFailed++;
                        Log::warning("⚠️ Ошибка выполнения seeder: {$seeder}", [
                            'error' => $error,
                        ]);
                    }
                } catch (\Exception $e) {
                    $results[$seeder] = 'exception: ' . $e->getMessage();
                    $totalFailed++;
                    Log::error("❌ Исключение при выполнении seeder: {$seeder}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'status' => $totalFailed === 0 ? 'success' : 'partial',
                'total' => count($seeders),
                'success' => $totalSuccess,
                'failed' => $totalFailed,
                'results' => $results,
                'message' => $totalFailed === 0
                    ? "Все seeders выполнены успешно ({$totalSuccess})"
                    : "Выполнено seeders: {$totalSuccess}, ошибок: {$totalFailed}",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Очистить временные файлы разработки
     */
    protected function cleanDevelopmentFiles(): void
    {
        try {
            $filesToRemove = [
                'public/hot',
            ];

            foreach ($filesToRemove as $file) {
                $filePath = $this->basePath . '/' . trim($file, '/');

                if (file_exists($filePath)) {
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    } elseif (is_dir($filePath)) {
                        $this->deleteDirectory($filePath);
                    }
                    Log::info("Удален файл разработки: {$file}");
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при очистке файлов разработки', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Рекурсивно удалить директорию
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Очистить все кеши
     */
    protected function clearAllCaches(): array
    {
        $commands = [
            'config:clear',
            'cache:clear',
            'route:clear',
            'view:clear',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $results[$command] = $process->successful();
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка очистки кеша: {$command}", ['error' => $e->getMessage()]);
            }
        }

        try {
            $process = Process::path($this->basePath)
                ->run("{$this->phpPath} artisan optimize:clear");
            
            $results['optimize:clear'] = $process->successful();
        } catch (\Exception $e) {
            $results['optimize:clear'] = false;
            Log::warning("Ошибка optimize:clear", ['error' => $e->getMessage()]);
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Оптимизировать приложение
     */
    protected function optimizeApplication(): array
    {
        $commands = [
            'route:clear',
            'config:cache',
            'route:cache',
            'view:cache',
        ];

        $results = [];
        foreach ($commands as $command) {
            try {
                $process = Process::path($this->basePath)
                    ->run("{$this->phpPath} artisan {$command}");

                $success = $process->successful();
                $results[$command] = $success;
                
                if (!$success) {
                    $error = $process->errorOutput() ?: $process->output();
                    Log::warning("Ошибка оптимизации: {$command}", ['error' => $error]);
                }
            } catch (\Exception $e) {
                $results[$command] = false;
                Log::warning("Ошибка оптимизации: {$command}", ['error' => $e->getMessage()]);
            }
        }

        return [
            'success' => !in_array(false, $results, true),
            'details' => $results,
        ];
    }

    /**
     * Проверить наличие файлов фронтенда
     */
    protected function checkFrontendFiles(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $manifestExists = file_exists($manifestPath);
        
        $assetsDir = public_path('build/assets');
        $assetsExists = is_dir($assetsDir);
        $assetsCount = 0;
        
        if ($assetsExists) {
            $files = glob($assetsDir . '/*.{js,css}', GLOB_BRACE);
            $assetsCount = $files ? count($files) : 0;
        }
        
        return [
            'manifest_exists' => $manifestExists,
            'assets_dir_exists' => $assetsExists,
            'assets_count' => $assetsCount,
        ];
    }

    /**
     * Выполнить npm install
     */
    protected function handleNpmInstall(): array
    {
        try {
            // Проверяем доступность npm
            if (!$this->isNpmAvailable()) {
                return [
                    'success' => false,
                    'status' => 'skipped',
                    'message' => 'npm недоступен на сервере',
                    'error' => 'npm не найден в системе',
                ];
            }

            // Проверяем наличие package.json
            $packageJsonPath = $this->basePath . '/package.json';
            if (!file_exists($packageJsonPath)) {
                return [
                    'success' => false,
                    'status' => 'skipped',
                    'message' => 'package.json не найден',
                    'error' => 'package.json отсутствует',
                ];
            }

            Log::info('📦 Выполнение npm install...');
            
            // Получаем путь к npm (может быть указан в .env)
            $npmPath = $this->getNpmPath();
            
            $env = [];
            // Увеличиваем таймаут для npm install (может занять много времени)
            $process = Process::path($this->basePath)
                ->timeout(600) // 10 минут
                ->env($env)
                ->run("{$npmPath} install --production 2>&1");

            if ($process->successful()) {
                Log::info('✅ npm install выполнен успешно');
                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'npm install выполнен успешно',
                    'output' => $process->output(),
                ];
            }

            $error = $process->errorOutput() ?: $process->output();
            Log::warning('⚠️ npm install завершился с ошибкой', [
                'error' => $error,
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'npm install завершился с ошибкой',
                'error' => substr($error, 0, 500), // Ограничиваем длину ошибки
            ];
        } catch (\Exception $e) {
            Log::error('❌ Исключение при выполнении npm install', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Исключение при выполнении npm install',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Выполнить npm run build (Vite — сборка админки)
     */
    protected function handleNpmBuild(): array
    {
        try {
            if (!$this->isNpmAvailable()) {
                return [
                    'success' => false,
                    'status' => 'skipped',
                    'message' => 'npm недоступен',
                ];
            }
            $npmPath = $this->getNpmPath();
            Log::info('🔨 Выполнение npm run build...');
            $process = Process::path($this->basePath)
                ->timeout(300)
                ->run("{$npmPath} run build 2>&1");
            if ($process->successful()) {
                Log::info('✅ npm run build выполнен успешно');
                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'npm run build выполнен успешно',
                ];
            }
            $error = $process->errorOutput() ?: $process->output();
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'npm run build завершился с ошибкой',
                'error' => substr($error, 0, 500),
            ];
        } catch (\Exception $e) {
            Log::error('Ошибка npm run build: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Исключение при npm run build',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Проверить доступность npm
     */
    protected function isNpmAvailable(): bool
    {
        try {
            $npmPath = $this->getNpmPath();
            
            // Проверка через which (Unix-like)
            $result = shell_exec("which {$npmPath} 2>/dev/null");
            if ($result && trim($result)) {
                return true;
            }

            // Проверка через exec (версия npm)
            exec("{$npmPath} --version 2>&1", $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить путь к npm
     */
    protected function getNpmPath(): string
    {
        // 1. Проверить явно указанный путь в .env
        $npmPath = env('NPM_PATH');
        if ($npmPath && $this->isNpmExecutable($npmPath)) {
            return $npmPath;
        }

        // 2. Попробовать автоматически найти npm
        $possiblePaths = ['npm', 'npm8', 'npm9', 'npm10'];
        foreach ($possiblePaths as $path) {
            if ($this->isNpmExecutable($path)) {
                return $path;
            }
        }

        // 3. Fallback на 'npm'
        return 'npm';
    }

    /**
     * Проверить доступность npm по пути
     */
    protected function isNpmExecutable(string $path): bool
    {
        try {
            exec("{$path} --version 2>&1", $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить текущий commit hash
     */
    protected function getCurrentCommitHash(): ?string
    {
        try {
            $safeDirectoryPath = escapeshellarg($this->basePath);
            $process = Process::path($this->basePath)
                ->env([
                    'GIT_CEILING_DIRECTORIES' => dirname($this->basePath),
                ])
                ->run("git -c safe.directory={$safeDirectoryPath} rev-parse HEAD 2>&1");

            if ($process->successful()) {
                $hash = trim($process->output());
                if (!empty($hash) && strlen($hash) === 40) {
                    return $hash;
                }
            } else {
                Log::warning('Не удалось получить commit hash', [
                    'output' => $process->output(),
                    'error' => $process->errorOutput(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка при получении commit hash', [
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }
}
