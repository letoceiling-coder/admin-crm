<template>
  <div class="payment-method-settings-form-page">
    <div class="mb-6 flex items-center gap-4">
      <router-link
        :to="{ name: 'admin.payment-methods' }"
        class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
      >
        <svg class="mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Способы оплаты
      </router-link>
    </div>

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ method?.name || 'Настройки способа оплаты' }}</h1>
      <p v-if="method?.description" class="text-gray-600 mt-1">{{ method.description }}</p>
    </div>

    <!-- Загрузка -->
    <div v-if="loading && !method" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <p class="text-gray-600">Загрузка настроек...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
      <router-link
        :to="{ name: 'admin.payment-methods' }"
        class="mt-2 inline-block text-sm font-medium text-red-600 hover:text-red-800"
      >
        ← Вернуться к списку
      </router-link>
    </div>

    <!-- Нет магазина -->
    <div v-else-if="!shopStore.selectedShopId" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <p class="text-yellow-800">Выберите магазин для настройки способов оплаты</p>
    </div>

    <!-- Форма -->
    <div v-else-if="method" class="bg-white rounded-lg border border-gray-200 p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Основная информация -->
        <div class="space-y-4">
          <h3 class="text-md font-medium text-gray-700">Основная информация</h3>

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

          <div v-if="method.discount_type !== 'none'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Значение скидки</label>
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

        <!-- Кнопки -->
        <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-200">
          <button
            type="submit"
            :disabled="saving"
            class="h-10 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2 disabled:opacity-50"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
          </button>
          <router-link
            :to="{ name: 'admin.payment-methods' }"
            class="inline-flex h-10 items-center px-4 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
          >
            Отмена
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import Swal from 'sweetalert2';
import { useShopStore } from '@/stores/shop';

const route = useRoute();
const shopStore = useShopStore();
const method = ref(null);
const loading = ref(false);
const saving = ref(false);
const error = ref(null);

const code = computed(() => route.params.code);

const loadMethod = async () => {
  if (!shopStore.selectedShopId || !code.value) {
    method.value = null;
    return;
  }
  loading.value = true;
  error.value = null;
  try {
    const response = await apiClient.get('/admin/payment-methods', {
      params: { shop_id: shopStore.selectedShopId }
    });
    const list = response.data?.data || response.data || [];
    const found = list.find((m) => m.payment_method_code === code.value);
    if (found) {
      method.value = { ...found };
    } else {
      error.value = 'Способ оплаты не найден';
      method.value = null;
    }
  } catch (err) {
    console.error('Error loading payment method:', err);
    error.value = err.response?.data?.message || 'Ошибка при загрузке настроек';
    method.value = null;
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  if (!method.value || !shopStore.selectedShopId) return;
  saving.value = true;
  try {
    const formData = { ...method.value };
    if (formData.discount_type === 'none') {
      formData.discount_value = null;
      formData.min_cart_amount = null;
    }
    if (!formData.show_notification) {
      formData.notification_text = null;
    }
    formData.shop_id = shopStore.selectedShopId;

    const response = await apiClient.put(`/admin/payment-methods/${method.value.payment_method_code}`, formData);

    await Swal.fire({
      icon: 'success',
      title: 'Успешно',
      text: 'Настройки способа оплаты сохранены',
      timer: 2000,
      showConfirmButton: false,
    });

    const updated = response.data?.data || response.data;
    if (updated) {
      method.value = { ...method.value, ...updated };
    }
  } catch (err) {
    console.error('Error saving payment method:', err);
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка',
      text: err.response?.data?.message || 'Ошибка при сохранении настроек',
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadMethod();
});

watch([() => shopStore.selectedShopId, code], () => {
  loadMethod();
});
</script>
