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
            <div class="flex gap-2">
              <input
                id="telegram_bot_token"
                v-model="form.telegram_bot_token"
                type="text"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-green-500': botTokenValid, 'border-red-500': botTokenValid === false }"
                placeholder="Введите токен телеграм-бота"
              />
              <button
                v-if="isEditMode && form.telegram_bot_token"
                type="button"
                @click="validateToken"
                :disabled="validatingToken"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="validatingToken" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                <span v-else>Проверить</span>
              </button>
            </div>
            <div class="mt-2 flex items-center gap-4">
              <p class="text-xs text-gray-500">
                Токен можно получить у @BotFather в Telegram
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
            <div v-if="botInfo" class="mt-2 p-3 bg-gray-50 rounded-lg">
              <p class="text-xs font-medium text-gray-700 mb-1">Информация о боте:</p>
              <p class="text-xs text-gray-600">Имя: {{ botInfo.bot?.first_name }} {{ botInfo.bot?.last_name || '' }}</p>
              <p class="text-xs text-gray-600">Username: @{{ botInfo.bot?.username }}</p>
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
  inn: '',
  ogrn: '',
  telegram_bot_token: '',
  addresses: [],
  phones: [],
  custom_fields: [],
});

const errors = ref({});
const loading = ref(false);
const submitting = ref(false);
const validatingToken = ref(false);
const botTokenValid = ref(null);
const botInfo = ref(null);

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
  if (!form.value.telegram_bot_token || !isEditMode.value) return;

  validatingToken.value = true;
  botTokenValid.value = null;
  botInfo.value = null;

  try {
    const response = await apiClient.post(`/admin/shops/${route.params.id}/validate-bot-token`);
    botTokenValid.value = response.data.valid;
    if (response.data.valid && response.data.bot) {
      botInfo.value = { bot: response.data.bot };
    }
  } catch (err) {
    botTokenValid.value = false;
    console.error('Error validating token:', err);
  } finally {
    validatingToken.value = false;
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
      inn: shop.inn || '',
      ogrn: shop.ogrn || '',
      telegram_bot_token: shop.telegram_bot_token || '',
      addresses: shop.addresses?.map(a => a.address) || [],
      phones: shop.phones?.map(p => p.phone) || [],
      custom_fields: shop.custom_fields?.map(f => ({
        field_name: f.field_name,
        field_value: f.field_value || '',
      })) || [],
    };
    
    // Сбрасываем состояние проверки токена при загрузке
    botTokenValid.value = null;
    botInfo.value = null;
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
      inn: form.value.inn || null,
      ogrn: form.value.ogrn || null,
      telegram_bot_token: form.value.telegram_bot_token || null,
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
