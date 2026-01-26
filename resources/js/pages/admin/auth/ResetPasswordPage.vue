<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md space-y-6">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Сброс пароля</h1>
        <p class="text-gray-600 mt-2">Введите новый пароль</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">
            {{ error }}
          </div>
          <div v-if="success" class="rounded-md bg-green-50 p-3 text-sm text-green-800">
            {{ success }}
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              Email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              placeholder="your@email.com"
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Новый пароль
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              placeholder="Минимум 8 символов"
            />
          </div>
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
              Подтверждение пароля
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              placeholder="Повторите пароль"
            />
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="!loading">Изменить пароль</span>
            <span v-else>Изменение...</span>
          </button>
        </form>
      </div>
      <p class="text-center text-sm text-gray-600">
        <router-link to="/admin/login" class="text-blue-600 hover:underline">
          Вернуться к входу
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
  password_confirmation: '',
  token: '',
});

const loading = computed(() => authStore.loading);
const error = computed(() => authStore.error);
const success = ref('');

onMounted(() => {
  authStore.clearError();
  form.value.token = route.query.token || '';
  form.value.email = route.query.email || '';
  success.value = '';
});

const handleSubmit = async () => {
  const result = await authStore.resetPassword(form.value);
  
  if (result.success) {
    success.value = 'Пароль успешно изменен';
    setTimeout(() => {
      router.push('/admin/login');
    }, 2000);
  }
};
</script>
