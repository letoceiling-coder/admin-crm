<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md space-y-6">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Регистрация</h1>
        <p class="text-gray-600 mt-2">Создайте новый аккаунт</p>
      </div>
      <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              Имя
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              placeholder="Иван Иванов"
            />
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
              Пароль
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              placeholder="Минимум 8 символов"
            />
            <p class="mt-1 text-xs text-gray-500">
              Пароль должен содержать буквы разного регистра, цифры и символы
            </p>
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
            <span v-if="!loading">Зарегистрироваться</span>
            <span v-else>Регистрация...</span>
          </button>
        </form>
      </div>
      <p class="text-center text-sm text-gray-600">
        Уже есть аккаунт?
        <router-link to="/admin/login" class="text-blue-600 hover:underline">
          Войти
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Swal from 'sweetalert2';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const loading = computed(() => authStore.loading);
const error = computed(() => authStore.error);

// Очищаем ошибки при монтировании компонента
onMounted(() => {
  authStore.clearError();
});

const handleSubmit = async () => {
  const result = await authStore.register(form.value);
  
  if (result.success) {
    // Успешная регистрация - редирект в админ панель
    router.push('/admin/dashboard');
  } else {
    // Ошибка регистрации - показываем SweetAlert2
    const errorMessage = result.error || 'Произошла ошибка при регистрации';
    const errorDetails = result.details || 'Не удалось завершить регистрацию. Пожалуйста, попробуйте снова.';
    
    // Определяем, является ли это ошибкой интеграции с ADMIN
    const isAdminApiError = errorMessage.includes('администрации') || 
                           errorMessage.includes('ADMIN') || 
                           errorMessage.includes('сервер');
    
    await Swal.fire({
      icon: 'error',
      title: 'Ошибка регистрации',
      html: `
        <div class="text-left">
          <p class="mb-3"><strong>${errorMessage}</strong></p>
          <p class="mb-3">${errorDetails}</p>
          ${isAdminApiError ? `
            <p class="text-sm text-gray-600 mt-3">Возможные причины:</p>
            <ul class="text-sm text-gray-600 mt-2 list-disc list-inside">
              <li>Проблемы с подключением к серверу администрации</li>
              <li>Временная недоступность сервиса</li>
              <li>Ошибка при создании заявки на подписку</li>
            </ul>
            <p class="text-sm text-blue-600 mt-3 font-medium">Рекомендация: Попробуйте зарегистрироваться снова через несколько секунд.</p>
          ` : ''}
        </div>
      `,
      confirmButtonText: 'Попробовать снова',
      confirmButtonColor: '#3b82f6',
      allowOutsideClick: false,
      allowEscapeKey: false,
    });
    
    // Очищаем форму для повторной попытки (сохраняем имя и email)
    form.value = {
      name: form.value.name,
      email: form.value.email,
      password: '',
      password_confirmation: '',
    };
  }
};
</script>
