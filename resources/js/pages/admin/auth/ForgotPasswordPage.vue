<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md space-y-6">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Восстановление пароля</h1>
        <p class="text-gray-600 mt-2">Введите ваш email для восстановления пароля</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div v-if="!success">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">
              {{ error }}
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
            <button
              type="submit"
              :disabled="loading"
              class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="!loading">Отправить ссылку</span>
              <span v-else>Отправка...</span>
            </button>
          </form>
        </div>
        <div v-else class="mt-4 space-y-4 text-center">
          <div class="rounded-md bg-blue-50 p-4">
            <p class="text-sm text-gray-900">
              Ссылка для восстановления пароля будет отправлена на указанный email
            </p>
          </div>
          <router-link
            to="/admin/login"
            class="block w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
          >
            Вернуться к входу
          </router-link>
        </div>
      </div>
      <p class="text-center text-sm text-gray-600">
        Вспомнили пароль?
        <router-link to="/admin/login" class="text-blue-600 hover:underline">
          Войти
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const form = ref({
  email: '',
});

const loading = computed(() => authStore.loading);
const error = computed(() => authStore.error);
const success = ref(false);

// Очищаем ошибки при монтировании компонента
onMounted(() => {
  authStore.clearError();
  success.value = false;
});

const handleSubmit = async () => {
  const result = await authStore.forgotPassword(form.value.email);
  
  if (result.success) {
    success.value = true;
  }
};
</script>
