<template>
  <div class="payment-form-page">
    <div class="mb-6">
      <router-link
        to="/admin/payments"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку платежей
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование платежа' : 'Создание платежа' }}
      </h1>
      <p class="text-gray-600 mt-1">
        {{ isEditMode ? 'Измените информацию о платеже' : 'Заполните информацию о новом платеже' }}
      </p>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Информация о плательщике -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о плательщике</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="payer_name" class="block text-sm font-medium text-gray-700 mb-1">
              Имя плательщика <span class="text-red-500">*</span>
            </label>
            <input
              id="payer_name"
              v-model="form.payer_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.payer_name }"
              placeholder="Введите имя плательщика"
            />
            <p v-if="errors.payer_name" class="mt-1 text-sm text-red-600">{{ errors.payer_name }}</p>
          </div>

          <div>
            <label for="payer_email" class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              id="payer_email"
              v-model="form.payer_email"
              type="email"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="email@example.com"
            />
          </div>

          <div>
            <label for="payer_phone" class="block text-sm font-medium text-gray-700 mb-1">
              Телефон
            </label>
            <input
              id="payer_phone"
              v-model="form.payer_phone"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="+7 (999) 123-45-67"
            />
          </div>
        </div>
      </div>

      <!-- Информация о платеже -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о платеже</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="payment_number" class="block text-sm font-medium text-gray-700 mb-1">
              Номер платежа
            </label>
            <input
              id="payment_number"
              v-model="paymentNumberDisplay"
              type="text"
              disabled
              class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
            />
            <p class="mt-1 text-xs text-gray-500">Генерируется автоматически</p>
          </div>

          <div>
            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
              Сумма платежа <span class="text-red-500">*</span>
            </label>
            <input
              id="amount"
              v-model.number="form.amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.amount }"
              placeholder="0.00"
            />
            <p v-if="errors.amount" class="mt-1 text-sm text-red-600">{{ errors.amount }}</p>
          </div>

          <div>
            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">
              Способ оплаты
            </label>
            <select
              id="payment_method"
              v-model="form.payment_method"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="cash">Наличные</option>
              <option value="card">Карта</option>
              <option value="bank_transfer">Банковский перевод</option>
              <option value="online">Онлайн</option>
              <option value="other">Другое</option>
            </select>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
              Статус платежа
            </label>
            <select
              id="status"
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="pending">Ожидает</option>
              <option value="processing">В обработке</option>
              <option value="completed">Завершен</option>
              <option value="failed">Неудачный</option>
              <option value="refunded">Возвращен</option>
            </select>
          </div>

          <div>
            <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">
              Дата платежа
            </label>
            <input
              id="payment_date"
              v-model="form.payment_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div v-if="isEditMode && form.status === 'completed'">
            <label for="paid_at" class="block text-sm font-medium text-gray-700 mb-1">
              Дата фактической оплаты
            </label>
            <input
              id="paid_at"
              v-model="form.paid_at"
              type="datetime-local"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
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
            placeholder="Дополнительная информация о платеже"
          ></textarea>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/admin/payments"
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
import { useShopStore } from '@/stores/shop';

const router = useRouter();
const route = useRoute();
const shopStore = useShopStore();
const isEditMode = computed(() => !!route.params.id);

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const errors = ref({});
const paymentNumberDisplay = ref('');

const form = ref({
  order_id: null,
  payer_name: '',
  payer_email: '',
  payer_phone: '',
  amount: 0,
  payment_method: 'cash',
  status: 'pending',
  notes: '',
  payment_date: '',
  paid_at: '',
});

const fetchPayment = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get(`/admin/payments/${route.params.id}`);
    const payment = response.data;
    
    form.value = {
      order_id: payment.order_id || null,
      payer_name: payment.payer_name || '',
      payer_email: payment.payer_email || '',
      payer_phone: payment.payer_phone || '',
      amount: payment.amount || 0,
      payment_method: payment.payment_method || 'cash',
      status: payment.status || 'pending',
      notes: payment.notes || '',
      payment_date: payment.payment_date ? payment.payment_date.split('T')[0] : '',
      paid_at: payment.paid_at ? payment.paid_at.replace('Z', '').slice(0, 16) : '',
    };

    paymentNumberDisplay.value = payment.payment_number || '';
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить платеж';
    console.error('Error fetching payment:', err);
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
    if (!data.payment_date) {
      delete data.payment_date;
    }
    if (!data.paid_at) {
      delete data.paid_at;
    }
    if (!data.order_id) {
      delete data.order_id;
    }

    // shop_id обязателен при создании
    if (!isEditMode.value) {
      if (!shopStore.selectedShopId) {
        error.value = 'Необходимо выбрать магазин';
        return;
      }
      data.shop_id = shopStore.selectedShopId;
    } else {
      // При редактировании shop_id должен быть установлен, если не передан явно
      if (!data.shop_id && shopStore.selectedShopId) {
        data.shop_id = shopStore.selectedShopId;
      }
    }

    if (isEditMode.value) {
      await apiClient.put(`/admin/payments/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/payments', data);
    }

    router.push('/admin/payments');
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
    fetchPayment();
  } else {
    paymentNumberDisplay.value = 'Будет сгенерирован автоматически';
  }
});
</script>
