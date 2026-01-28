<template>
  <div class="shop-bot-users-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Пользователи магазина</h1>
        <p class="text-gray-600 mt-1">Пользователи бота магазина (Telegram), привязаны к shop_id</p>
      </div>
      <router-link
        v-if="shopStore.selectedShop"
        to="/admin/shop-bot-users/broadcast"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
        Рассылка
      </router-link>
    </div>

    <router-link
      to="/admin/shops"
      class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
    >
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Назад к магазинам
    </router-link>

    <div v-if="!shopStore.selectedShopId" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
      <div class="flex items-center gap-2 mb-2">
        <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <p class="text-yellow-800 font-medium">Выберите магазин</p>
      </div>
      <p class="text-yellow-700 text-sm">Выберите магазин в переключателе в шапке страницы, чтобы увидеть пользователей бота.</p>
    </div>

    <div v-else-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <div v-else-if="users.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
      <p class="text-gray-600 mb-2">Пользователей бота пока нет</p>
      <p class="text-gray-500 text-sm">Они появятся, когда кто-то нажмёт /start в боте магазина «{{ shopStore.selectedShop?.name }}».</p>
      <router-link
        to="/admin/shop-bot-users/broadcast"
        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Рассылка
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">Магазин: {{ shopStore.selectedShop?.name }}</span>
        <span class="text-sm text-gray-500">Всего: {{ users.length }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Пользователь</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chat ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Начал бота</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ [u.first_name, u.last_name].filter(Boolean).join(' ') || '—' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ u.username ? `@${u.username}` : '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ u.telegram_chat_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ u.started_at ? formatDate(u.started_at) : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useShopStore } from '@/stores/shop';
import apiClient from '@/api/axios';

const shopStore = useShopStore();
const users = ref([]);
const loading = ref(false);
const error = ref(null);

function formatDate(val) {
  if (!val) return '—';
  const d = new Date(val);
  return d.toLocaleString('ru-RU');
}

async function fetchUsers() {
  if (!shopStore.selectedShopId) {
    users.value = [];
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    const res = await apiClient.get(`/admin/shops/${shopStore.selectedShopId}/bot-users`);
    users.value = res.data || [];
  } catch (e) {
    error.value = e.response?.data?.message || 'Ошибка загрузки пользователей';
    users.value = [];
  } finally {
    loading.value = false;
  }
}

watch(() => shopStore.selectedShopId, fetchUsers);
onMounted(fetchUsers);
</script>
