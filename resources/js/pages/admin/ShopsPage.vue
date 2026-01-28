<template>
  <div class="shops-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Магазины</h1>
        <p class="text-gray-600 mt-1">Управление магазинами</p>
      </div>
      <router-link
        to="/admin/shops/create"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Создать магазин
      </router-link>
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

    <div v-else-if="shops.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
      </svg>
      <p class="text-gray-600 mb-4">Магазины не найдены</p>
      <router-link
        to="/admin/shops/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Создать первый магазин
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Название
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ИНН
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ОГРН
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Администратор
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Телеграм бот
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Шаблон
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="shop in shops" :key="shop.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ shop.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ shop.inn || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ shop.ogrn || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ shop.admin?.name || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-if="shop.telegram_bot_token"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  Подключен
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  Не подключен
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="{
                    'bg-amber-100 text-amber-800': shop.template === 'amber',
                    'bg-blue-100 text-blue-800': shop.template === 'blue',
                    'bg-green-100 text-green-800': shop.template === 'green',
                    'bg-purple-100 text-purple-800': shop.template === 'purple',
                    'bg-gray-100 text-gray-800': shop.template === 'monochrome' || !shop.template
                  }"
                >
                  {{ getTemplateName(shop.template) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/admin/shops/${shop.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                    title="Редактировать"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </router-link>
                  <button
                    @click="confirmDelete(shop)"
                    class="text-red-600 hover:text-red-900"
                    title="Удалить"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div
      v-if="shopToDelete"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="shopToDelete = null"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Подтверждение удаления</h3>
        <p class="text-gray-600 mb-6">
          Вы уверены, что хотите удалить магазин "{{ shopToDelete.name }}"? Это действие нельзя отменить.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="shopToDelete = null"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Отмена
          </button>
          <button
            @click="deleteShop"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/axios';

const router = useRouter();
const shops = ref([]);
const loading = ref(false);
const error = ref(null);
const shopToDelete = ref(null);

const fetchShops = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get('/admin/shops');
    shops.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить список магазинов';
    console.error('Error fetching shops:', err);
  } finally {
    loading.value = false;
  }
};

const confirmDelete = (shop) => {
  shopToDelete.value = shop;
};

const deleteShop = async () => {
  if (!shopToDelete.value) return;

  try {
    await apiClient.delete(`/admin/shops/${shopToDelete.value.id}`);
    shops.value = shops.value.filter(s => s.id !== shopToDelete.value.id);
    shopToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось удалить магазин';
    console.error('Error deleting shop:', err);
  }
};

const getTemplateName = (template) => {
  const templates = {
    'amber': 'Янтарный',
    'blue': 'Синий',
    'green': 'Зеленый',
    'purple': 'Фиолетовый',
    'monochrome': 'Черно-белый'
  };
  return templates[template] || 'Янтарный';
};

const getTemplateName = (template) => {
  const templates = {
    'amber': 'Янтарный',
    'blue': 'Синий',
    'green': 'Зеленый',
    'purple': 'Фиолетовый',
    'monochrome': 'Черно-белый'
  };
  return templates[template] || 'Янтарный';
};

onMounted(() => {
  fetchShops();
});
</script>
