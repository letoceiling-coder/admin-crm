# Инструкция по деплою Frontend

## Команда artisan deploy

Команда `php artisan deploy` теперь автоматически собирает frontend из папки `/frontend` и копирует собранные файлы в `public/miniapp/`.

## Процесс сборки

1. **Проверка директории frontend/**
   - Если директория не существует, сборка пропускается

2. **Установка зависимостей**
   - Выполняется `npm install` в папке `frontend/`
   - Таймаут: 10 минут

3. **Сборка проекта**
   - Выполняется `npm run build` в папке `frontend/`
   - Таймаут: 10 минут
   - Результат сохраняется в `frontend/dist/`

4. **Копирование файлов**
   - Содержимое `frontend/dist/` копируется в `public/miniapp/`
   - Старые файлы в `public/miniapp/` удаляются перед копированием

5. **Добавление в git**
   - Файлы из `public/miniapp/` принудительно добавляются в git (`git add -f`)

## Опции команды

```bash
# Обычный деплой (с сборкой frontend)
php artisan deploy

# Пропустить сборку frontend
php artisan deploy --skip-build

# Пропустить npm install на сервере
php artisan deploy --skip-npm

# Тестовый запуск (без выполнения команд)
php artisan deploy --dry-run

# С кастомным сообщением коммита
php artisan deploy --message="Обновление frontend"
```

## Структура файлов

```
crm/
├── frontend/              # Исходники React приложения
│   ├── src/
│   ├── package.json
│   └── dist/             # Собранные файлы (не в git)
│
└── public/
    └── miniapp/          # Собранные файлы для продакшена (в git)
        ├── index.html
        ├── assets/
        └── ...
```

## Важные замечания

1. **Файлы в git**: Собранные файлы из `public/miniapp/` должны быть в git, чтобы они попадали на сервер при деплое.

2. **.gitignore**: 
   - `frontend/dist/` - игнорируется (временные файлы сборки)
   - `public/miniapp/` - НЕ игнорируется (должен быть в git)

3. **На сервере**: После git pull на сервере файлы из `public/miniapp/` уже будут доступны, дополнительная сборка не требуется.

4. **Локальная разработка**: 
   - Для разработки используйте `cd frontend && npm run dev`
   - Для сборки: `cd frontend && npm run build`

## Проверка сборки

После выполнения команды проверьте:

1. Существует ли `public/miniapp/index.html`
2. Есть ли файлы в `public/miniapp/assets/`
3. Добавлены ли файлы в git: `git status public/miniapp/`

## Устранение проблем

### Ошибка: "Директория frontend/dist не найдена"
- Проверьте, что `npm run build` выполнился успешно
- Проверьте наличие `frontend/package.json`

### Ошибка: "npm install завершился с ошибкой"
- Проверьте доступность npm: `npm --version`
- Проверьте интернет-соединение
- Попробуйте выполнить вручную: `cd frontend && npm install`

### Файлы не копируются
- Проверьте права доступа к папке `public/`
- Убедитесь, что директория `frontend/dist/` существует после сборки
