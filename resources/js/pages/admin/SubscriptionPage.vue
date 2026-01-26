<template>
  <div class="subscription-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Информация о подписке</h1>
      <p class="text-gray-600 mt-1">Управление подпиской и доступом к системе</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-red-800">{{ error }}</p>
      </div>
    </div>

    <div v-else class="space-y-6">
      <!-- Таблица с информацией о подписке -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase">
              <tr>
                <th class="px-4 py-3 font-medium">Домен</th>
                <th class="px-4 py-3 font-medium">Логин</th>
                <th class="px-4 py-3 font-medium">План</th>
                <th class="px-4 py-3 font-medium">Начало</th>
                <th class="px-4 py-3 font-medium">Конец</th>
                <th class="px-4 py-3 font-medium">Активность</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr class="bg-white hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 font-medium text-gray-900">
                  {{ subscription?.domain || 'Не указан' }}
                </td>
                <td class="px-4 py-3 text-gray-900">
                  {{ subscription?.login || '—' }}
                </td>
                <td class="px-4 py-3 text-gray-900">
                  {{ subscription?.plan?.name || subscription?.plan || '—' }}
                </td>
                <td class="px-4 py-3 text-gray-600">
                  {{ formatDate(subscription?.subscription_start) }}
                </td>
                <td class="px-4 py-3 text-gray-600">
                  {{ formatDate(subscription?.subscription_end || subscription?.expires_at) }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                      subscription?.is_active
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-700',
                    ]"
                  >
                    {{ subscription?.is_active ? 'Активен' : 'Неактивен' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Дополнительная информация -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Дополнительная информация</h2>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-gray-600">Статус подписки:</span>
            <span
              class="px-3 py-1 rounded-full text-sm font-medium"
              :class="statusClass"
            >
              {{ statusText }}
            </span>
          </div>

          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-gray-600">API токен:</span>
            <span class="font-mono text-sm text-gray-900">
              {{ subscription?.api_token || 'Не установлен' }}
            </span>
          </div>

          <div v-if="subscription?.subscription_end || subscription?.expires_at" class="flex items-center justify-between py-2">
            <span class="text-gray-600">Дата окончания:</span>
            <span class="font-medium text-gray-900">
              {{ formatDate(subscription?.subscription_end || subscription?.expires_at) }}
              <span v-if="isExpiringSoon" class="ml-2 text-orange-600 font-medium">
                (истекает скоро)
              </span>
            </span>
          </div>
        </div>
      </div>

      <!-- Действия -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">Управление подпиской</h3>
        <p class="text-sm text-blue-800 mb-4">
          Для изменения подписки или получения дополнительной информации обратитесь к администратору системы.
        </p>
        <button
          @click="refreshSubscription"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Обновить информацию
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';

const subscription = ref({
  status: 'pending',
  api_token: null,
  expires_at: null,
  subscription_start: null,
  subscription_end: null,
  domain: null,
  login: null,
  plan: null,
  is_active: false,
});

const loading = ref(false);
const error = ref(null);

const statusText = computed(() => {
  if (!subscription.value || !subscription.value.status) {
    return 'Неизвестно';
  }
  const status = subscription.value.status;
  const statusMap = {
    active: 'Активна',
    pending: 'Ожидает активации',
    expired: 'Истекла',
    cancelled: 'Отменена',
  };
  return statusMap[status] || 'Неизвестно';
});

const statusClass = computed(() => {
  if (!subscription.value || !subscription.value.status) {
    return 'bg-gray-100 text-gray-800';
  }
  const status = subscription.value.status;
  const isActive = subscription.value.is_active;
  
  if (isActive && status === 'active') {
    return 'bg-green-100 text-green-800';
  } else if (status === 'pending') {
    return 'bg-yellow-100 text-yellow-800';
  } else if (status === 'expired' || status === 'cancelled') {
    return 'bg-red-100 text-red-800';
  }
  return 'bg-gray-100 text-gray-800';
});

const expiresAtText = computed(() => {
  const endDate = subscription.value?.subscription_end || subscription.value?.expires_at;
  if (!endDate) {
    return 'Не ограничен';
  }
  return formatDate(endDate);
});

const isExpiringSoon = computed(() => {
  const endDate = subscription.value?.subscription_end || subscription.value?.expires_at;
  if (!endDate) return false;
  const expiresAt = new Date(endDate);
  const now = new Date();
  const daysUntilExpiry = Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24));
  return daysUntilExpiry > 0 && daysUntilExpiry <= 30;
});

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const fetchSubscription = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get('/admin/subscription');
    // Проверяем, что данные получены
    if (response.data && response.data.subscription) {
      subscription.value = response.data.subscription;
    } else {
      // Если данных нет, используем значения по умолчанию
      subscription.value = {
        status: 'pending',
        api_token: null,
        expires_at: null,
        subscription_start: null,
        subscription_end: null,
        domain: null,
        login: null,
        plan: null,
        is_active: false,
      };
      error.value = 'Информация о подписке не найдена';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить информацию о подписке';
    console.error('Error fetching subscription:', err);
    // Устанавливаем значения по умолчанию при ошибке
    subscription.value = {
      status: 'pending',
      api_token: null,
      expires_at: null,
      subscription_start: null,
      subscription_end: null,
      domain: null,
      login: null,
      plan: null,
      is_active: false,
    };
  } finally {
    loading.value = false;
  }
};

const refreshSubscription = () => {
  fetchSubscription();
};

onMounted(() => {
  fetchSubscription();
});
</script>
