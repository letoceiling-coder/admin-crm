<template>
  <div class="categories-page">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Категории</h1>
        <p class="text-gray-600 mt-1">Управление категориями товаров</p>
      </div>
      <router-link
        to="/admin/categories/create"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Создать категорию
      </router-link>
    </div>

    <!-- Фильтры и поиск -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Поиск -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Название, slug..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="debounceSearch"
          />
        </div>

        <!-- Фильтр по родительской категории -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Родительская категория</label>
          <select
            v-model="filters.parent_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchCategories"
          >
            <option value="">Все</option>
            <option value="null">Без родителя</option>
            <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- Сортировка -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сортировка</label>
          <select
            v-model="filters.sort_by"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchCategories"
          >
            <option value="position">По позиции</option>
            <option value="name">По названию</option>
            <option value="created_at">По дате создания</option>
          </select>
        </div>

        <!-- Количество на странице -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">На странице</label>
          <select
            v-model="filters.per_page"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchCategories"
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
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-red-800">{{ error }}</p>
      </div>
    </div>

    <div v-else-if="categories.length === 0" class="bg-white rounded-lg border border-gray-200 p-12 text-center">
      <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
      </svg>
      <p class="text-gray-600 mb-4">Категории не найдены</p>
      <router-link
        to="/admin/categories/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        Создать первую категорию
      </router-link>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                #
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Название
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Родитель
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Изображение
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Статус
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200" id="categories-list">
            <tr
              v-for="(category, index) in categories"
              :key="category.id"
              :data-id="category.id"
              :draggable="true"
              @dragstart="handleDragStart($event, category, index)"
              @dragover.prevent="handleDragOver($event, index)"
              @drop="handleDrop($event, category, index)"
              @dragend="handleDragEnd"
              class="hover:bg-gray-50 cursor-move"
              :class="{ 'opacity-50': draggingIndex === index }"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                  </svg>
                  <span class="text-sm text-gray-500">{{ category.position }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                <div class="text-sm text-gray-500">{{ category.slug }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-500">{{ category.parent?.name || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <img
                  v-if="category.image?.url"
                  :src="category.image.url"
                  :alt="category.name"
                  class="h-10 w-10 object-cover rounded"
                />
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ category.is_active ? 'Активна' : 'Неактивна' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    :to="`/admin/categories/${category.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                    title="Редактировать"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </router-link>
                  <button
                    @click="confirmDelete(category)"
                    class="text-red-600 hover:text-red-900"
                    title="Удалить"
                  >
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
        <div class="text-sm text-gray-700">
          Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
        </div>
        <div class="flex gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Назад
          </button>
          <span class="px-3 py-2 text-sm text-gray-700">
            Страница {{ pagination.current_page }} из {{ pagination.last_page }}
          </span>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Вперед
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div
      v-if="categoryToDelete"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="categoryToDelete = null"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Подтверждение удаления</h3>
        <p class="text-gray-600 mb-6">
          Вы уверены, что хотите удалить категорию "{{ categoryToDelete.name }}"? Это действие нельзя отменить.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="categoryToDelete = null"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Отмена
          </button>
          <button
            @click="deleteCategory"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/axios';

const router = useRouter();
const categories = ref([]);
const parentCategories = ref([]);
const loading = ref(false);
const error = ref(null);
const categoryToDelete = ref(null);
const pagination = ref(null);
const searchTimeout = ref(null);

// Drag & Drop
const draggingIndex = ref(null);
const draggedCategory = ref(null);

const filters = ref({
  search: '',
  parent_id: '',
  sort_by: 'position',
  sort_order: 'asc',
  per_page: 20,
});

const fetchCategories = async (page = 1) => {
  loading.value = true;
  error.value = null;

  try {
    const params = {
      page,
      per_page: filters.value.per_page,
      sort_by: filters.value.sort_by,
      sort_order: filters.value.sort_order,
    };

    if (filters.value.search) {
      params.search = filters.value.search;
    }

    if (filters.value.parent_id) {
      params.parent_id = filters.value.parent_id === 'null' ? null : filters.value.parent_id;
    }

    const response = await apiClient.get('/admin/categories', { params });
    categories.value = response.data.data || response.data;
    
    if (response.data.meta) {
      pagination.value = {
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        from: response.data.meta.from,
        to: response.data.meta.to,
        total: response.data.meta.total,
      };
    } else {
      pagination.value = null;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить список категорий';
    console.error('Error fetching categories:', err);
  } finally {
    loading.value = false;
  }
};

const fetchParentCategories = async () => {
  try {
    const response = await apiClient.get('/admin/categories', { params: { per_page: 1000 } });
    parentCategories.value = response.data.data || response.data;
  } catch (err) {
    console.error('Error fetching parent categories:', err);
  }
};

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    fetchCategories(1);
  }, 500);
};

const changePage = (page) => {
  if (page >= 1 && (!pagination.value || page <= pagination.value.last_page)) {
    fetchCategories(page);
  }
};

// Drag & Drop
const handleDragStart = (event, category, index) => {
  draggedCategory.value = category;
  draggingIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/html', event.target);
};

const handleDragOver = (event, index) => {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
};

const handleDrop = async (event, targetCategory, targetIndex) => {
  event.preventDefault();
  
  if (!draggedCategory.value || draggedCategory.value.id === targetCategory.id) {
    return;
  }

  // Обновляем позиции локально
  const newCategories = [...categories.value];
  const draggedIndex = newCategories.findIndex(c => c.id === draggedCategory.value.id);
  
  if (draggedIndex === -1) return;

  // Перемещаем элемент
  const [removed] = newCategories.splice(draggedIndex, 1);
  newCategories.splice(targetIndex, 0, removed);

  // Обновляем позиции
  const positions = newCategories.map((cat, idx) => ({
    id: cat.id,
    position: idx + 1,
  }));

  try {
    await apiClient.post('/admin/categories/update-positions', { categories: positions });
    categories.value = newCategories;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось обновить позиции';
    await fetchCategories(pagination.value?.current_page || 1);
  }
};

const handleDragEnd = () => {
  draggingIndex.value = null;
  draggedCategory.value = null;
};

const confirmDelete = (category) => {
  categoryToDelete.value = category;
};

const deleteCategory = async () => {
  if (!categoryToDelete.value) return;

  try {
    await apiClient.delete(`/admin/categories/${categoryToDelete.value.id}`);
    categories.value = categories.value.filter(c => c.id !== categoryToDelete.value.id);
    categoryToDelete.value = null;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось удалить категорию';
    console.error('Error deleting category:', err);
  }
};

onMounted(() => {
  fetchCategories();
  fetchParentCategories();
});
</script>
