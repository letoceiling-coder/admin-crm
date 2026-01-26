<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy
                            {--message= : Кастомное сообщение для коммита}
                            {--skip-build : Пропустить npm run build}
                            {--dry-run : Показать что будет сделано без выполнения}
                            {--insecure : Отключить проверку SSL сертификата (для разработки)}
                            {--with-seed : Выполнить seeders на сервере (по умолчанию пропускаются)}
                            {--force : Принудительная отправка (force push) - перезаписывает удаленную ветку}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Деплой проекта: сборка, коммит в git, отправка на сервер';

    /**
     * Git repository URL
     *
     * @var string
     */
    protected $gitRepository = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Начало процесса деплоя...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        try {
            // Шаг 0: Проверка и установка composer
            $this->ensureComposerInstalled($dryRun);

            // Шаг 1: Сборка фронтенда
            if (!$this->option('skip-build')) {
                $this->buildFrontend($dryRun);
            } else {
                $this->warn('⚠️  Пропущена сборка фронтенда (--skip-build)');
            }

            // Шаг 2: Проверка git статуса
            $hasChanges = $this->checkGitStatus($dryRun);

            if (!$hasChanges && !$dryRun) {
                $this->warn('⚠️  Нет изменений для коммита.');
                $this->info('  ℹ️  Продолжаем деплой без изменений (автоматический режим)');
            }

            // Шаг 3: Проверка remote репозитория
            $this->ensureGitRemote($dryRun);

            // Шаг 3.5: Проверка актуальности коммитов
            $this->checkCommitsUpToDate($dryRun);

            // Шаг 4: Добавление изменений в git
            if ($hasChanges) {
                $this->addChangesToGit($dryRun);

                // Шаг 4.5: Обновление версии приложения для сброса кеша
                if (!$dryRun) {
                    $this->updateAppVersion();
                }

                // Шаг 5: Создание коммита
                $commitMessage = $this->createCommit($dryRun);

                // Шаг 6: Отправка в репозиторий
                $this->pushToRepository($dryRun);
            }

            // Шаг 7: Отправка POST запроса на сервер
            if (!$dryRun) {
                $this->sendDeployRequest();
            } else {
                $this->info('📤 [DRY-RUN] Отправка POST запроса на сервер пропущена');
            }

            $this->newLine();
            $this->info('✅ Деплой успешно завершен!');
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка деплоя: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Проверка и установка composer в bin/composer
     */
    protected function ensureComposerInstalled(bool $dryRun): void
    {
        $this->info('🔧 Шаг 0: Проверка composer...');

        $composerPath = base_path('bin/composer');
        $binDir = base_path('bin');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Проверка наличия bin/composer');
            return;
        }

        // Проверяем наличие bin/composer
        if (File::exists($composerPath)) {
            // Проверяем, что файл исполняемый
            if (is_executable($composerPath) || is_file($composerPath)) {
                $this->line('  ✅ Composer найден в bin/composer');
                $this->newLine();
                return;
            }
        }

        // Composer не найден, нужно установить
        $this->line('  📥 Composer не найден, выполняется установка...');

        try {
            // Создаем директорию bin, если её нет
            if (!File::isDirectory($binDir)) {
                File::makeDirectory($binDir, 0755, true);
                $this->line('  📁 Создана директория bin/');
            }

            // Определяем PHP путь
            $phpPath = $this->getPhpPath();

            // Скачиваем composer installer
            $this->line('  📥 Скачивание composer installer...');
            $installerUrl = 'https://getcomposer.org/installer';
            
            $installerContent = @file_get_contents($installerUrl);
            
            if ($installerContent === false) {
                // Пробуем через curl
                $curlProcess = Process::run("curl -sS {$installerUrl}");
                if ($curlProcess->successful()) {
                    $installerContent = $curlProcess->output();
                } else {
                    throw new \Exception("Не удалось скачать composer installer. Проверьте интернет-соединение.");
                }
            }

            // Сохраняем installer во временный файл
            $installerPath = base_path('composer-installer.php');
            File::put($installerPath, $installerContent);

            // Выполняем installer
            $this->line('  🔄 Установка composer...');
            $installProcess = Process::path(base_path())
                ->run("{$phpPath} {$installerPath} --install-dir=" . escapeshellarg($binDir) . " --filename=composer 2>&1");

            // Удаляем временный installer
            if (File::exists($installerPath)) {
                File::delete($installerPath);
            }

            if (!$installProcess->successful()) {
                // Пробуем альтернативный способ - скачать composer.phar напрямую
                $this->line('  🔄 Попытка альтернативного способа установки...');
                $pharUrl = 'https://getcomposer.org/download/latest-stable/composer.phar';
                
                $pharContent = @file_get_contents($pharUrl);
                if ($pharContent === false) {
                    $curlProcess = Process::run("curl -sS {$pharUrl}");
                    if ($curlProcess->successful()) {
                        $pharContent = $curlProcess->output();
                    }
                }

                if ($pharContent !== false) {
                    File::put($composerPath, $pharContent);
                    // Делаем файл исполняемым
                    @chmod($composerPath, 0755);
                } else {
                    throw new \Exception("Не удалось установить composer. Ошибка: " . $installProcess->errorOutput());
                }
            }

            // Проверяем, что composer установлен
            if (File::exists($composerPath)) {
                // Делаем файл исполняемым
                @chmod($composerPath, 0755);
                
                // Проверяем работоспособность
                $testProcess = Process::run("{$phpPath} {$composerPath} --version 2>&1");
                if ($testProcess->successful()) {
                    $version = trim($testProcess->output());
                    $this->info("  ✅ Composer успешно установлен: {$version}");
                } else {
                    $this->warn('  ⚠️  Composer установлен, но проверка версии не удалась');
                }
            } else {
                throw new \Exception("Composer не был установлен. Файл не найден: {$composerPath}");
            }

        } catch (\Exception $e) {
            $this->warn("  ⚠️  Не удалось установить composer: " . $e->getMessage());
            $this->warn('  💡 Убедитесь, что composer установлен глобально или установите его вручную в bin/composer');
            // Не прерываем выполнение, так как может быть установлен глобально
        }

        $this->newLine();
    }

    /**
     * Определить путь к PHP
     */
    protected function getPhpPath(): string
    {
        // Проверяем явно указанный путь в .env
        $phpPath = env('PHP_PATH');
        if ($phpPath && $this->isPhpExecutable($phpPath)) {
            return $phpPath;
        }

        // Пробуем автоматически найти PHP
        $possiblePaths = ['php8.2', 'php8.3', 'php8.1', 'php'];
        foreach ($possiblePaths as $path) {
            if ($this->isPhpExecutable($path)) {
                return $path;
            }
        }

        // Fallback на 'php'
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
     * Сборка фронтенда
     */
    protected function buildFrontend(bool $dryRun): void
    {
        $this->info('📦 Шаг 1: Сборка фронтенда...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: npm run build');
            return;
        }

        // Увеличиваем таймаут до 5 минут (300 секунд) для сборки фронтенда
        $process = Process::timeout(300)->run('npm run build');

        if (!$process->successful()) {
            throw new \Exception("Ошибка сборки фронтенда:\n" . $process->errorOutput());
        }

        // Проверяем наличие собранных файлов
        $buildDir = public_path('build');
        if (!File::exists($buildDir)) {
            throw new \Exception("Директория {$buildDir} не найдена после сборки");
        }

        $this->info('  ✅ Сборка завершена успешно');
        $this->newLine();
    }

    /**
     * Проверка git статуса
     */
    protected function checkGitStatus(bool $dryRun): bool
    {
        $this->info('📋 Шаг 2: Проверка статуса git...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git status');
            return true;
        }

        $process = Process::run('git status --porcelain');

        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git статуса:\n" . $process->errorOutput());
        }

        $output = trim($process->output());
        $hasChanges = !empty($output);

        if ($hasChanges) {
            $this->line('  📝 Найдены изменения:');
            $this->line($output);
        } else {
            $this->line('  ℹ️  Изменений не обнаружено');
        }

        $this->newLine();
        return $hasChanges;
    }

    /**
     * Проверка и настройка git remote
     */
    protected function ensureGitRemote(bool $dryRun): void
    {
        $this->info('🔗 Шаг 3: Проверка git remote...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git remote -v');
            return;
        }

        $process = Process::run('git remote -v');

        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git remote:\n" . $process->errorOutput());
        }

        $output = trim($process->output());

        if (empty($output)) {
            $this->warn('  ⚠️  Git remote не настроен. Настройте remote вручную.');
        } else {
            $this->line('  ✅ Remote настроен');
        }

        $this->newLine();
    }

    /**
     * Проверка актуальности коммитов
     */
    protected function checkCommitsUpToDate(bool $dryRun): void
    {
        $this->info('🔍 Шаг 3.5: Проверка актуальности коммитов...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: проверка коммитов');
            return;
        }

        try {
            // Получаем текущую ветку
            $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
            $currentBranch = trim($branchProcess->output()) ?: 'main';

            // Получаем локальный коммит
            $localCommitProcess = Process::run('git rev-parse HEAD');
            $localCommit = trim($localCommitProcess->output());

            if (empty($localCommit)) {
                $this->warn('  ⚠️  Не удалось определить локальный коммит');
                $this->newLine();
                return;
            }

            // Обновляем информацию о remote (fetch)
            $this->line('  📥 Обновление информации о remote...');
            $fetchProcess = Process::run("git fetch origin {$currentBranch} 2>&1");

            if (!$fetchProcess->successful()) {
                $this->warn('  ⚠️  Не удалось обновить информацию о remote (возможно, ветка еще не существует на remote)');
                $this->newLine();
                return;
            }

            // Получаем удаленный коммит
            $remoteCommitProcess = Process::run("git rev-parse origin/{$currentBranch} 2>&1");
            $remoteCommit = trim($remoteCommitProcess->output());

            if (empty($remoteCommit)) {
                $this->line('  ℹ️  Удаленная ветка не найдена (первый деплой?)');
                $this->newLine();
                return;
            }

            // Сравниваем коммиты
            $localShort = substr($localCommit, 0, 7);
            $remoteShort = substr($remoteCommit, 0, 7);

            $this->line("  📍 Локальный коммит:  {$localShort}");
            $this->line("  📍 Удаленный коммит: {$remoteShort}");

            if ($localCommit === $remoteCommit) {
                $this->newLine();
                $this->warn('  ⚠️  Локальный и удаленный коммиты совпадают!');
                $this->warn('  ⚠️  На сервере уже установлена эта версия.');
            }

            $this->newLine();
        } catch (\Exception $e) {
            $this->warn('  ⚠️  Не удалось проверить коммиты: ' . $e->getMessage());
            $this->line('  ℹ️  Продолжаем деплой...');
            $this->newLine();
        }
    }

    /**
     * Добавление изменений в git
     */
    protected function addChangesToGit(bool $dryRun): void
    {
        $this->info('➕ Шаг 4: Добавление изменений в git...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git add .');
            return;
        }

        // Сначала принудительно добавляем собранные файлы (на случай если они были в .gitignore)
        if (File::exists(public_path('build'))) {
            $process = Process::run('git add -f public/build');
            if (!$process->successful()) {
                $this->warn('  ⚠️  Предупреждение: не удалось добавить public/build');
            } else {
                $this->line('  ✅ Добавлен public/build');
            }
        }

        // Затем добавляем все остальные изменения
        $process = Process::run('git add .');

        if (!$process->successful()) {
            throw new \Exception("Ошибка добавления файлов в git:\n" . $process->errorOutput());
        }

        $this->info('  ✅ Файлы добавлены в git');
        $this->newLine();
    }

    /**
     * Создание коммита
     */
    protected function createCommit(bool $dryRun): string
    {
        $this->info('💾 Шаг 5: Создание коммита...');

        $customMessage = $this->option('message');
        $commitMessage = $customMessage ?: 'Deploy: ' . now()->format('Y-m-d H:i:s');

        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: git commit -m \"{$commitMessage}\"");
            return $commitMessage;
        }

        $process = Process::run(['git', 'commit', '-m', $commitMessage]);

        if (!$process->successful()) {
            // Возможно, коммит уже существует или нет изменений
            $errorOutput = $process->errorOutput();
            if (strpos($errorOutput, 'nothing to commit') !== false) {
                $this->warn('  ⚠️  Нет изменений для коммита');
                return $commitMessage;
            }
            throw new \Exception("Ошибка создания коммита:\n" . $errorOutput);
        }

        $this->info("  ✅ Коммит создан: {$commitMessage}");
        $this->newLine();
        return $commitMessage;
    }

    /**
     * Отправка в репозиторий
     */
    protected function pushToRepository(bool $dryRun): void
    {
        $this->info('📤 Шаг 6: Отправка в репозиторий...');

        // Определяем текущую ветку
        $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
        $branch = trim($branchProcess->output()) ?: 'main';

        $forcePush = $this->option('force');

        if ($forcePush) {
            $this->warn('  ⚠️  ВНИМАНИЕ: Используется принудительная отправка (--force)');
            $this->warn('  ⚠️  Это перезапишет удаленную ветку и может удалить коммиты!');
        }

        if ($dryRun) {
            $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
            $this->line("  [DRY-RUN] Выполнение: {$pushCommand}");
            return;
        }

        // Увеличиваем таймаут для git push
        $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
        $process = Process::timeout(300) // 5 минут
            ->run($pushCommand);

        if (!$process->successful()) {
            $errorOutput = $process->errorOutput();

            // Проверяем, нужно ли установить upstream
            if (str_contains($errorOutput, 'no upstream branch')) {
                $this->line("  🔄 Установка upstream для ветки {$branch}...");
                $upstreamCommand = $forcePush ? "git push --force -u origin {$branch}" : "git push -u origin {$branch}";
                $process = Process::timeout(300)
                    ->run($upstreamCommand);

                if (!$process->successful()) {
                    throw new \Exception("Ошибка отправки в репозиторий:\n" . $process->errorOutput());
                }
            } else {
                throw new \Exception("Ошибка отправки в репозиторий:\n" . $errorOutput);
            }
        }

        $this->info("  ✅ Изменения отправлены в ветку: {$branch}" . ($forcePush ? " (force push)" : ""));
        $this->newLine();
    }

    /**
     * Отправка POST запроса на сервер
     */
    protected function sendDeployRequest(): void
    {
        $this->info('🌐 Шаг 7: Отправка запроса на сервер...');

        $serverUrl = env('DEPLOY_SERVER_URL');
        $deployToken = env('DEPLOY_TOKEN');

        if (!$serverUrl) {
            $this->warn('  ⚠️  DEPLOY_SERVER_URL не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_SERVER_URL и DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        if (!$deployToken) {
            $this->warn('  ⚠️  DEPLOY_TOKEN не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        // Получаем текущий commit hash
        $commitProcess = Process::run('git rev-parse HEAD');
        $commitHash = trim($commitProcess->output()) ?: 'unknown';

        // Формируем правильный URL
        $deployUrl = rtrim($serverUrl, '/');
        if (str_contains($deployUrl, '/api/deploy')) {
            $pos = strpos($deployUrl, '/api/deploy');
            $deployUrl = substr($deployUrl, 0, $pos);
            $deployUrl = rtrim($deployUrl, '/');
        }
        $deployUrl .= '/api/deploy';

        $this->line("  📡 URL: {$deployUrl}");
        $this->line("  🔑 Commit: " . substr($commitHash, 0, 7));
        $this->line("  🔐 Token: " . (substr($deployToken, 0, 3) . '...' . substr($deployToken, -3)));

        try {
            $httpClient = Http::timeout(300); // 5 минут таймаут

            // Отключить проверку SSL для локальной разработки (если указана опция)
            if ($this->option('insecure') || env('APP_ENV') === 'local') {
                $httpClient = $httpClient->withoutVerifying();
                if ($this->option('insecure')) {
                    $this->warn('  ⚠️  Проверка SSL сертификата отключена (--insecure)');
                }
            }

            $response = $httpClient
                ->withHeaders([
                    'X-Deploy-Token' => $deployToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'Admin-CRM-Deploy/1.0',
                ])
                ->post($deployUrl, [
                    'commit_hash' => $commitHash,
                    'branch' => trim(Process::run('git rev-parse --abbrev-ref HEAD')->output() ?: 'main'),
                    'deployed_by' => get_current_user(),
                    'timestamp' => now()->toDateTimeString(),
                    'run_seeders' => $this->option('with-seed'),
                ]);

            // Проверяем статус ответа
            if ($response->successful()) {
                $data = $response->json();

                $this->newLine();
                $this->info('  ✅ Сервер ответил успешно:');

                if (isset($data['data'])) {
                    $dataArray = $data['data'];

                    if (isset($dataArray['php_path'])) {
                        $this->line("     PHP: {$dataArray['php_path']} (v{$dataArray['php_version']})");
                    }

                    if (isset($dataArray['git_pull'])) {
                        $this->line("     Git Pull: {$dataArray['git_pull']}");
                    }

                    // Информация об обновлении кода
                    if (isset($dataArray['commit_changed']) && isset($dataArray['old_commit_hash']) && isset($dataArray['new_commit_hash'])) {
                        $oldCommit = substr($dataArray['old_commit_hash'], 0, 7);
                        $newCommit = substr($dataArray['new_commit_hash'], 0, 7);
                        
                        if ($dataArray['commit_changed']) {
                            $this->info("     ✅ Код обновлен: {$oldCommit} → {$newCommit}");
                        } else {
                            $this->line("     ℹ️  Код актуален (коммит {$newCommit}, изменений нет)");
                        }
                    } elseif (isset($dataArray['new_commit_hash'])) {
                        $currentCommit = substr($dataArray['new_commit_hash'], 0, 7);
                        $this->line("     📦 Текущий коммит: {$currentCommit}");
                    }

                    if (isset($dataArray['composer_install'])) {
                        $this->line("     Composer: {$dataArray['composer_install']}");
                    }

                    if (isset($dataArray['migrations'])) {
                        $migrations = $dataArray['migrations'];
                        if (is_array($migrations) && isset($migrations['status'])) {
                            if ($migrations['status'] === 'success') {
                                $this->line("     Миграции: " . ($migrations['message'] ?? 'успешно'));
                            } else {
                                $this->warn("     Миграции: ошибка - " . ($migrations['error'] ?? 'неизвестная ошибка'));
                            }
                        }
                    }

                    if (isset($dataArray['duration_seconds'])) {
                        $this->line("     Время выполнения: {$dataArray['duration_seconds']}с");
                    }

                    if (isset($dataArray['deployed_at'])) {
                        $this->line("     Дата: {$dataArray['deployed_at']}");
                    }
                }
            } else {
                $errorData = $response->json();
                throw new \Exception(
                    "Ошибка деплоя на сервере (HTTP {$response->status()}): " .
                    ($errorData['message'] ?? $response->body())
                );
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка отправки запроса');
            $this->line("  🔍 Детали: " . $e->getMessage());

            if ($this->option('verbose')) {
                $this->line("  📋 Trace: " . $e->getTraceAsString());
            }

            throw new \Exception("Ошибка отправки запроса: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Обновление версии приложения для сброса кеша
     */
    protected function updateAppVersion(): void
    {
        try {
            // Получаем хеш последнего коммита
            $process = Process::run('git rev-parse --short HEAD');
            $gitHash = trim($process->output());
            
            if ($process->successful() && !empty($gitHash)) {
                // Используем git hash как версию
                $version = $gitHash;
            } else {
                // Если не удалось получить git hash, используем timestamp
                $version = (string)(int)(microtime(true) * 1000);
            }
            
            // Обновляем .env файл
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                
                // Заменяем или добавляем APP_VERSION
                if (preg_match('/^APP_VERSION=.*$/m', $envContent)) {
                    $envContent = preg_replace('/^APP_VERSION=.*$/m', "APP_VERSION={$version}", $envContent);
                } else {
                    $envContent .= "\nAPP_VERSION={$version}\n";
                }
                
                File::put($envPath, $envContent);
                $this->line("  ✅ Версия приложения обновлена: {$version}");
            }
        } catch (\Exception $e) {
            // Не критично, просто логируем
            $this->warn("  ⚠️  Не удалось обновить версию: " . $e->getMessage());
        }
    }
}
