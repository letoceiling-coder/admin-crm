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
        path: 'subscription',
        name: 'admin.subscription',
        component: () => import('../pages/admin/SubscriptionPage.vue'),
        meta: { title: 'Подписка' },
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
