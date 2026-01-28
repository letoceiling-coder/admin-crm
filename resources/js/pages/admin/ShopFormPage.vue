<template>
  <div class="shop-form-page">
    <div class="mb-6">
      <router-link
        to="/admin/shops"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку магазинов
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование магазина' : 'Создание магазина' }}
      </h1>
      <p class="text-gray-600 mt-1">
        {{ isEditMode ? 'Измените информацию о магазине' : 'Заполните информацию о новом магазине' }}
      </p>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Основная информация -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Основная информация</h2>
        
        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
              Название магазина <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Введите название магазина"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
          </div>

          <div>
            <label for="template" class="block text-sm font-medium text-gray-700 mb-1">
              Шаблон дизайна
            </label>
            <select
              id="template"
              v-model="form.template"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="amber">Янтарный (по умолчанию)</option>
              <option value="blue">Синий</option>
              <option value="green">Зеленый</option>
              <option value="purple">Фиолетовый</option>
              <option value="monochrome">Черно-белый</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Выберите цветовую схему для фронтенда магазина</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="inn" class="block text-sm font-medium text-gray-700 mb-1">
                ИНН
              </label>
              <input
                id="inn"
                v-model="form.inn"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Введите ИНН"
              />
            </div>

            <div>
              <label for="ogrn" class="block text-sm font-medium text-gray-700 mb-1">
                ОГРН
              </label>
              <input
                id="ogrn"
                v-model="form.ogrn"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Введите ОГРН"
              />
            </div>
          </div>

          <div>
            <label for="telegram_bot_token" class="block text-sm font-medium text-gray-700 mb-1">
              Токен телеграм-бота
            </label>
            <div class="flex gap-2 flex-wrap">
              <input
                id="telegram_bot_token"
                v-model="form.telegram_bot_token"
                type="text"
                class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-green-500': botTokenValid, 'border-red-500': botTokenValid === false }"
                placeholder="Введите токен телеграм-бота"
              />
              <button
                v-if="form.telegram_bot_token"
                type="button"
                @click="validateToken"
                :disabled="validatingToken"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="validatingToken" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span v-else>Проверить токен</span>
              </button>
              <button
                v-if="isEditMode && form.telegram_bot_token"
                type="button"
                @click="checkWebhook"
                :disabled="loadingWebhook"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="loadingWebhook" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span v-else>Тест webhook</span>
              </button>
            </div>
            <div class="mt-2 flex items-center gap-4 flex-wrap">
              <p class="text-xs text-gray-500">
                Токен можно получить у @BotFather в Telegram. При сохранении webhook устанавливается автоматически.
              </p>
              <span v-if="botTokenValid === true" class="text-xs text-green-600 flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Токен валиден
              </span>
              <span v-if="botTokenValid === false" class="text-xs text-red-600 flex items-center gap-1">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Токен невалиден
              </span>
            </div>
            <div v-if="botInfo || form.telegram_bot_name" class="mt-2 p-3 bg-gray-50 rounded-lg">
              <p class="text-xs font-medium text-gray-700 mb-1">Наименование бота:</p>
              <p class="text-sm text-gray-900">
                {{ (botInfo && botInfo.bot_name) ? botInfo.bot_name : (form.telegram_bot_name || displayBotName) }}
              </p>
              <p v-if="botInfo && botInfo.bot" class="text-xs text-gray-600 mt-1">
                @{{ botInfo.bot?.username }}
              </p>
            </div>
            <div v-if="webhookInfo" class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <p class="text-xs font-medium text-gray-700 mb-1">Webhook:</p>
              <p class="text-xs text-gray-600 break-all">{{ webhookInfo.url || 'Не установлен' }}</p>
              <p v-if="webhookInfo.error" class="text-xs text-red-600 mt-1">{{ webhookInfo.error }}</p>
            </div>
          </div>

          <!-- Приветственное сообщение бота (/start) -->
          <div class="space-y-2 pt-4 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-700">Приветственное сообщение (команда /start)</h3>
            <label for="welcome_message" class="block text-sm text-gray-600 mb-1">
              Текст приветствия (под кнопкой — инлайн «Открыть каталог» → Mini App)
            </label>
            <textarea
              id="welcome_message"
              v-model="form.welcome_message"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Добро пожаловать! Нажмите кнопку ниже, чтобы открыть каталог."
            ></textarea>
            <div class="flex items-center gap-4">
              <div>
                <label for="welcome_photo_media_id" class="block text-sm text-gray-600 mb-1">Фото из медиа (ID)</label>
                <input
                  id="welcome_photo_media_id"
                  v-model.number="form.welcome_photo_media_id"
                  type="number"
                  min="0"
                  class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="ID медиа"
                />
              </div>
              <p class="text-xs text-gray-500 self-end pb-2">Оставьте пустым, если без фото. Mini App: https://crm.neeklo.ru/{{ form.slug || 'slug' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Адреса -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Адреса</h2>
          <button
            type="button"
            @click="addAddress"
            class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Добавить адрес
          </button>
        </div>

        <div v-if="form.addresses.length === 0" class="text-gray-500 text-sm text-center py-4">
          Адреса не добавлены
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(address, index) in form.addresses"
            :key="index"
            class="flex items-start gap-3"
          >
            <input
              v-model="form.addresses[index]"
              type="text"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Введите адрес"
            />
            <button
              type="button"
              @click="removeAddress(index)"
              class="px-3 py-2 text-red-600 hover:text-red-800"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Телефоны -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Телефоны</h2>
          <button
            type="button"
            @click="addPhone"
            class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Добавить телефон
          </button>
        </div>

        <div v-if="form.phones.length === 0" class="text-gray-500 text-sm text-center py-4">
          Телефоны не добавлены
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(phone, index) in form.phones"
            :key="index"
            class="flex items-start gap-3"
          >
            <input
              v-model="form.phones[index]"
              type="text"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Введите телефон"
            />
            <button
              type="button"
              @click="removePhone(index)"
              class="px-3 py-2 text-red-600 hover:text-red-800"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Кастомные поля -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Дополнительные поля</h2>
          <button
            type="button"
            @click="addCustomField"
            class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Добавить поле
          </button>
        </div>

        <div v-if="form.custom_fields.length === 0" class="text-gray-500 text-sm text-center py-4">
          Дополнительные поля не добавлены
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(field, index) in form.custom_fields"
            :key="index"
            class="grid grid-cols-1 md:grid-cols-3 gap-3"
          >
            <input
              v-model="field.field_name"
              type="text"
              required
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Название поля"
            />
            <input
              v-model="field.field_value"
              type="text"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Значение"
            />
            <button
              type="button"
              @click="removeCustomField(index)"
              class="px-3 py-2 text-red-600 hover:text-red-800"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex items-center justify-end gap-3">
        <router-link
          to="/admin/shops"
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
          {{ isEditMode ? 'Сохранить изменения' : 'Создать магазин' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/axios';

const router = useRouter();
const route = useRoute();

const isEditMode = computed(() => !!route.params.id);

const form = ref({
  name: '',
  template: 'amber',
  inn: '',
  ogrn: '',
  telegram_bot_token: '',
  telegram_bot_name: '',
  welcome_message: '',
  welcome_photo_media_id: null,
  slug: '',
  addresses: [],
  phones: [],
  custom_fields: [],
});

const errors = ref({});
const loading = ref(false);
const submitting = ref(false);
const validatingToken = ref(false);
const loadingWebhook = ref(false);
const botTokenValid = ref(null);
const botInfo = ref(null);
const webhookInfo = ref(null);

const displayBotName = computed(() => {
  if (!botInfo.value?.bot) return '';
  const b = botInfo.value.bot;
  return [b.first_name, b.last_name].filter(Boolean).join(' ') + (b.username ? ' (@' + b.username + ')' : '');
});

const addAddress = () => {
  form.value.addresses.push('');
};

const removeAddress = (index) => {
  form.value.addresses.splice(index, 1);
};

const addPhone = () => {
  form.value.phones.push('');
};

const removePhone = (index) => {
  form.value.phones.splice(index, 1);
};

const addCustomField = () => {
  form.value.custom_fields.push({
    field_name: '',
    field_value: '',
  });
};

const removeCustomField = (index) => {
  form.value.custom_fields.splice(index, 1);
};

const validateToken = async () => {
  if (!form.value.telegram_bot_token) return;

  validatingToken.value = true;
  botTokenValid.value = null;
  botInfo.value = null;
  webhookInfo.value = null;

  try {
    const url = isEditMode.value
      ? `/admin/shops/${route.params.id}/validate-bot-token`
      : '/admin/validate-bot-token';
    const response = await apiClient.post(url, {
      telegram_bot_token: form.value.telegram_bot_token,
    });
    botTokenValid.value = response.data.valid;
    if (response.data.valid) {
      botInfo.value = {
        bot: response.data.bot,
        bot_name: response.data.bot_name,
      };
      if (response.data.bot_name) form.value.telegram_bot_name = response.data.bot_name;
    }
  } catch (err) {
    botTokenValid.value = false;
    console.error('Error validating token:', err);
  } finally {
    validatingToken.value = false;
  }
};

const checkWebhook = async () => {
  if (!isEditMode.value || !form.value.telegram_bot_token) return;
  loadingWebhook.value = true;
  webhookInfo.value = null;
  try {
    const response = await apiClient.get(`/admin/shops/${route.params.id}/webhook-info`);
    if (response.data.success && response.data.webhook) {
      webhookInfo.value = { url: response.data.webhook.url || null };
    } else {
      webhookInfo.value = { url: null, error: response.data.error || 'Нет данных' };
    }
  } catch (err) {
    webhookInfo.value = { url: null, error: err.response?.data?.message || 'Ошибка запроса' };
  } finally {
    loadingWebhook.value = false;
  }
};

const fetchShop = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  try {
    const response = await apiClient.get(`/admin/shops/${route.params.id}`);
    const shop = response.data;
    
    form.value = {
      name: shop.name || '',
      template: shop.template || 'amber',
      inn: shop.inn || '',
      ogrn: shop.ogrn || '',
      telegram_bot_token: shop.telegram_bot_token || '',
      telegram_bot_name: shop.telegram_bot_name || '',
      welcome_message: shop.welcome_message || '',
      welcome_photo_media_id: shop.welcome_photo_media_id ?? null,
      slug: shop.slug || '',
      addresses: shop.addresses?.map(a => a.address) || [],
      phones: shop.phones?.map(p => p.phone) || [],
      custom_fields: shop.custom_fields?.map(f => ({
        field_name: f.field_name,
        field_value: f.field_value || '',
      })) || [],
    };

    botTokenValid.value = null;
    botInfo.value = null;
    webhookInfo.value = null;
  } catch (err) {
    console.error('Error fetching shop:', err);
    router.push('/admin/shops');
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  submitting.value = true;
  errors.value = {};

  try {
    const data = {
      name: form.value.name,
      template: form.value.template || 'amber',
      inn: form.value.inn || null,
      ogrn: form.value.ogrn || null,
      telegram_bot_token: form.value.telegram_bot_token || null,
      telegram_bot_name: form.value.telegram_bot_name || null,
      welcome_message: form.value.welcome_message || null,
      welcome_photo_media_id: form.value.welcome_photo_media_id || null,
      addresses: form.value.addresses.filter(a => a.trim() !== ''),
      phones: form.value.phones.filter(p => p.trim() !== ''),
      custom_fields: form.value.custom_fields
        .filter(f => f.field_name.trim() !== '')
        .map(f => ({
          field_name: f.field_name,
          field_value: f.field_value || null,
        })),
    };

    if (isEditMode.value) {
      await apiClient.put(`/admin/shops/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/shops', data);
    }

    router.push('/admin/shops');
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      errors.value = { general: err.response?.data?.message || 'Произошла ошибка при сохранении' };
    }
    console.error('Error submitting form:', err);
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  if (isEditMode.value) {
    fetchShop();
  }
});
</script>
