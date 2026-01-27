<template>
  <div class="payments-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Платежи</h1>
        <p class="text-gray-600 mt-1">Управление платежами</p>
      </div>
      <router-link
        to="/admin/payments/create"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Создать платеж
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
            placeholder="Номер, имя, email..."
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
            @change="fetchPayments"
          >
            <option value="">Все</option>
            <option value="pending">Ожидает</option>
            <option value="processing">В обработке</option>
            <option value="completed">Завершен</option>
            <option value="failed">Неудачный</option>
            <option value="refunded">Возвращен</option>
          </select>
        </div>

        <!-- Фильтр по способу оплаты -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Способ оплаты</label>
          <select
            v-model="filters.payment_method"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchPayments"
          >
            <option value="">Все</option>
            <option value="cash">Наличные</option>
            <option value="card">Карта</option>
            <option value="bank_transfer">Банковский перевод</option>
            <option value="online">Онлайн</option>
            <option value="other">Другое</option>
          </select>
        </div>

        <!-- Сортировка -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сортировка</label>
          <select
            v-model="filters.sort_by"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchPayments"
          >
            <option value="created_at">По дате создания</option>
            <option value="payment_number">По номеру</option>
            <option value="payer_name">По имени плательщика</option>
            <option value="amount">По сумме</option>
            <option value="status">По статусу</option>
          </select>
        </div>

        <!-- Количество на странице -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
          <select
            v-model="filters.per_page"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchPayments"
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

    <div v-else-if="payments.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
      </svg>
      <p class="text-gray-600 mb-4">Платежи не найдены</p>
      <router-link
        to="/admin/payments/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Создать первый платеж
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Номер платежа
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Плательщик
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Сумма
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Способ оплаты
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Статус
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Дата платежа
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ payment.payment_number }}</div>
                <div v-if="payment.order" class="text-xs text-gray-500">Заказ: {{ payment.order.order_number }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ payment.payer_name }}</div>
                <div class="text-sm text-gray-500">{{ payment.payer_email || payment.payer_phone || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(payment.amount) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ getPaymentMethodLabel(payment.payment_method) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(payment.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(payment.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ formatDate(payment.payment_date || payment.created_at) }}</div>
                <div v-if="payment.paid_at" class="text-xs text-gray-400">
                  Оплачено: {{ formatDate(payment.paid_at) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/admin/payments/${payment.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                    title="Редактировать"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </router-link>
                  <button
                    @click="confirmDelete(payment)"
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
      v-if="paymentToDelete"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="paymentToDelete = null"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Подтверждение удаления</h3>
        <p class="text-gray-600 mb-6">
          Вы уверены, что хотите удалить платеж "{{ paymentToDelete.payment_number }}"? Это действие нельзя отменить.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="paymentToDelete = null"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Отмена
          </button>
          <button
            @click="deletePayment"
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
const payments = ref([]);
const loading = ref(false);
const error = ref(null);
const paymentToDelete = ref(null);
const pagination = ref(null);
const searchTimeout = ref(null);
const filters = ref({
  search: '',
  status: '',
  payment_method: '',
  sort_by: 'created_at',
  sort_order: 'desc',
  per_page: 20,
});

const fetchPayments = async (page = 1) => {
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

    if (filters.value.payment_method) {
      params.payment_method = filters.value.payment_method;
    }

    if (shopStore.selectedShopId) {
      params.shop_id = shopStore.selectedShopId;
    }

    const response = await apiClient.get('/admin/payments', { params });
    payments.value = response.data.data || response.data;
    
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
    error.value = err.response?.data?.message || 'Не удалось загрузить список платежей';
    console.error('Error fetching payments:', err);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    fetchPayments(1);
  }, 500);
};

const changePage = (page) => {
  if (page >= 1 && (!pagination.value || page <= pagination.value.last_page)) {
    fetchPayments(page);
  }
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    processing: 'В обработке',
    completed: 'Завершен',
    failed: 'Неудачный',
    refunded: 'Возвращен',
  };
  return labels[status] || status;
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getPaymentMethodLabel = (method) => {
  const labels = {
    cash: 'Наличные',
    card: 'Карта',
    bank_transfer: 'Банковский перевод',
    online: 'Онлайн',
    other: 'Другое',
  };
  return labels[method] || method;
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('ru-RU');
};

const confirmDelete = (payment) => {
  paymentToDelete.value = payment;
};

const deletePayment = async () => {
  if (!paymentToDelete.value) return;

  try {
    await apiClient.delete(`/admin/payments/${paymentToDelete.value.id}`);
    payments.value = payments.value.filter(p => p.id !== paymentToDelete.value.id);
    paymentToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось удалить платеж';
    console.error('Error deleting payment:', err);
  }
};

onMounted(() => {
  fetchPayments();
});

// Реактивность на изменение выбранного магазина
watch(() => shopStore.selectedShopId, () => {
  fetchPayments(1);
});
</script>
