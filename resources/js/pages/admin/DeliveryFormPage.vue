<template>
  <div class="delivery-form-page">
    <div class="mb-6">
      <router-link
        to="/admin/deliveries"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку доставок
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование доставки' : 'Создание доставки' }}
      </h1>
      <p class="text-gray-600 mt-1">
        {{ isEditMode ? 'Измените информацию о доставке' : 'Заполните информацию о новой доставке' }}
      </p>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Информация о получателе -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о получателе</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1">
              Имя получателя <span class="text-red-500">*</span>
            </label>
            <input
              id="recipient_name"
              v-model="form.recipient_name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.recipient_name }"
              placeholder="Введите имя получателя"
            />
            <p v-if="errors.recipient_name" class="mt-1 text-sm text-red-600">{{ errors.recipient_name }}</p>
          </div>

          <div>
            <label for="recipient_phone" class="block text-sm font-medium text-gray-700 mb-1">
              Телефон <span class="text-red-500">*</span>
            </label>
            <input
              id="recipient_phone"
              v-model="form.recipient_phone"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.recipient_phone }"
              placeholder="+7 (999) 123-45-67"
            />
            <p v-if="errors.recipient_phone" class="mt-1 text-sm text-red-600">{{ errors.recipient_phone }}</p>
          </div>
        </div>

        <div class="mt-4">
          <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">
            Адрес доставки <span class="text-red-500">*</span>
          </label>
          <textarea
            id="delivery_address"
            v-model="form.delivery_address"
            rows="3"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="{ 'border-red-500': errors.delivery_address }"
            placeholder="Введите полный адрес доставки"
          ></textarea>
          <p v-if="errors.delivery_address" class="mt-1 text-sm text-red-600">{{ errors.delivery_address }}</p>
        </div>
      </div>

      <!-- Информация о доставке -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация о доставке</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="delivery_number" class="block text-sm font-medium text-gray-700 mb-1">
              Номер доставки
            </label>
            <input
              id="delivery_number"
              v-model="deliveryNumberDisplay"
              type="text"
              disabled
              class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
            />
            <p class="mt-1 text-xs text-gray-500">Генерируется автоматически</p>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
              Статус доставки
            </label>
            <select
              id="status"
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="pending">Ожидает</option>
              <option value="in_transit">В пути</option>
              <option value="delivered">Доставлено</option>
              <option value="cancelled">Отменено</option>
            </select>
          </div>

          <div>
            <label for="delivery_date" class="block text-sm font-medium text-gray-700 mb-1">
              Дата доставки
            </label>
            <input
              id="delivery_date"
              v-model="form.delivery_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div v-if="isEditMode && form.status === 'delivered'">
            <label for="delivered_at" class="block text-sm font-medium text-gray-700 mb-1">
              Дата фактической доставки
            </label>
            <input
              id="delivered_at"
              v-model="form.delivered_at"
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
            placeholder="Дополнительная информация о доставке"
          ></textarea>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/admin/deliveries"
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
const deliveryNumberDisplay = ref('');

const form = ref({
  order_id: null,
  recipient_name: '',
  recipient_phone: '',
  delivery_address: '',
  notes: '',
  status: 'pending',
  delivery_date: '',
  delivered_at: '',
});

const fetchDelivery = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get(`/admin/deliveries/${route.params.id}`);
    const delivery = response.data;
    
    form.value = {
      order_id: delivery.order_id || null,
      recipient_name: delivery.recipient_name || '',
      recipient_phone: delivery.recipient_phone || '',
      delivery_address: delivery.delivery_address || '',
      notes: delivery.notes || '',
      status: delivery.status || 'pending',
      delivery_date: delivery.delivery_date ? delivery.delivery_date.split('T')[0] : '',
      delivered_at: delivery.delivered_at ? delivery.delivered_at.replace('Z', '').slice(0, 16) : '',
    };

    deliveryNumberDisplay.value = delivery.delivery_number || '';
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить доставку';
    console.error('Error fetching delivery:', err);
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
    if (!data.delivery_date) {
      delete data.delivery_date;
    }
    if (!data.delivered_at) {
      delete data.delivered_at;
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
      await apiClient.put(`/admin/deliveries/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/deliveries', data);
    }

    router.push('/admin/deliveries');
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
    fetchDelivery();
  } else {
    deliveryNumberDisplay.value = 'Будет сгенерирован автоматически';
  }
});
</script>
