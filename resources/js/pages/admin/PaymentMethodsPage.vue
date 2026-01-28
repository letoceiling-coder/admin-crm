<template>
  <div class="payment-methods-list-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Способы оплаты</h1>
      <p class="text-gray-600 mt-1">Выберите способ оплаты для настройки</p>
    </div>

    <!-- Загрузка -->
    <div v-if="loading && !methods.length" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <p class="text-gray-600">Загрузка способов оплаты...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <!-- Нет магазина -->
    <div v-else-if="!shopStore.selectedShopId" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <p class="text-yellow-800">Выберите магазин для настройки способов оплаты</p>
      </div>
    </div>

    <!-- Список карточек -->
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <router-link
        v-for="method in methods"
        :key="method.id"
        :to="{ name: 'admin.payment-methods.edit', params: { code: method.payment_method_code } }"
        class="block rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-500 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <div class="flex items-start justify-between">
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-semibold text-gray-900">{{ method.name }}</h2>
            <p v-if="method.description" class="mt-1 text-sm text-gray-500 line-clamp-2">{{ method.description }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                v-if="method.is_enabled"
                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
              >
                Включён
              </span>
              <span
                v-else
                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
              >
                Выключен
              </span>
              <span
                v-if="method.is_default"
                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
              >
                По умолчанию
              </span>
            </div>
          </div>
          <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </div>
        <p class="mt-4 text-sm font-medium text-blue-600">Настройки →</p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import apiClient from '@/api/axios';
import { useShopStore } from '@/stores/shop';

const shopStore = useShopStore();
const methods = ref([]);
const loading = ref(false);
const error = ref(null);

const loadMethods = async () => {
  if (!shopStore.selectedShopId) {
    methods.value = [];
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    const response = await apiClient.get('/admin/payment-methods', {
      params: { shop_id: shopStore.selectedShopId }
    });
    methods.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error('Error loading payment methods:', err);
    if (err.response?.status !== 404) {
      error.value = err.response?.data?.message || 'Ошибка при загрузке способов оплаты';
    }
    methods.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadMethods();
});

watch(() => shopStore.selectedShopId, () => {
  loadMethods();
});
</script>
