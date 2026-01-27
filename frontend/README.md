# Telegram Mini App - Frontend

React приложение для Telegram Mini App с интеграцией CRM системы.

## Структура проекта

```
frontend/
├── src/
│   ├── components/          # Переиспользуемые компоненты
│   │   ├── ProductCard.tsx
│   │   ├── MiniAppHeader.tsx
│   │   ├── BottomNavigation.tsx
│   │   ├── CategoryTabs.tsx
│   │   └── ui/              # UI компоненты (tabs, dialog и т.д.)
│   ├── pages/               # Страницы приложения
│   │   ├── CatalogPage.tsx
│   │   ├── ProductDetailPage.tsx
│   │   ├── CartPage.tsx
│   │   └── CheckoutPage.tsx
│   ├── services/            # API сервисы
│   │   └── api.ts
│   ├── store/               # Zustand stores
│   │   └── cartStore.ts
│   ├── types/               # TypeScript типы
│   │   └── index.ts
│   ├── lib/                 # Утилиты
│   │   └── utils.ts
│   ├── App.tsx
│   └── main.tsx
├── package.json
├── vite.config.ts
└── tailwind.config.js
```

## Установка и запуск

1. Установите зависимости:
```bash
npm install
```

2. Создайте файл `.env` на основе `.env.example`:
```bash
cp .env.example .env
```

3. Запустите dev сервер:
```bash
npm run dev
```

4. Соберите для production:
```bash
npm run build
```

## Роутинг

Приложение использует роутинг по названию магазина:
- `/:shopSlug` - каталог товаров магазина
- `/:shopSlug/product/:productId` - детальная страница товара
- `/:shopSlug/cart` - корзина
- `/:shopSlug/checkout` - оформление заказа

Пример: `https://crm.neeklo.ru/мой-магазин`

## API интеграция

Приложение работает с публичными API endpoints:
- `GET /api/shops/slug/{slug}` - получение магазина по slug
- `GET /api/shops/{shopId}/categories` - категории магазина
- `GET /api/shops/{shopId}/products` - товары магазина
- `GET /api/products/{id}` - детальная информация о товаре

## Telegram Mini App

Приложение интегрировано с Telegram Web App SDK:
- Автоматическое определение цветовой схемы
- Получение данных пользователя из Telegram
- Готовность к использованию в Telegram боте

## Дизайн

Дизайн основан на стиле сайта pirogi.ru с использованием:
- Градиентов (amber, orange, red)
- Анимаций (framer-motion)
- Адаптивной верстки
- Темной темы

## Технологии

- React 19
- TypeScript
- Vite
- React Router
- Zustand (state management)
- TanStack Query (data fetching)
- Framer Motion (анимации)
- Tailwind CSS
- Telegram Web App SDK
