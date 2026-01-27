<template>
  <div class="payment-method-settings-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Способы оплаты</h1>
      <p class="text-gray-600 mt-1">Настройка способов оплаты и скидок</p>
    </div>

    <!-- Загрузка -->
    <div v-if="loading && !methods.length" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <p class="text-gray-600">Загрузка способов оплаты...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <!-- Предупреждение о выборе магазина -->
    <div v-else-if="!shopStore.selectedShopId" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <p class="text-yellow-800">Выберите магазин для настройки способов оплаты</p>
      </div>
    </div>

    <!-- Список способов оплаты -->
    <div v-else class="space-y-6">
      <div
        v-for="method in methods"
        :key="method.id"
        class="bg-white rounded-lg border border-gray-200 p-6"
      >
        <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ method.name }}</h2>
        
        <form @submit.prevent="handleSubmit(method)" class="space-y-6">
          <!-- Основная информация -->
          <div class="space-y-4">
            <h3 class="text-md font-medium text-gray-700">Основная информация</h3>
            
            <!-- Статус -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="method.is_enabled"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Способ оплаты активен</span>
              </label>
            </div>

            <!-- По умолчанию -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="method.is_default"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">По умолчанию (будет выбран автоматически)</span>
              </label>
            </div>

            <!-- Порядок сортировки -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Порядок сортировки</label>
              <input
                v-model.number="method.sort_order"
                type="number"
                min="0"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
            </div>
          </div>

          <!-- Доступность -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-700">Доступность оплаты</h3>
            
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="method.available_for_delivery"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Доступен при доставке</span>
              </label>
            </div>

            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="method.available_for_pickup"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Доступен при самовывозе</span>
              </label>
            </div>
          </div>

          <!-- Настройки скидки -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-700">Настройки скидки</h3>
            
            <!-- Тип скидки -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Тип скидки</label>
              <select
                v-model="method.discount_type"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              >
                <option value="none">Нет скидки</option>
                <option value="percentage">Процент от суммы корзины</option>
                <option value="fixed">Фиксированная сумма</option>
              </select>
            </div>

            <!-- Значение скидки -->
            <div v-if="method.discount_type !== 'none'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Значение скидки
              </label>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="method.discount_value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="flex-1 h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
                />
                <span class="text-sm text-gray-600">
                  {{ method.discount_type === 'percentage' ? '%' : '₽' }}
                </span>
              </div>
            </div>

            <!-- Минимальная сумма корзины -->
            <div v-if="method.discount_type !== 'none'">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Минимальная сумма корзины (₽)
              </label>
              <input
                v-model.number="method.min_cart_amount"
                type="number"
                step="0.01"
                min="0"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
              <p class="text-xs text-gray-500 mt-1">
                Скидка будет применяться только если сумма корзины превышает указанное значение
              </p>
            </div>
          </div>

          <!-- Уведомление -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-700">Уведомление пользователя</h3>
            
            <!-- Показывать уведомление -->
            <div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="method.show_notification"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Показывать уведомление при выборе</span>
              </label>
            </div>

            <!-- Текст уведомления -->
            <div v-if="method.show_notification">
              <label class="block text-sm font-medium text-gray-700 mb-1">Текст уведомления</label>
              <textarea
                v-model="method.notification_text"
                rows="3"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-900"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">
                Доступные плейсхолдеры: {discount}, {discount_percent}, {final_amount}, {cart_amount}
              </p>
            </div>
          </div>

          <!-- Кнопка сохранения -->
          <div class="flex justify-end pt-4 border-t border-gray-200">
            <button
              type="submit"
              :disabled="saving[method.payment_method_code]"
              class="h-10 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2 disabled:opacity-50"
            >
              <svg v-if="saving[method.payment_method_code]" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              {{ saving[method.payment_method_code] ? 'Сохранение...' : 'Сохранить настройки' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '@/api/axios';
import Swal from 'sweetalert2';
import { useShopStore } from '@/stores/shop';

const shopStore = useShopStore();
const methods = ref([]);
const loading = ref(false);
const saving = ref({});
const error = ref(null);

const loadMethods = async () => {
  loading.value = true;
  error.value = null;
  try {
    if (!shopStore.selectedShopId) {
      error.value = 'Выберите магазин';
      loading.value = false;
      return;
    }

    const response = await apiClient.get('/admin/payment-methods', {
      params: { shop_id: shopStore.selectedShopId }
    });
    
    methods.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error('Error loading payment methods:', err);
    if (err.response?.status !== 404) {
      error.value = err.response?.data?.message || 'Ошибка при загрузке способов оплаты';
    }
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async (method) => {
  saving.value[method.payment_method_code] = true;
  try {
    if (!shopStore.selectedShopId) {
      await Swal.fire({
        icon: 'error',
        title: 'Ошибка',
        text: 'Выберите магазин',
      });
      return;
    }

    // Очистка полей в зависимости от типа скидки
    const formData = { ...method };
    if (formData.discount_type === 'none') {
      formData.discount_value = null;
      formData.min_cart_amount = null;
    }
    if (!formData.show_notification) {
      formData.notification_text = null;
    }
    
    // Добавляем shop_id
    formData.shop_id = shopStore.selectedShopId;

    const response = await apiClient.put(`/admin/payment-methods/${method.payment_method_code}`, formData);
    
    await Swal.fire({
      icon: 'success',
      title: 'Успешно',
      text: 'Настройки способа оплаты сохранены',
      timer: 2000,
      showConfirmButton: false,
    });
    
    // Обновляем данные метода
    const updatedMethod = response.data?.data || response.data;
    if (updatedMethod) {
      const index = methods.value.findIndex(m => m.id === method.id);
      if (index !== -1) {
        methods.value[index] = { ...methods.value[index], ...updatedMethod };
      }
    }
  } catch (err) {
    console.error('Error saving payment method:', err);
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка',
      text: err.response?.data?.message || 'Ошибка при сохранении настроек',
    });
  } finally {
    saving.value[method.payment_method_code] = false;
  }
};

onMounted(() => {
  loadMethods();
});
</script>
