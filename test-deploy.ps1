# Скрипт для тестирования deploy endpoint через HTTPS
# Использование: .\test-deploy.ps1

$deployUrl = "https://crm.neeklo.ru/api/deploy"
$deployToken = "4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ТЕСТИРОВАНИЕ DEPLOY ENDPOINT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Тест 1: Проверка доступности сайта
Write-Host "1. Проверка доступности сайта..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "https://crm.neeklo.ru" -Method Get -UseBasicParsing -TimeoutSec 10
    Write-Host "   ✓ Сайт доступен (HTTP $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Сайт недоступен: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Тест 2: Проверка endpoint с неверным токеном
Write-Host ""
Write-Host "2. Проверка endpoint с неверным токеном..." -ForegroundColor Yellow
try {
    $headers = @{
        "X-Deploy-Token" = "wrong-token"
        "Content-Type" = "application/json"
    }
    $response = Invoke-RestMethod -Uri $deployUrl -Method Post -Headers $headers -ErrorAction Stop
    Write-Host "   ✗ ОШИБКА: Endpoint принял неверный токен!" -ForegroundColor Red
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 403) {
        Write-Host "   ✓ Защита работает (HTTP 403 - доступ запрещен)" -ForegroundColor Green
    } else {
        Write-Host "   ⚠ Неожиданный ответ: HTTP $statusCode" -ForegroundColor Yellow
    }
}

# Тест 3: Проверка endpoint с правильным токеном (dry-run)
Write-Host ""
Write-Host "3. Проверка endpoint с правильным токеном..." -ForegroundColor Yellow
Write-Host "   ВНИМАНИЕ: Это выполнит реальный deploy на сервере!" -ForegroundColor Red
$confirm = Read-Host "   Продолжить? (y/n)"

if ($confirm -ne "y" -and $confirm -ne "Y") {
    Write-Host "   Отменено пользователем" -ForegroundColor Yellow
    exit 0
}

try {
    $headers = @{
        "X-Deploy-Token" = $deployToken
        "Content-Type" = "application/json"
        "Accept" = "application/json"
    }
    $body = @{
        branch = "main"
        run_seeders = $false
    } | ConvertTo-Json

    Write-Host "   Отправка запроса..." -ForegroundColor Yellow
    $response = Invoke-RestMethod -Uri $deployUrl -Method Post -Headers $headers -Body $body -TimeoutSec 600
    
    Write-Host ""
    Write-Host "   ✓ Deploy выполнен!" -ForegroundColor Green
    Write-Host ""
    Write-Host "   Результат:" -ForegroundColor Cyan
    Write-Host "   - Успех: $($response.success)" -ForegroundColor $(if ($response.success) { "Green" } else { "Red" })
    Write-Host "   - Сообщение: $($response.message)" -ForegroundColor Cyan
    
    if ($response.data) {
        $data = $response.data
        if ($data.php_path) {
            Write-Host "   - PHP: $($data.php_path) (v$($data.php_version))" -ForegroundColor Cyan
        }
        if ($data.git_pull) {
            Write-Host "   - Git Pull: $($data.git_pull)" -ForegroundColor Cyan
        }
        if ($data.commit_changed) {
            Write-Host "   - Код обновлен: $($data.old_commit_hash.Substring(0,7)) → $($data.new_commit_hash.Substring(0,7))" -ForegroundColor Green
        }
        if ($data.composer_install) {
            Write-Host "   - Composer: $($data.composer_install)" -ForegroundColor Cyan
        }
        if ($data.migrations) {
            Write-Host "   - Миграции: $($data.migrations.message)" -ForegroundColor Cyan
        }
        if ($data.duration_seconds) {
            Write-Host "   - Время выполнения: $($data.duration_seconds)с" -ForegroundColor Cyan
        }
    }
    
    if (-not $response.success) {
        Write-Host ""
        Write-Host "   ✗ Ошибка deploy: $($response.message)" -ForegroundColor Red
        if ($response.data.error) {
            Write-Host "   Детали: $($response.data.error)" -ForegroundColor Red
        }
    }
    
} catch {
    Write-Host ""
    Write-Host "   ✗ Ошибка при выполнении deploy:" -ForegroundColor Red
    Write-Host "   $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "   HTTP Status: $statusCode" -ForegroundColor Red
        
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Host "   Ответ сервера: $responseBody" -ForegroundColor Yellow
        } catch {
            # Игнорируем ошибки чтения ответа
        }
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ТЕСТИРОВАНИЕ ЗАВЕРШЕНО" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
