import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import '../css/app.css';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Восстанавливаем сессию при загрузке приложения, если есть токен
const authStore = useAuthStore();
const token = localStorage.getItem('auth_token');
if (token) {
  // Пытаемся восстановить пользователя
  authStore.fetchUser().catch(() => {
    // Если токен невалидный, он будет удален в fetchUser
  });
}

app.mount('#app');
