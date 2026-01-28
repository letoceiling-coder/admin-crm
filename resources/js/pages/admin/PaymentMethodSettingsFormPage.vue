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

        <!-- Интеграция ЮКасса (только для способа ЮКасса, привязана к магазину) -->
        <div v-if="code === 'yookassa'" class="space-y-4 pt-4 border-t border-gray-200">
          <h3 class="text-md font-medium text-gray-700">Подключение к ЮКасса</h3>
          <p class="text-xs text-gray-500">Настройки интеграции привязаны к выбранному магазину и не связаны с другими магазинами.</p>

          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="method.yookassa_is_test_mode"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">Тестовый режим (Sandbox)</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">В тестовом режиме используются тестовые Shop ID и Secret Key из личного кабинета ЮКасса</p>
          </div>

          <div v-if="method.yookassa_is_test_mode" class="space-y-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-800">Тестовые ключи</h4>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Test Shop ID</label>
              <input
                v-model="method.yookassa_test_shop_id"
                type="text"
                placeholder="Идентификатор тестового магазина"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Test Secret Key</label>
              <input
                v-model="method.yookassa_test_secret_key"
                :type="showTestSecretKey ? 'text' : 'password'"
                placeholder="Оставьте пустым, чтобы не менять"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
              <button type="button" @click="showTestSecretKey = !showTestSecretKey" class="mt-1 text-xs text-gray-500 hover:text-gray-700">
                {{ showTestSecretKey ? 'Скрыть' : 'Показать' }}
              </button>
            </div>
          </div>

          <div v-else class="space-y-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-800">Рабочие ключи (Production)</h4>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shop ID</label>
              <input
                v-model="method.yookassa_shop_id"
                type="text"
                placeholder="Идентификатор магазина в ЮКасса"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
              <input
                v-model="method.yookassa_secret_key"
                :type="showSecretKey ? 'text' : 'password'"
                placeholder="Оставьте пустым, чтобы не менять"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
              <button type="button" @click="showSecretKey = !showSecretKey" class="mt-1 text-xs text-gray-500 hover:text-gray-700">
                {{ showSecretKey ? 'Скрыть' : 'Показать' }}
              </button>
            </div>
          </div>

          <div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="method.yookassa_auto_capture"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">Автоматическое подтверждение платежей (capture)</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL для уведомлений (webhook)</label>
            <p v-if="method.yookassa_webhook_suggested_url" class="text-xs text-gray-500 mb-2">
              Рекомендуемый URL для этого магазина (указать в личном кабинете ЮКасса → Настройки → Уведомления):
            </p>
            <div v-if="method.yookassa_webhook_suggested_url" class="flex gap-2 mb-2">
              <input
                :value="method.yookassa_webhook_suggested_url"
                type="text"
                readonly
                class="flex-1 h-10 px-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-800 text-sm font-mono"
              />
              <button
                type="button"
                @click="copyWebhookUrl"
                class="h-10 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shrink-0 font-medium"
              >
                Копировать
              </button>
            </div>
            <input
              v-model="method.yookassa_webhook_url"
              type="url"
              :placeholder="method.yookassa_webhook_suggested_url || 'https://ваш-домен.ru/api/...'"
              class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
            />
            <p class="text-xs text-gray-500 mt-1">При необходимости укажите другой URL или оставьте рекомендуемый (сохраните настройки после «Копировать»)</p>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              :disabled="testingConnection"
              @click="testYooKassaConnection"
              class="h-10 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 inline-flex items-center gap-2 disabled:opacity-50"
            >
              <svg v-if="testingConnection" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              {{ testingConnection ? 'Проверка...' : 'Проверить подключение' }}
            </button>
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
const testingConnection = ref(false);
const showSecretKey = ref(false);
const showTestSecretKey = ref(false);

const code = computed(() => route.params.code);

function mergeYooKassaIntegration(m) {
  if (!m || m.payment_method_code !== 'yookassa') return m;
  const yi = m.yookassa_integration || {};
  const suggestedUrl = yi.yookassa_webhook_suggested_url || '';
  return {
    ...m,
    yookassa_shop_id: yi.yookassa_shop_id ?? '',
    yookassa_test_shop_id: yi.yookassa_test_shop_id ?? '',
    yookassa_is_test_mode: yi.yookassa_is_test_mode !== false,
    yookassa_auto_capture: yi.yookassa_auto_capture !== false,
    yookassa_webhook_url: yi.yookassa_webhook_url ?? suggestedUrl,
    yookassa_webhook_suggested_url: suggestedUrl,
    yookassa_secret_key: '',
    yookassa_test_secret_key: '',
  };
}

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
      method.value = mergeYooKassaIntegration({ ...found });
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

    if (code.value === 'yookassa') {
      formData.yookassa_shop_id = method.value.yookassa_shop_id ?? '';
      formData.yookassa_test_shop_id = method.value.yookassa_test_shop_id ?? '';
      formData.yookassa_is_test_mode = method.value.yookassa_is_test_mode !== false;
      formData.yookassa_auto_capture = method.value.yookassa_auto_capture !== false;
      formData.yookassa_webhook_url = method.value.yookassa_webhook_url ?? '';
      formData.yookassa_secret_key = method.value.yookassa_secret_key || undefined;
      formData.yookassa_test_secret_key = method.value.yookassa_test_secret_key || undefined;
    }

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
      method.value = mergeYooKassaIntegration({ ...method.value, ...updated });
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

async function testYooKassaConnection() {
  if (!shopStore.selectedShopId) return;
  testingConnection.value = true;
  try {
    const response = await apiClient.post('/admin/payment-methods/yookassa/test', {
      shop_id: shopStore.selectedShopId,
    });
    const result = response.data;
    await Swal.fire({
      icon: result.success ? 'success' : 'error',
      title: result.success ? 'Подключение успешно' : 'Ошибка подключения',
      text: result.message || (result.success ? 'ЮКасса отвечает.' : 'Проверьте ключи и режим.'),
    });
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Ошибка при проверке';
    await Swal.fire({ icon: 'error', title: 'Ошибка', text: msg });
  } finally {
    testingConnection.value = false;
  }
}

function copyWebhookUrl() {
  const url = method.value?.yookassa_webhook_suggested_url;
  if (!url) return;
  navigator.clipboard.writeText(url).then(() => {
    Swal.fire({ icon: 'success', title: 'Скопировано', timer: 1500, showConfirmButton: false });
  }).catch(() => {
    Swal.fire({ icon: 'error', title: 'Не удалось скопировать' });
  });
}

watch([() => shopStore.selectedShopId, code], () => {
  loadMethod();
});
</script>
