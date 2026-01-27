import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../pages/HomePage.vue'),
  },
  // Публичные роуты авторизации
  {
    path: '/admin/login',
    name: 'admin.login',
    component: () => import('../pages/admin/auth/LoginPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/admin/register',
    name: 'admin.register',
    component: () => import('../pages/admin/auth/RegisterPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/admin/forgot-password',
    name: 'admin.forgot-password',
    component: () => import('../pages/admin/auth/ForgotPasswordPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/admin/reset-password',
    name: 'admin.reset-password',
    component: () => import('../pages/admin/auth/ResetPasswordPage.vue'),
    meta: { requiresGuest: true },
  },
  // Защищенные роуты админ-панели
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: () => import('../pages/admin/DashboardPage.vue'),
        meta: { title: 'Панель управления' },
      },
      {
        path: 'shops',
        name: 'admin.shops.index',
        component: () => import('../pages/admin/ShopsPage.vue'),
        meta: { title: 'Магазины' },
      },
      {
        path: 'shops/create',
        name: 'admin.shops.create',
        component: () => import('../pages/admin/ShopFormPage.vue'),
        meta: { title: 'Создание магазина' },
      },
      {
        path: 'shops/:id/edit',
        name: 'admin.shops.edit',
        component: () => import('../pages/admin/ShopFormPage.vue'),
        meta: { title: 'Редактирование магазина' },
      },
      {
        path: 'subscription',
        name: 'admin.subscription',
        component: () => import('../pages/admin/SubscriptionPage.vue'),
        meta: { title: 'Подписка' },
      },
      {
        path: 'media',
        name: 'admin.media',
        component: () => import('../pages/admin/MediaPage.vue'),
        meta: { title: 'Медиа' },
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: () => import('../pages/admin/SettingsPage.vue'),
        meta: { title: 'Настройки' },
      },
      // Каталог
      {
        path: 'categories',
        name: 'admin.categories.index',
        component: () => import('../pages/admin/CategoriesPage.vue'),
        meta: { title: 'Категории' },
      },
      {
        path: 'categories/create',
        name: 'admin.categories.create',
        component: () => import('../pages/admin/CategoryFormPage.vue'),
        meta: { title: 'Создание категории' },
      },
      {
        path: 'categories/:id/edit',
        name: 'admin.categories.edit',
        component: () => import('../pages/admin/CategoryFormPage.vue'),
        meta: { title: 'Редактирование категории' },
      },
      {
        path: 'products',
        name: 'admin.products.index',
        component: () => import('../pages/admin/ProductsPage.vue'),
        meta: { title: 'Товары' },
      },
      {
        path: 'products/create',
        name: 'admin.products.create',
        component: () => import('../pages/admin/ProductFormPage.vue'),
        meta: { title: 'Создание товара' },
      },
      {
        path: 'products/:id/edit',
        name: 'admin.products.edit',
        component: () => import('../pages/admin/ProductFormPage.vue'),
        meta: { title: 'Редактирование товара' },
      },
      {
        path: 'units',
        name: 'admin.units.index',
        component: () => import('../pages/admin/UnitsPage.vue'),
        meta: { title: 'Единицы измерения' },
      },
      {
        path: 'units/create',
        name: 'admin.units.create',
        component: () => import('../pages/admin/UnitFormPage.vue'),
        meta: { title: 'Создание единицы измерения' },
      },
      {
        path: 'units/:id/edit',
        name: 'admin.units.edit',
        component: () => import('../pages/admin/UnitFormPage.vue'),
        meta: { title: 'Редактирование единицы измерения' },
      },
      // Заказы, Доставки, Платежи
      {
        path: 'orders',
        name: 'admin.orders.index',
        component: () => import('../pages/admin/OrdersPage.vue'),
        meta: { title: 'Заказы' },
      },
      {
        path: 'orders/create',
        name: 'admin.orders.create',
        component: () => import('../pages/admin/OrderFormPage.vue'),
        meta: { title: 'Создание заказа' },
      },
      {
        path: 'orders/:id/edit',
        name: 'admin.orders.edit',
        component: () => import('../pages/admin/OrderFormPage.vue'),
        meta: { title: 'Редактирование заказа' },
      },
      {
        path: 'deliveries',
        name: 'admin.deliveries.index',
        component: () => import('../pages/admin/DeliveriesPage.vue'),
        meta: { title: 'Доставки' },
      },
      {
        path: 'deliveries/create',
        name: 'admin.deliveries.create',
        component: () => import('../pages/admin/DeliveryFormPage.vue'),
        meta: { title: 'Создание доставки' },
      },
      {
        path: 'deliveries/:id/edit',
        name: 'admin.deliveries.edit',
        component: () => import('../pages/admin/DeliveryFormPage.vue'),
        meta: { title: 'Редактирование доставки' },
      },
      {
        path: 'payments',
        name: 'admin.payments.index',
        component: () => import('../pages/admin/PaymentsPage.vue'),
        meta: { title: 'Платежи' },
      },
      {
        path: 'payments/create',
        name: 'admin.payments.create',
        component: () => import('../pages/admin/PaymentFormPage.vue'),
        meta: { title: 'Создание платежа' },
      },
      {
        path: 'payments/:id/edit',
        name: 'admin.payments.edit',
        component: () => import('../pages/admin/PaymentFormPage.vue'),
        meta: { title: 'Редактирование платежа' },
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Очищаем ошибки при переходе между страницами авторизации
  const authRoutes = ['admin.login', 'admin.register', 'admin.forgot-password', 'admin.reset-password'];
  if (authRoutes.includes(to.name) || authRoutes.includes(from.name)) {
    authStore.clearError();
  }

  // Если требуется авторизация
  if (to.meta.requiresAuth) {
    // Проверяем, авторизован ли пользователь
    if (!authStore.isAuthenticated) {
      // Пытаемся получить пользователя
      const result = await authStore.fetchUser();
      if (!result.success) {
        return next({ name: 'admin.login', query: { redirect: to.fullPath } });
      }
    }

    // Проверяем доступ администратора/менеджера
    if (to.meta.requiresAdmin) {
      if (!authStore.isAdmin && !authStore.isManager && !authStore.isDeveloper) {
        return next({ name: 'admin.login' });
      }
    }
  }

  // Если требуется гость (для страниц авторизации)
  if (to.meta.requiresGuest) {
    if (authStore.isAuthenticated) {
      return next({ name: 'admin.dashboard' });
    }
  }

  next();
});

export default router;
