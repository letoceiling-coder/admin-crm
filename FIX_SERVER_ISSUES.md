# Исправление проблем с доступом к сайту

## Проблемы:
1. `403 Forbidden` - нет доступа к ресурсу
2. `404 Not Found` - URL не найден

## Решения:

### 1. Проверка структуры директорий на сервере

Через веб-интерфейс хостинга (File Manager) или Terminal проверьте:

```
~/crm.neeklo.ru/public_html/
```

Должна быть такая структура:
```
public_html/
├── .htaccess          ← должен быть в корне public_html
├── .env
├── artisan
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── .htaccess     ← должен быть в public/
│   ├── index.php
│   └── ...
├── resources/
├── routes/
├── storage/
└── ...
```

### 2. Проблема: Файлы в неправильной директории

**Если файлы находятся в `~/crm.neeklo.ru/public_html/`, но Apache настроен на `public/`:**

**Вариант А: Переместить файлы в public (если DocumentRoot указывает на public/)**

Через Terminal или File Manager:
```bash
cd ~/crm.neeklo.ru/public_html

# Если DocumentRoot указывает на public_html/public/
# То нужно переместить содержимое public/ на уровень выше
# ИЛИ настроить DocumentRoot на public_html/
```

**Вариант Б: Настроить DocumentRoot на public_html/public/**

В панели управления хостингом найдите настройки домена и укажите:
- DocumentRoot: `~/crm.neeklo.ru/public_html/public`

### 3. Проверка и создание .htaccess файлов

#### Файл 1: `~/crm.neeklo.ru/public_html/.htaccess`

Создайте или проверьте содержимое (должно быть):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Перенаправление всех запросов в public директорию
    # Исключаем уже запросы к public и существующие файлы/директории
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]

    # Перенаправление корневого запроса на public/index.php
    RewriteCond %{REQUEST_URI} ^/$
    RewriteRule ^(.*)$ public/index.php [L]
</IfModule>

# Запрет доступа к скрытым файлам и директориям
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Запрет доступа к важным файлам конфигурации
<FilesMatch "^(\.env|composer\.(json|lock)|package(-lock)?\.json|\.git|\.gitignore|\.gitattributes|artisan|install\.sh)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Запрет доступа к директориям
<IfModule mod_rewrite.c>
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/index.php [L]
</IfModule>
```

#### Файл 2: `~/crm.neeklo.ru/public_html/public/.htaccess`

Проверьте содержимое (должно быть):

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 4. Настройка прав доступа

Через Terminal выполните:

```bash
cd ~/crm.neeklo.ru/public_html

# Установить права на директории
chmod 755 .
chmod 755 public
chmod -R 755 app bootstrap config database resources routes

# Установить права на storage и cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Установить права на файлы
chmod 644 .htaccess
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 755 artisan

# Установить владельца (замените username на ваш)
chown -R dsc23ytp:dsc23ytp .
```

### 5. Если DocumentRoot указывает на public/

Если Apache настроен так, что DocumentRoot = `public_html/public/`, то:

**Вариант А: Переместить .htaccess**

1. Удалите `.htaccess` из `public_html/`
2. Убедитесь, что `.htaccess` есть в `public_html/public/`

**Вариант Б: Изменить DocumentRoot в настройках домена**

В панели управления хостингом измените DocumentRoot на:
```
~/crm.neeklo.ru/public_html/public
```

### 6. Проверка через Terminal

```bash
cd ~/crm.neeklo.ru/public_html

# Проверить структуру
ls -la
ls -la public/

# Проверить наличие .htaccess
ls -la .htaccess
ls -la public/.htaccess

# Проверить права
ls -ld .
ls -ld public/
ls -ld storage/

# Проверить содержимое public/index.php
head -20 public/index.php
```

### 7. Проверка логов Apache

Через панель управления хостингом найдите логи ошибок Apache и проверьте:
- `/var/log/apache2/error.log`
- Или через панель управления: "Error Logs"

Ищите ошибки типа:
- "Permission denied"
- "Directory index forbidden"
- "Options FollowSymLinks or SymLinksIfOwnerMatch is off"

### 8. Альтернативное решение: Символическая ссылка

Если DocumentRoot должен указывать на `public/`, но файлы в `public_html/`:

```bash
cd ~/crm.neeklo.ru
# Создать символическую ссылку
ln -s public_html/public public
```

### 9. Проверка конфигурации Apache

Если у вас есть доступ к конфигурации Apache, проверьте:

```apache
<VirtualHost *:80>
    ServerName crm.neeklo.ru
    DocumentRoot /home/dsc23ytp/crm.neeklo.ru/public_html/public
    
    <Directory /home/dsc23ytp/crm.neeklo.ru/public_html/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

### 10. Быстрая диагностика

Выполните через Terminal:

```bash
cd ~/crm.neeklo.ru/public_html

# 1. Проверить где мы находимся
pwd

# 2. Проверить структуру
ls -la | head -20

# 3. Проверить public/
ls -la public/ | head -20

# 4. Проверить .htaccess
cat .htaccess
cat public/.htaccess

# 5. Проверить права
stat .
stat public/
stat storage/

# 6. Проверить index.php
head -5 public/index.php
```

## После исправления:

1. Очистите кеш браузера
2. Проверьте сайт: https://crm.neeklo.ru
3. Проверьте API: https://crm.neeklo.ru/api/deploy

## Если ничего не помогает:

Свяжитесь с поддержкой хостинга и предоставьте:
- Путь к проекту: `~/crm.neeklo.ru/public_html`
- Структуру директорий
- Содержимое логов ошибок Apache
- Настройки DocumentRoot для домена
