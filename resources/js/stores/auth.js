import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '../api/axios';
import router from '../router';

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null);
  const loading = ref(false);
  const error = ref(null);

  // Getters
  const isAuthenticated = computed(() => !!user.value);
  const isAdmin = computed(() => user.value?.role?.level === 3);
  const isManager = computed(() => user.value?.role?.level === 2);
  const isDeveloper = computed(() => user.value?.role?.level === 4);
  const userShops = computed(() => {
    if (!user.value) return [];
    // Администратор видит свои магазины, менеджер - назначенные
    if (isAdmin.value) {
      return user.value.owned_shops || user.value.ownedShops || [];
    }
    if (isManager.value) {
      return user.value.shops || [];
    }
    return [];
  });

  // Actions
  /**
   * Извлечь сообщение об ошибке валидации из ответа
   */
  const extractValidationError = (err) => {
    const response = err.response;
    if (!response) {
      return 'Произошла ошибка при выполнении запроса';
    }

    const data = response.data;

    // Если есть ошибки валидации (422)
    if (response.status === 422 && data.errors) {
      // Приоритет: сначала проверяем admin_api (ошибка интеграции с ADMIN)
      if (data.errors.admin_api && Array.isArray(data.errors.admin_api) && data.errors.admin_api.length > 0) {
        return data.errors.admin_api[0];
      }
      // Берем первое сообщение об ошибке
      const firstError = Object.values(data.errors)[0];
      if (Array.isArray(firstError) && firstError.length > 0) {
        return firstError[0];
      }
      return firstError || 'Ошибка валидации';
    }

    // Если есть общее сообщение
    if (data.message) {
      return data.message;
    }

    // Стандартные сообщения по статусам
    if (response.status === 401) {
      return 'Неверный email или пароль';
    }

    if (response.status === 422) {
      return 'Ошибка валидации данных';
    }

    return 'Произошла ошибка при выполнении запроса';
  };

  /**
   * Получить CSRF cookie
   */
  const fetchCsrfCookie = async () => {
    try {
      await apiClient.get('/auth/csrf-cookie');
    } catch (err) {
      console.error('Failed to fetch CSRF cookie:', err);
    }
  };

  /**
   * Авторизация
   */
  const login = async (credentials) => {
    loading.value = true;
    error.value = null;

    try {
      // Получаем CSRF cookie перед запросом
      await fetchCsrfCookie();

      const response = await apiClient.post('/auth/login', credentials);
      user.value = response.data.user;

      return { success: true };
    } catch (err) {
      error.value = extractValidationError(err);
      return { success: false, error: error.value };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Регистрация
   */
  const register = async (userData) => {
    loading.value = true;
    error.value = null;
    // Очищаем пользователя на случай предыдущей неудачной попытки
    user.value = null;

    try {
      // Получаем CSRF cookie перед запросом
      await fetchCsrfCookie();

      const response = await apiClient.post('/auth/register', userData);
      user.value = response.data.user;

      return { success: true };
    } catch (err) {
      // Очищаем пользователя при ошибке (на случай если он был установлен)
      user.value = null;
      
      const response = err.response;
      const errorMessage = extractValidationError(err);
      
      // Если сервер вернул детали ошибки (например, при ошибке ADMIN)
      const errorDetails = response?.data?.details || null;
      
      return { 
        success: false, 
        error: errorMessage,
        details: errorDetails,
      };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Выход
   */
  const logout = async () => {
    loading.value = true;
    error.value = null;

    try {
      await apiClient.post('/logout');
      user.value = null;
      router.push('/admin/login');
    } catch (err) {
      console.error('Logout error:', err);
      // Даже при ошибке выхода очищаем состояние пользователя
      user.value = null;
      router.push('/admin/login');
    } finally {
      loading.value = false;
    }
  };

  /**
   * Получить текущего пользователя
   */
  const fetchUser = async () => {
    loading.value = true;
    error.value = null;

    try {
      const response = await apiClient.get('/user');
      user.value = response.data.user;
      return { success: true };
    } catch (err) {
      user.value = null;
      return { success: false };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Восстановление пароля
   */
  const forgotPassword = async (email) => {
    loading.value = true;
    error.value = null;

    try {
      await fetchCsrfCookie();
      await apiClient.post('/auth/forgot-password', { email });
      return { success: true };
    } catch (err) {
      error.value = extractValidationError(err);
      return { success: false, error: error.value };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Сброс пароля
   */
  const resetPassword = async (data) => {
    loading.value = true;
    error.value = null;

    try {
      await fetchCsrfCookie();
      await apiClient.post('/auth/reset-password', data);
      return { success: true };
    } catch (err) {
      error.value = extractValidationError(err);
      return { success: false, error: error.value };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Проверка доступа к магазину
   */
  const hasAccessToShop = (shopId) => {
    if (!user.value) return false;
    if (isDeveloper.value) return true;
    if (isAdmin.value) {
      return userShops.value.some(shop => shop.id === shopId);
    }
    if (isManager.value) {
      return userShops.value.some(shop => shop.id === shopId);
    }
    return false;
  };

  /**
   * Очистить ошибки
   */
  const clearError = () => {
    error.value = null;
  };

  return {
    // State
    user,
    loading,
    error,
    // Getters
    isAuthenticated,
    isAdmin,
    isManager,
    isDeveloper,
    userShops,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    forgotPassword,
    resetPassword,
    hasAccessToShop,
    clearError,
  };
});
