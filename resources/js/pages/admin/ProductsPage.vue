<template>
  <div class="products-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Товары</h1>
        <p class="text-gray-600 mt-1">Управление товарами</p>
      </div>
      <router-link
        to="/admin/products/create"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Создать товар
      </router-link>
    </div>

    <!-- Фильтры и поиск -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Название, артикул..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="debounceSearch"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
          <select
            v-model="filters.category_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchProducts"
          >
            <option value="">Все</option>
            <option value="null">Без категории</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сортировка</label>
          <select
            v-model="filters.sort_by"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchProducts"
          >
            <option value="position">По позиции</option>
            <option value="name">По названию</option>
            <option value="price">По цене</option>
            <option value="created_at">По дате</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Порядок</label>
          <select
            v-model="filters.sort_order"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchProducts"
          >
            <option value="asc">По возрастанию</option>
            <option value="desc">По убыванию</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
          <select
            v-model="filters.per_page"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchProducts"
          >
            <option :value="20">20</option>
            <option :value="30">30</option>
            <option :value="40">40</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <div v-else-if="products.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <p class="text-gray-600 mb-4">Товары не найдены</p>
      <router-link to="/admin/products/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Создать первый товар
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Товар</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Категория</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Остаток</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200" id="products-list">
            <tr
              v-for="(product, index) in products"
              :key="product.id"
              :data-id="product.id"
              :draggable="true"
              @dragstart="handleDragStart($event, product, index)"
              @dragover.prevent="handleDragOver($event, index)"
              @drop="handleDrop($event, product, index)"
              @dragend="handleDragEnd"
              class="hover:bg-gray-50 cursor-move"
              :class="{ 'opacity-50': draggingIndex === index }"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                  </svg>
                  <span class="text-sm text-gray-500">{{ product.position }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="product.image?.url"
                    :src="product.image.url"
                    :alt="product.name"
                    class="h-12 w-12 object-cover rounded"
                  />
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ product.name }}</div>
                    <div class="text-sm text-gray-500">{{ product.sku || '-' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ product.category?.name || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ formatPrice(product.price) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ product.stock }} {{ product.unit?.short_name || '' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <router-link :to="`/admin/products/${product.id}/edit`" class="text-blue-600 hover:text-blue-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </router-link>
                  <button @click="confirmDelete(product)" class="text-red-600 hover:text-red-900">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Пагинация -->
      <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}</div>
        <div class="flex gap-2">
          <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Назад</button>
          <span class="px-3 py-2 text-sm text-gray-700">Страница {{ pagination.current_page }} из {{ pagination.last_page }}</span>
          <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Вперед</button>
        </div>
      </div>
    </div>

    <!-- Модальное окно удаления -->
    <div v-if="productToDelete" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="productToDelete = null">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Подтверждение удаления</h3>
        <p class="text-gray-600 mb-6">Вы уверены, что хотите удалить товар "{{ productToDelete.name }}"?</p>
        <div class="flex justify-end gap-3">
          <button @click="productToDelete = null" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</button>
          <button @click="deleteProduct" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Удалить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/axios';

const router = useRouter();
const products = ref([]);
const categories = ref([]);
const loading = ref(false);
const error = ref(null);
const productToDelete = ref(null);
const pagination = ref(null);
const searchTimeout = ref(null);
const draggingIndex = ref(null);
const draggedProduct = ref(null);

const filters = ref({
  search: '',
  category_id: '',
  sort_by: 'position',
  sort_order: 'asc',
  per_page: 20,
});

const fetchProducts = async (page = 1) => {
  loading.value = true;
  error.value = null;
  try {
    const params = { page, per_page: filters.value.per_page, sort_by: filters.value.sort_by, sort_order: filters.value.sort_order };
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.category_id) params.category_id = filters.value.category_id === 'null' ? null : filters.value.category_id;
    const response = await apiClient.get('/admin/products', { params });
    products.value = response.data.data || response.data;
    if (response.data.meta) {
      pagination.value = {
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        from: response.data.meta.from,
        to: response.data.meta.to,
        total: response.data.meta.total,
      };
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить список товаров';
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

const debounceSearch = () => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => fetchProducts(1), 500);
};

const changePage = (page) => {
  if (page >= 1 && (!pagination.value || page <= pagination.value.last_page)) {
    fetchProducts(page);
  }
};

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(price || 0);
};

const handleDragStart = (event, product, index) => {
  draggedProduct.value = product;
  draggingIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
};

const handleDragOver = (event) => {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
};

const handleDrop = async (event, targetProduct, targetIndex) => {
  event.preventDefault();
  if (!draggedProduct.value || draggedProduct.value.id === targetProduct.id) return;
  const newProducts = [...products.value];
  const draggedIndex = newProducts.findIndex(p => p.id === draggedProduct.value.id);
  if (draggedIndex === -1) return;
  const [removed] = newProducts.splice(draggedIndex, 1);
  newProducts.splice(targetIndex, 0, removed);
  const positions = newProducts.map((p, idx) => ({ id: p.id, position: idx + 1 }));
  try {
    await apiClient.post('/admin/products/update-positions', { products: positions });
    products.value = newProducts;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось обновить позиции';
    await fetchProducts(pagination.value?.current_page || 1);
  }
};

const handleDragEnd = () => {
  draggingIndex.value = null;
  draggedProduct.value = null;
};

const confirmDelete = (product) => {
  productToDelete.value = product;
};

const deleteProduct = async () => {
  if (!productToDelete.value) return;
  try {
    await apiClient.delete(`/admin/products/${productToDelete.value.id}`);
    products.value = products.value.filter(p => p.id !== productToDelete.value.id);
    productToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось удалить товар';
  }
};

onMounted(() => {
  fetchProducts();
  fetchCategories();
});
</script>
