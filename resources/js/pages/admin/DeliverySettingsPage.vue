<template>
  <div class="delivery-settings-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Настройки доставки</h1>
      <p class="text-gray-600 mt-1">Настройка расчета стоимости доставки по расстоянию</p>
    </div>

    <!-- Загрузка -->
    <div v-if="loading && !settings" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <p class="text-gray-600">Загрузка настроек...</p>
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
        <p class="text-yellow-800">Выберите магазин для настройки доставки</p>
      </div>
    </div>

    <!-- Форма настроек -->
    <div v-else class="space-y-6">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Параметры подключения</h2>
        
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- API Settings -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                API ключ Яндекс.Геокодер
              </label>
              <input
                v-model="form.yandex_geocoder_api_key"
                type="password"
                placeholder="Введите API ключ Яндекс.Геокодера"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Оставьте пустым, чтобы не изменять существующий ключ
              </p>
            </div>
          </div>

          <!-- Default City -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Город по умолчанию для поиска адресов
              </label>
              <input
                v-model="form.default_city"
                type="text"
                placeholder="Например: Екатеринбург"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Город, по которому будет происходить поиск адресов при оформлении заказа
              </p>
            </div>
          </div>

          <!-- Free Delivery Threshold -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Сумма корзины для бесплатной доставки (₽)
              </label>
              <input
                v-model.number="form.free_delivery_threshold"
                type="number"
                step="0.01"
                min="0"
                placeholder="7000"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                При сумме заказа равной или превышающей указанную, доставка будет бесплатной
              </p>
            </div>
          </div>

          <!-- Origin Point -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Точка начала доставки
              </label>
              <p class="text-xs text-gray-500 mb-2">
                Адрес и координаты точки, от которой рассчитывается расстояние доставки
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Адрес
              </label>
              <input
                v-model="form.origin_address"
                type="text"
                placeholder="г. Екатеринбург, ул. Ленина, 1"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
              />
              <p class="text-xs text-gray-500 mt-1">
                Адрес будет автоматически геокодирован при сохранении
              </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Широта
                </label>
                <input
                  v-model="form.origin_latitude"
                  type="number"
                  step="any"
                  placeholder="56.8431"
                  class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Долгота
                </label>
                <input
                  v-model="form.origin_longitude"
                  type="number"
                  step="any"
                  placeholder="60.6454"
                  class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
                />
              </div>
            </div>
          </div>

          <!-- Delivery Zones -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Зоны доставки
              </label>
              <p class="text-xs text-gray-500 mb-2">
                Настройте зоны доставки по расстоянию. Последняя зона с пустым расстоянием будет применяться для всех адресов дальше предыдущих зон.
              </p>
            </div>
            <div class="space-y-3">
              <div
                v-for="(zone, index) in form.delivery_zones"
                :key="index"
                class="flex items-end gap-4 p-4 border border-gray-300 rounded-lg"
              >
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Максимальное расстояние (км)
                  </label>
                  <input
                    v-model.number="zone.max_distance"
                    type="number"
                    step="0.1"
                    min="0"
                    :placeholder="index === form.delivery_zones.length - 1 ? 'Свыше предыдущей зоны' : '3'"
                    class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
                    :disabled="index === form.delivery_zones.length - 1 && zone.max_distance === null"
                  />
                  <p v-if="index === form.delivery_zones.length - 1" class="text-xs text-gray-500 mt-1">
                    Оставьте пустым для последней зоны
                  </p>
                </div>
                <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Стоимость (₽)
                  </label>
                  <input
                    v-model.number="zone.cost"
                    type="number"
                    step="1"
                    min="0"
                    placeholder="300"
                    class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900"
                  />
                </div>
                <button
                  v-if="form.delivery_zones.length > 1"
                  type="button"
                  @click="removeZone(index)"
                  class="h-10 px-3 bg-red-50 text-red-600 rounded-lg hover:bg-red-100"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>
            <button
              type="button"
              @click="addZone"
              class="w-full h-10 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 border border-gray-300 inline-flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              Добавить зону
            </button>
          </div>

          <!-- Minimum Delivery Order Total -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Минимальный заказ для доставки (₽)
              </label>
              <input
                v-model.number="form.min_delivery_order_total_rub"
                type="number"
                step="0.01"
                min="0"
                placeholder="3000"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Минимальная сумма заказа для оформления доставки курьером. На самовывоз не влияет.
              </p>
            </div>
          </div>

          <!-- Minimum Lead Time -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Минимальное время подготовки (часы)
              </label>
              <input
                v-model.number="form.delivery_min_lead_hours"
                type="number"
                step="1"
                min="0"
                max="72"
                placeholder="3"
                class="w-full h-10 px-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-gray-500 mt-1">
                Минимальное количество часов от текущего момента до доступного времени доставки. Например, при значении 3 и текущем времени 10:00, самый ранний слот будет 13:00.
              </p>
            </div>
          </div>

          <!-- Enable/Disable -->
          <div class="space-y-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Включить систему расчета доставки
                </label>
                <p class="text-xs text-gray-500">
                  Включите эту опцию, чтобы система автоматически рассчитывала стоимость доставки
                </p>
              </div>
              <input
                v-model="form.is_enabled"
                type="checkbox"
                id="is_enabled"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
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
const settings = ref(null);
const form = ref({
  yandex_geocoder_api_key: '',
  origin_address: '',
  origin_latitude: '',
  origin_longitude: '',
  default_city: 'Екатеринбург',
  free_delivery_threshold: 7000,
  delivery_zones: [
    { max_distance: 3, cost: 300 },
    { max_distance: 7, cost: 500 },
    { max_distance: 12, cost: 800 },
    { max_distance: null, cost: 1000 },
  ],
  is_enabled: false,
  min_delivery_order_total_rub: 3000,
  delivery_min_lead_hours: 3,
});
const savedApiKey = ref('');
const errors = ref({});
const loading = ref(false);
const saving = ref(false);
const error = ref(null);

