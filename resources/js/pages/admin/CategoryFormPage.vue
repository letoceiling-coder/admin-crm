<template>
  <div class="category-form-page">
    <div class="mb-6">
      <router-link
        to="/admin/categories"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку категорий
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование категории' : 'Создание категории' }}
      </h1>
      <p class="text-gray-600 mt-1">
        {{ isEditMode ? 'Измените информацию о категории' : 'Заполните информацию о новой категории' }}
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
              Название категории <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Введите название категории"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
          </div>

          <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
              Slug (URL)
            </label>
            <input
              id="slug"
              v-model="form.slug"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Автоматически генерируется из названия"
            />
            <p class="mt-1 text-xs text-gray-500">Оставьте пустым для автоматической генерации</p>
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Описание
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Введите описание категории"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                Родительская категория
              </label>
              <select
                id="parent_id"
                v-model="form.parent_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option :value="null">Без родителя</option>
                <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                Позиция
              </label>
              <input
                id="position"
                v-model.number="form.position"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label for="image_id" class="block text-sm font-medium text-gray-700 mb-1">
              Изображение
            </label>
            <div class="flex items-center gap-4">
              <div v-if="selectedImage" class="relative">
                <img
                  :src="selectedImage.url"
                  :alt="selectedImage.original_name"
                  class="h-24 w-24 object-cover rounded-lg border border-gray-300"
                />
                <button
                  type="button"
                  @click="removeImage"
                  class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
              <button
                type="button"
                @click="showMediaSelector = true"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                {{ selectedImage ? 'Изменить изображение' : 'Выбрать изображение' }}
              </button>
            </div>
          </div>

          <div>
            <label class="flex items-center gap-2">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">Активна</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex justify-end gap-3">
        <router-link
          to="/admin/categories"
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

    <!-- Media Selector -->
    <MediaSelector
      :open="showMediaSelector"
      :multiple="false"
      :allowedTypes="['photo']"
      :currentSelection="selectedImage ? [selectedImage] : []"
      @close="showMediaSelector = false"
      @select="handleImageSelect"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import MediaSelector from '@/components/admin/MediaSelector.vue';
import { useShopStore } from '@/stores/shop';

const router = useRouter();
const route = useRoute();
const shopStore = useShopStore();
const isEditMode = computed(() => !!route.params.id);

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const errors = ref({});
const showMediaSelector = ref(false);
const selectedImage = ref(null);
const parentCategories = ref([]);

const form = ref({
  name: '',
  slug: '',
  description: '',
  parent_id: null,
  image_id: null,
  position: 0,
  is_active: true,
});

const fetchCategory = async () => {
  if (!isEditMode.value) return;

  loading.value = true;
  error.value = null;

  try {
    const response = await apiClient.get(`/admin/categories/${route.params.id}`);
    const category = response.data;
    
    form.value = {
      name: category.name || '',
      slug: category.slug || '',
      description: category.description || '',
      parent_id: category.parent_id || null,
      image_id: category.image_id || null,
      position: category.position || 0,
      is_active: category.is_active !== undefined ? category.is_active : true,
    };

    if (category.image) {
      selectedImage.value = category.image;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить категорию';
    console.error('Error fetching category:', err);
  } finally {
    loading.value = false;
  }
};

const fetchParentCategories = async () => {
  try {
    const response = await apiClient.get('/admin/categories', { 
      params: { per_page: 1000 } 
    });
    const allCategories = response.data.data || response.data;
    // Исключаем текущую категорию из списка родительских (чтобы избежать циклических ссылок)
    if (isEditMode.value) {
      parentCategories.value = allCategories.filter(cat => cat.id !== parseInt(route.params.id));
    } else {
      parentCategories.value = allCategories;
    }
  } catch (err) {
    console.error('Error fetching parent categories:', err);
  }
};

const handleImageSelect = (image) => {
  selectedImage.value = image;
  form.value.image_id = image.id;
  showMediaSelector.value = false;
};

const removeImage = () => {
  selectedImage.value = null;
  form.value.image_id = null;
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;
  errors.value = {};

  try {
    const data = { ...form.value };
    
    // Если slug пустой, не отправляем его (сервер сгенерирует автоматически)
    if (!data.slug) {
      delete data.slug;
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
      await apiClient.put(`/admin/categories/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/categories', data);
    }

    router.push('/admin/categories');
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
  fetchParentCategories();
  if (isEditMode.value) {
    fetchCategory();
  }
});
</script>
