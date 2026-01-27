<template>
  <div class="unit-form-page">
    <div class="mb-6">
      <router-link to="/admin/units" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку единиц измерения
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        {{ isEditMode ? 'Редактирование единицы измерения' : 'Создание единицы измерения' }}
      </h1>
    </div>

    <div v-if="loading && isEditMode" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Основная информация</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Например: Килограмм" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Краткое название <span class="text-red-500">*</span></label>
            <input v-model="form.short_name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Например: кг" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Позиция</label>
            <input v-model.number="form.position" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
              <span class="text-sm font-medium text-gray-700">Активна</span>
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <router-link to="/admin/units" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</router-link>
        <button type="submit" :disabled="submitting" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
          <div v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          {{ isEditMode ? 'Сохранить' : 'Создать' }}
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

const loading = ref(false);
const submitting = ref(false);
const error = ref(null);
const errors = ref({});

const form = ref({
  name: '',
  short_name: '',
  position: 0,
  is_active: true,
});

const fetchUnit = async () => {
  if (!isEditMode.value) return;
  loading.value = true;
  try {
    const response = await apiClient.get(`/admin/units/${route.params.id}`);
    const unit = response.data;
    form.value = {
      name: unit.name || '',
      short_name: unit.short_name || '',
      position: unit.position || 0,
      is_active: unit.is_active !== undefined ? unit.is_active : true,
    };
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить единицу измерения';
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;
  errors.value = {};
  try {
    if (isEditMode.value) {
      await apiClient.put(`/admin/units/${route.params.id}`, form.value);
    } else {
      await apiClient.post('/admin/units', form.value);
    }
    router.push('/admin/units');
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
  if (isEditMode.value) {
    fetchUnit();
  }
});
</script>
