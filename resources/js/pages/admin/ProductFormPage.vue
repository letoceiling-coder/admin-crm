<template>
  <div class="product-form-page">
    <div class="mb-6">
      <router-link to="/admin/products" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку товаров
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование товара' : 'Создание товара' }}
      </h1>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Основная информация -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Основная информация</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Артикул (SKU)</label>
            <input v-model="form.sku" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
          <textarea v-model="form.description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
      </div>

      <!-- Категория и единица измерения -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Классификация</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
            <select v-model="form.category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
              <option :value="null">Без категории</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Единица измерения</label>
            <select v-model="form.unit_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
              <option :value="null">Не выбрано</option>
              <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }} ({{ unit.short_name }})</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Позиция</label>
            <input v-model.number="form.position" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
      </div>

      <!-- Цена и остаток -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Цена и остаток</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
            <input v-model.number="form.price" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Остаток</label>
            <input v-model.number="form.stock" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
      </div>

      <!-- Вес и питательные вещества -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Вес и питательные вещества</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Вес порции (гр)</label>
            <input v-model.number="form.weight" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Калорийность (ккал)</label>
            <input v-model.number="form.calories" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Белки (гр)</label>
            <input v-model.number="form.protein" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Жиры (гр)</label>
            <input v-model.number="form.fat" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Углеводы (гр)</label>
            <input v-model.number="form.carbs" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
      </div>

      <!-- Изображения -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Изображения</h2>
        
        <!-- Главное изображение -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Главное изображение</label>
          <div class="flex items-center gap-4">
            <img v-if="mainImage" :src="mainImage.url" :alt="mainImage.original_name" class="h-32 w-32 object-cover rounded-lg border border-gray-300" />
            <div class="flex gap-2">
              <button type="button" @click="showMainImageSelector = true" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                {{ mainImage ? 'Изменить' : 'Выбрать' }}
              </button>
              <button v-if="mainImage" type="button" @click="removeMainImage" class="px-4 py-2 text-red-600 hover:text-red-800">
                Удалить
              </button>
            </div>
          </div>
        </div>

        <!-- Дополнительные изображения -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">Дополнительные изображения</label>
            <button type="button" @click="showImagesSelector = true" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
              Добавить изображения
            </button>
          </div>
          <div v-if="selectedImages.length === 0" class="text-gray-500 text-sm py-4">Изображения не выбраны</div>
          <div v-else class="grid grid-cols-4 gap-4">
            <div v-for="(img, index) in selectedImages" :key="img.id" class="relative group">
              <img :src="img.url" :alt="img.original_name" class="w-full h-32 object-cover rounded-lg border border-gray-300" />
              <button
                type="button"
                @click="removeImage(index)"
                class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Статус -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <label class="flex items-center gap-2">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          <span class="text-sm font-medium text-gray-700">Активен</span>
        </label>
      </div>

      <!-- Кнопки -->
      <div class="flex justify-end gap-3">
        <router-link to="/admin/products" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</router-link>
        <button type="submit" :disabled="submitting" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
          <div v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          {{ isEditMode ? 'Сохранить' : 'Создать' }}
        </button>
      </div>
    </form>

    <!-- Media Selectors -->
    <MediaSelector
      :open="showMainImageSelector"
      :multiple="false"
      :allowedTypes="['photo']"
      :currentSelection="mainImage ? [mainImage] : []"
      @close="showMainImageSelector = false"
      @select="handleMainImageSelect"
    />
    <MediaSelector
      :open="showImagesSelector"
      :multiple="true"
      :allowedTypes="['photo']"
      :currentSelection="selectedImages"
      @close="showImagesSelector = false"
      @select="handleImagesSelect"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import MediaSelector from '@/components/admin/MediaSelector.vue';
import { useShopStore } from '@/stores/shop';

const router = useRouter();
const route = useRoute();
const isEditMode = computed(() => !!route.params.id);

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const errors = ref({});
const showMainImageSelector = ref(false);
const showImagesSelector = ref(false);
const mainImage = ref(null);
const selectedImages = ref([]);
const categories = ref([]);
const units = ref([]);

const form = ref({
  name: '',
  slug: '',
  description: '',
  sku: '',
  price: 0,
  weight: null,
  protein: null,
  fat: null,
  carbs: null,
  calories: null,
  category_id: null,
  unit_id: null,
  image_id: null,
  stock: 0,
  position: 0,
  is_active: true,
  images: [],
});

const fetchProduct = async () => {
  if (!isEditMode.value) return;
  loading.value = true;
  try {
    const response = await apiClient.get(`/admin/products/${route.params.id}`);
    const product = response.data;
    form.value = {
      name: product.name || '',
      slug: product.slug || '',
      description: product.description || '',
      sku: product.sku || '',
      price: product.price || 0,
      weight: product.weight || null,
      protein: product.protein || null,
      fat: product.fat || null,
      carbs: product.carbs || null,
      calories: product.calories || null,
      category_id: product.category_id || null,
      unit_id: product.unit_id || null,
      image_id: product.image_id || null,
      stock: product.stock || 0,
      position: product.position || 0,
      is_active: product.is_active !== undefined ? product.is_active : true,
      images: [],
    };
    if (product.image) mainImage.value = product.image;
    if (product.images && product.images.length > 0) {
      selectedImages.value = product.images;
      form.value.images = product.images.map(img => img.id);
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить товар';
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await apiClient.get('/admin/categories', { params: { per_page: 1000 } });
    categories.value = response.data.data || response.data;
  } catch (err) {
    console.error('Error fetching categories:', err);
  }
};

const fetchUnits = async () => {
  try {
    const response = await apiClient.get('/admin/units', { params: { per_page: 1000 } });
    units.value = response.data.data || response.data;
  } catch (err) {
    console.error('Error fetching units:', err);
  }
};

const handleMainImageSelect = (image) => {
  mainImage.value = image;
  form.value.image_id = image.id;
  showMainImageSelector.value = false;
};

const removeMainImage = () => {
  mainImage.value = null;
  form.value.image_id = null;
};

const handleImagesSelect = (images) => {
  const imageArray = Array.isArray(images) ? images : [images];
  selectedImages.value = imageArray;
  form.value.images = imageArray.map(img => img.id);
  showImagesSelector.value = false;
};

const removeImage = (index) => {
  selectedImages.value.splice(index, 1);
  form.value.images = selectedImages.value.map(img => img.id);
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;
  errors.value = {};
  try {
    const data = { ...form.value };
    if (!data.slug) delete data.slug;
    
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
      await apiClient.put(`/admin/products/${route.params.id}`, data);
    } else {
      await apiClient.post('/admin/products', data);
    }
    router.push('/admin/products');
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Произошла ошибка при сохранении';
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchCategories();
  fetchUnits();
  if (isEditMode.value) {
    fetchProduct();
  }
});
</script>
