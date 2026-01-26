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
      <!-- Статус подписки -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Статус подписки</h2>
          <span
            class="px-3 py-1 rounded-full text-sm font-medium"
            :class="statusClass"
          >
            {{ statusText }}
          </span>
        </div>

        <div class="space-y-4">
          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-gray-600">Домен:</span>
            <span class="font-medium text-gray-900">{{ subscription?.domain || 'Не указан' }}</span>
          </div>

          <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-gray-600">API токен:</span>
            <span class="font-mono text-sm text-gray-900">
              {{ subscription?.api_token || 'Не установлен' }}
            </span>
          </div>

          <div class="flex items-center justify-between py-2">
            <span class="text-gray-600">Дата окончания:</span>
            <span class="font-medium text-gray-900">
              {{ expiresAtText }}
            </span>
          </div>
        </div>
      </div>

      <!-- Информация о подписке -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Детали подписки</h2>
        
        <div class="space-y-3">
          <div class="flex items-start">
            <svg class="h-5 w-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
              <p class="text-sm font-medium text-gray-900">Статус подписки</p>
              <p class="text-sm text-gray-600 mt-1">
                {{ subscription?.is_active ? 'Подписка активна' : 'Подписка неактивна или истекла' }}
              </p>
            </div>
          </div>

          <div v-if="subscription?.domain" class="flex items-start">
            <svg class="h-5 w-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
            </svg>
            <div>
              <p class="text-sm font-medium text-gray-900">Домен системы</p>
              <p class="text-sm text-gray-600 mt-1">{{ subscription.domain }}</p>
            </div>
          </div>

          <div v-if="subscription?.expires_at" class="flex items-start">
            <svg class="h-5 w-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <div>
              <p class="text-sm font-medium text-gray-900">Срок действия</p>
              <p class="text-sm text-gray-600 mt-1">
                {{ formatDate(subscription?.expires_at) }}
                <span v-if="isExpiringSoon" class="ml-2 text-orange-600 font-medium">
                  (истекает скоро)
                </span>
              </p>
            </div>
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
  domain: null,
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
  if (!subscription.value || !subscription.value.expires_at) {
    return 'Не ограничен';
  }
  return formatDate(subscription.value.expires_at);
});

const isExpiringSoon = computed(() => {
  if (!subscription.value || !subscription.value.expires_at) return false;
  const expiresAt = new Date(subscription.value.expires_at);
  const now = new Date();
  const daysUntilExpiry = Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24));
  return daysUntilExpiry > 0 && daysUntilExpiry <= 30;
});

const formatDate = (dateString) => {
  if (!dateString) return 'Не указано';
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
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
        domain: null,
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
      domain: null,
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
