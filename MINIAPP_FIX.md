# Исправление проблемы с MIME type для Mini App

## Проблема

При загрузке Mini App возникала ошибка:
```
Failed to load module script: Expected a JavaScript-or-Wasm module script but the server responded with a MIME type of "text/html"
```

## Причина

1. В `index.html` пути к assets были `/assets/...` вместо `/miniapp/assets/...`
2. Роуты не правильно обрабатывали запросы к статическим файлам
3. Не был настроен правильный MIME type для JS файлов

## Решение

### 1. Настроен base path в vite.config.ts

```typescript
export default defineConfig({
  base: '/miniapp/',
  // ...
})
```

Теперь все пути в собранных файлах будут начинаться с `/miniapp/`.

### 2. Улучшены роуты в web.php

- Добавлена защита от path traversal
- Правильное определение MIME типов для разных файлов
- Роуты для `/miniapp/{any}` обрабатываются ДО общих роутов

### 3. Структура файлов

```
public/
└── miniapp/
    ├── index.html          (пути: /miniapp/assets/...)
    ├── assets/
    │   ├── index-*.js      (MIME: application/javascript)
    │   └── index-*.css     (MIME: text/css)
    └── vite.svg
```

## Проверка

После исправления:
1. Пересоберите frontend: `cd frontend && npm run build`
2. Скопируйте файлы: `cp -r frontend/dist/* public/miniapp/`
3. Проверьте `public/miniapp/index.html` - пути должны быть `/miniapp/assets/...`
4. Откройте в браузере: `https://crm.neeklo.ru/pirogi`

## Ссылки для доступа

- **Каталог:** https://crm.neeklo.ru/pirogi
- **Товар:** https://crm.neeklo.ru/pirogi/product/{id}
- **Корзина:** https://crm.neeklo.ru/pirogi/cart
- **Оформление:** https://crm.neeklo.ru/pirogi/checkout

## Для Telegram бота

В @BotFather укажите:
```
Web App URL: https://crm.neeklo.ru/pirogi
```
