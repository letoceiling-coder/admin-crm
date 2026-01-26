import axios from 'axios';

// Создаём глобальный экземпляр Axios
const apiClient = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Перехватчик запросов
apiClient.interceptors.request.use(
  (config) => {
    // CSRF токен добавляется автоматически через cookies
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Перехватчик ответов
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    const { response } = error;

    // Обработка 401 (Unauthorized)
    if (response?.status === 401) {
      // Перенаправление на страницу входа будет обработано в router
      console.error('Unauthorized: Authentication required');
    }

    // Обработка 419 (CSRF token mismatch)
    if (response?.status === 419) {
      // Получаем новый CSRF токен
      try {
        await axios.get('/sanctum/csrf-cookie');
        // Повторяем запрос
        return apiClient.request(error.config);
      } catch (csrfError) {
        console.error('CSRF token refresh failed:', csrfError);
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