const loadSettings = async () => {
  loading.value = true;
  error.value = null;
  try {
    if (!shopStore.selectedShopId) {
      error.value = 'Выберите магазин';
      loading.value = false;
      return;
    }

    const response = await apiClient.get('/admin/settings/delivery', {
      params: { shop_id: shopStore.selectedShopId }
    });
    settings.value = response.data?.settings || response.data;
    
    // Заполняем форму (если настройки уже есть)
    if (settings.value) {
      const currentApiKey = form.value.yandex_geocoder_api_key || savedApiKey.value;
      
      form.value = {
        yandex_geocoder_api_key: currentApiKey || '',
        origin_address: settings.value.origin_address || '',
        origin_latitude: settings.value.origin_latitude ? String(settings.value.origin_latitude) : '',
        origin_longitude: settings.value.origin_longitude ? String(settings.value.origin_longitude) : '',
        default_city: settings.value.default_city || 'Екатеринбург',
        free_delivery_threshold: settings.value.free_delivery_threshold !== undefined && settings.value.free_delivery_threshold !== null
          ? Number(settings.value.free_delivery_threshold)
          : 7000,
        delivery_zones: settings.value.delivery_zones && Array.isArray(settings.value.delivery_zones) && settings.value.delivery_zones.length > 0
          ? settings.value.delivery_zones
          : form.value.delivery_zones,
        is_enabled: settings.value.is_enabled !== undefined ? settings.value.is_enabled : false,
        min_delivery_order_total_rub: settings.value.min_delivery_order_total_rub !== undefined && settings.value.min_delivery_order_total_rub !== null
          ? Number(settings.value.min_delivery_order_total_rub)
          : 3000,
        delivery_min_lead_hours: settings.value.delivery_min_lead_hours !== undefined && settings.value.delivery_min_lead_hours !== null
          ? Number(settings.value.delivery_min_lead_hours)
          : 3,
      };
      
      savedApiKey.value = currentApiKey;
    }
  } catch (err) {
    console.error('Error loading delivery settings:', err);
    if (err.response?.status !== 404) {
      error.value = err.response?.data?.message || 'Ошибка при загрузке настроек';
    }
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  saving.value = true;
  errors.value = {};
  error.value = null;
  
  if (!shopStore.selectedShopId) {
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка',
      text: 'Выберите магазин',
    });
    saving.value = false;
    return;
  }
  
  try {
    const submitData = {
      ...form.value,
      shop_id: shopStore.selectedShopId,
      origin_latitude: form.value.origin_latitude ? parseFloat(form.value.origin_latitude) : null,
      origin_longitude: form.value.origin_longitude ? parseFloat(form.value.origin_longitude) : null,
      yandex_geocoder_api_key: form.value.yandex_geocoder_api_key || undefined,
    };
    
    const response = await apiClient.put('/admin/settings/delivery', submitData);
    settings.value = response.data?.settings || response.data;
    
    // Обновляем координаты в форме из ответа сервера
    if (settings.value) {
      form.value.origin_latitude = settings.value.origin_latitude ? String(settings.value.origin_latitude) : '';
      form.value.origin_longitude = settings.value.origin_longitude ? String(settings.value.origin_longitude) : '';
    }
    
    await Swal.fire({
      icon: 'success',
      title: 'Успешно',
      text: 'Настройки доставки успешно сохранены',
      timer: 2000,
      showConfirmButton: false,
    });
    
    if (form.value.yandex_geocoder_api_key) {
      savedApiKey.value = form.value.yandex_geocoder_api_key;
    }
  } catch (err) {
    console.error('Error saving delivery settings:', err);
    error.value = err.response?.data?.message || 'Ошибка при сохранении настроек';
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка',
      text: error.value,
    });
  } finally {
    saving.value = false;
  }
};

const addZone = () => {
  form.value.delivery_zones.push({ max_distance: null, cost: 0 });
};

const removeZone = (index) => {
  if (form.value.delivery_zones.length > 1) {
    form.value.delivery_zones.splice(index, 1);
  }
};

onMounted(() => {
  loadSettings();
});
</script>
