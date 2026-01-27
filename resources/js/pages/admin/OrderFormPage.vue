<template>
  <div class="order-form-page">
    <div class="mb-6">
      <router-link
        to="/admin/orders"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку заказов
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование заказа' : 'Создание заказа' }}
      </h1>
      <p class="text-gray-600 mt-1">
        {{ isEditMode ? 'Измените информацию о заказе' : 'Заполните информацию о новом заказе' }}
      </p>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Информация о клиенте -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о клиенте</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">
              Имя клиента <span class="text-red-500">*</span>
            </label>
            <input
              id="customer_name"
              v-model="form.customer_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.customer_name }"
              placeholder="Введите имя клиента"
            />
            <p v-if="errors.customer_name" class="mt-1 text-sm text-red-600">{{ errors.customer_name }}</p>
          </div>

          <div>
            <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              id="customer_email"
              v-model="form.customer_email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="email@example.com"
            />
          </div>

          <div>
            <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">
              Телефон
            </label>
            <input
              id="customer_phone"
              v-model="form.customer_phone"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="+7 (999) 123-45-67"
            />
          </div>

          <div>
            <label for="order_date" class="block text-sm font-medium text-gray-700 mb-1">
              Дата заказа
            </label>
            <input
              id="order_date"
              v-model="form.order_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <div class="mt-4">
          <label for="customer_address" class="block text-sm font-medium text-gray-700 mb-1">
            Адрес клиента
          </label>
          <textarea
            id="customer_address"
            v-model="form.customer_address"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Введите адрес клиента"
          ></textarea>
        </div>
      </div>

      <!-- Информация о заказе -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о заказе</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="order_number" class="block text-sm font-medium text-gray-700 mb-1">
              Номер заказа
            </label>
            <input
              id="order_number"
              v-model="orderNumberDisplay"
              type="text"
              disabled
              class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
            />
            <p class="mt-1 text-xs text-gray-500">Генерируется автоматически</p>
          </div>

          <div>
            <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-1">
              Сумма заказа <span class="text-red-500">*</span>
            </label>
            <input
              id="total_amount"
              v-model.number="form.total_amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.total_amount }"
              placeholder="0.00"
            />
            <p v-if="errors.total_amount" class="mt-1 text-sm text-red-600">{{ errors.total_amount }}</p>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
              Статус заказа
            </label>
            <select
              id="status"
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="pending">Ожидает</option>
              <option value="processing">В обработке</option>
              <option value="completed">Завершен</option>
              <option value="cancelled">Отменен</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
            Примечания
          </label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Дополнительная информация о заказе"
          ></textarea>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/admin/orders"
          class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Отмена
        </router-link>
        <button
          type="submit"
          :disabled="submitting"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <div v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          {{ isEditMode ? 'Сохранить' : 'Создать' }}
        </button>
      </div>
    </form>

    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-red-800">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/axios';

const router = useRouter();
const route = useRoute();
const isEditMode = computed(() => !!route.params.id);

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const errors = ref({});
const orderNumberDisplay = ref('');

const form = ref({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  customer_address: '',
  notes: '',
  total_amount: 0,
  status: 'pending',
  order_date: '',
});

const fetchOrder = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get(`/admin/orders/${route.params.id}`);
    const order = response.data;
    
    form.value = {
      customer_name: order.customer_name || '',
      customer_email: order.customer_email || '',
      customer_phone: order.customer_phone || '',
      customer_address: order.customer_address || '',
      notes: order.notes || '',
      total_amount: order.total_amount || 0,
      status: order.status || 'pending',
      order_date: order.order_date ? order.order_date.split('T')[0] : '',
    };

    orderNumberDisplay.value = order.order_number || '';
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить заказ';
    console.error('Error fetching order:', err);
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;
  errors.value = {};

  try {
    const data = { ...form.value };
    
    // Если дата не указана, не отправляем её
    if (!data.order_date) {
      delete data.order_date;
    }

    if (isEditMode.value) {
      await apiClient.put(`/admin/orders/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/orders', data);
    }

    router.push('/admin/orders');
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Произошла ошибка при сохранении';
    }
    console.error('Error submitting form:', err);
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  if (isEditMode.value) {
    fetchOrder();
  } else {
    orderNumberDisplay.value = 'Будет сгенерирован автоматически';
  }
});
</script>
