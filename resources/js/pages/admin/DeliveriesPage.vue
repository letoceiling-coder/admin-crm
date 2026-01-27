<template>
  <div class="deliveries-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Доставки</h1>
        <p class="text-gray-600 mt-1">Управление доставками</p>
      </div>
      <router-link
        to="/admin/deliveries/create"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Создать доставку
      </router-link>
    </div>

    <!-- Фильтры и поиск -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Поиск -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Номер, имя, адрес..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="debounceSearch"
          />
        </div>

        <!-- Фильтр по статусу -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchDeliveries"
          >
            <option value="">Все</option>
            <option value="pending">Ожидает</option>
            <option value="in_transit">В пути</option>
            <option value="delivered">Доставлено</option>
            <option value="cancelled">Отменено</option>
          </select>
        </div>

        <!-- Сортировка -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сортировка</label>
          <select
            v-model="filters.sort_by"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchDeliveries"
          >
            <option value="created_at">По дате создания</option>
            <option value="delivery_number">По номеру</option>
            <option value="recipient_name">По имени получателя</option>
            <option value="delivery_date">По дате доставки</option>
            <option value="status">По статусу</option>
          </select>
        </div>

        <!-- Порядок сортировки -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Порядок</label>
          <select
            v-model="filters.sort_order"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchDeliveries"
          >
            <option value="desc">По убыванию</option>
            <option value="asc">По возрастанию</option>
          </select>
        </div>

        <!-- Количество на странице -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
          <select
            v-model="filters.per_page"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchDeliveries"
          >
            <option :value="20">20</option>
            <option :value="30">30</option>
            <option :value="40">40</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
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

    <div v-else-if="deliveries.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
      </svg>
      <p class="text-gray-600 mb-4">Доставки не найдены</p>
      <router-link
        to="/admin/deliveries/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Создать первую доставку
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Номер доставки
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Получатель
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Адрес
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Статус
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Дата доставки
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="delivery in deliveries" :key="delivery.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ delivery.delivery_number }}</div>
                <div v-if="delivery.order" class="text-xs text-gray-500">Заказ: {{ delivery.order.order_number }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ delivery.recipient_name }}</div>
                <div class="text-sm text-gray-500">{{ delivery.recipient_phone }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-xs truncate">{{ delivery.delivery_address }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(delivery.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(delivery.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ formatDate(delivery.delivery_date || delivery.created_at) }}</div>
                <div v-if="delivery.delivered_at" class="text-xs text-gray-400">
                  Доставлено: {{ formatDate(delivery.delivered_at) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/admin/deliveries/${delivery.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                    title="Редактировать"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </router-link>
                  <button
                    @click="confirmDelete(delivery)"
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

      <!-- Пагинация -->
      <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Назад
          </button>
          <span class="px-3 py-2 text-sm text-gray-700">
            Страница {{ pagination.current_page }} из {{ pagination.last_page }}
          </span>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Вперед
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div
      v-if="deliveryToDelete"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="deliveryToDelete = null"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Подтверждение удаления</h3>
        <p class="text-gray-600 mb-6">
          Вы уверены, что хотите удалить доставку "{{ deliveryToDelete.delivery_number }}"? Это действие нельзя отменить.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="deliveryToDelete = null"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Отмена
          </button>
          <button
            @click="deleteDelivery"
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
import { ref, onMounted, watch } from 'vue';
import apiClient from '@/api/axios';
import { useShopStore } from '@/stores/shop';

const shopStore = useShopStore();
const deliveries = ref([]);
const loading = ref(false);
const error = ref(null);
const deliveryToDelete = ref(null);
const pagination = ref(null);
const searchTimeout = ref(null);
const filters = ref({
  search: '',
  status: '',
  sort_by: 'created_at',
  sort_order: 'desc',
  per_page: 20,
});

const fetchDeliveries = async (page = 1) => {
  loading.value = true;
  error.value = null;

  try {
    const params = {
      page,
      per_page: filters.value.per_page,
      sort_by: filters.value.sort_by,
      sort_order: filters.value.sort_order,
    };

    if (filters.value.search) {
      params.search = filters.value.search;
    }

    if (filters.value.status) {
      params.status = filters.value.status;
    }

    if (shopStore.selectedShopId) {
      params.shop_id = shopStore.selectedShopId;
    }

    const response = await apiClient.get('/admin/deliveries', { params });
    deliveries.value = response.data.data || response.data;
    
    if (response.data.meta) {
      pagination.value = {
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        from: response.data.meta.from,
        to: response.data.meta.to,
        total: response.data.meta.total,
      };
    } else {
      pagination.value = null;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить список доставок';
    console.error('Error fetching deliveries:', err);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    fetchDeliveries(1);
  }, 500);
};

const changePage = (page) => {
  if (page >= 1 && (!pagination.value || page <= pagination.value.last_page)) {
    fetchDeliveries(page);
  }
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    in_transit: 'В пути',
    delivered: 'Доставлено',
    cancelled: 'Отменено',
  };
  return labels[status] || status;
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_transit: 'bg-blue-100 text-blue-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ru-RU');
};

const confirmDelete = (delivery) => {
  deliveryToDelete.value = delivery;
};

const deleteDelivery = async () => {
  if (!deliveryToDelete.value) return;

  try {
    await apiClient.delete(`/admin/deliveries/${deliveryToDelete.value.id}`);
    deliveries.value = deliveries.value.filter(d => d.id !== deliveryToDelete.value.id);
    deliveryToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось удалить доставку';
    console.error('Error deleting delivery:', err);
  }
};

onMounted(() => {
  fetchDeliveries();
});

// Реактивность на изменение выбранного магазина
watch(() => shopStore.selectedShopId, () => {
  fetchDeliveries(1);
});
</script>
