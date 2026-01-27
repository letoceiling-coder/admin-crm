<template>
  <div class="settings-page">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Настройки</h1>
      <p class="text-gray-600 mt-1">Управление настройками системы</p>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-red-800">{{ error }}</p>
      </div>
    </div>

    <form v-if="!loading" @submit.prevent="submitForm" class="space-y-6">
      <!-- Навигация по настройкам -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div class="flex gap-4">
          <router-link
            to="/admin/settings"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="route.name === 'admin.settings' 
              ? 'bg-blue-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            Общие
          </router-link>
          <router-link
            to="/admin/settings/delivery"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="route.name === 'admin.settings.delivery' 
              ? 'bg-blue-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            Доставка
          </router-link>
        </div>
      </div>

      <!-- Фото по умолчанию -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Фото по умолчанию</h2>
        <p class="text-sm text-gray-600 mb-4">
          Это фото будет отображаться на фронтенде в случае, если у товара нет фото или фото битое.
        </p>
        
        <div class="flex items-center gap-4">
          <div v-if="defaultImage" class="relative">
            <img 
              :src="defaultImage.url" 
              :alt="defaultImage.name" 
              class="h-32 w-32 object-cover rounded-lg border border-gray-300"
            />
          </div>
          <div v-else class="h-32 w-32 bg-gray-100 rounded-lg border border-gray-300 flex items-center justify-center">
            <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div class="flex gap-2">
            <button 
              type="button" 
              @click="showImageSelector = true" 
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              {{ defaultImage ? 'Изменить' : 'Выбрать из медиа' }}
            </button>
            <button 
              v-if="defaultImage" 
              type="button" 
              @click="removeDefaultImage" 
              class="px-4 py-2 text-red-600 hover:text-red-800 transition-colors"
            >
              Удалить
            </button>
          </div>
        </div>
      </div>

      <!-- Кнопки -->
      <div class="flex justify-end gap-3">
        <button 
          type="submit" 
          :disabled="submitting" 
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
        >
          <div v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          Сохранить настройки
        </button>
      </div>
    </form>

    <!-- Media Selector -->
    <MediaSelector
      :open="showImageSelector"
      :multiple="false"
      :allowedTypes="['photo']"
      :currentSelection="defaultImage ? [defaultImage] : []"
      @close="showImageSelector = false"
      @select="handleImageSelect"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import MediaSelector from '@/components/admin/MediaSelector.vue';
import Swal from 'sweetalert2';

const route = useRoute();

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const defaultImage = ref(null);
const showImageSelector = ref(false);

const fetchSettings = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get('/admin/settings');
    if (response.data && response.data.settings) {
      defaultImage.value = response.data.settings.default_image || null;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить настройки';
    console.error('Error fetching settings:', err);
  } finally {
    loading.value = false;
  }
};

const handleImageSelect = (selectedFile) => {
  defaultImage.value = selectedFile;
};

const removeDefaultImage = () => {
  defaultImage.value = null;
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;

  try {
    const response = await apiClient.put('/admin/settings', {
      default_image_id: defaultImage.value?.id || null,
    });

    if (response.data && response.data.settings) {
      if (response.data.settings.default_image) {
        defaultImage.value = response.data.settings.default_image;
      } else {
        defaultImage.value = null;
      }
    }

    await Swal.fire({
      icon: 'success',
      title: 'Успешно',
      text: 'Настройки успешно сохранены',
      timer: 2000,
      showConfirmButton: false,
    });
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить настройки';
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка',
      text: error.value,
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>
