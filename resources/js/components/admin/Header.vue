<template>
  <header class="relative flex h-16 items-center justify-between border-b border-gray-200 bg-white backdrop-blur-xl px-4 sm:px-6 gap-2 sm:gap-4 z-30">
    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
      <button
        @click="toggleMobileMenu"
        class="lg:hidden flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-md hover:bg-gray-100 transition-colors"
        aria-label="Открыть меню"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
      <div class="hidden sm:flex items-center gap-2 text-sm min-w-0">
        <span class="font-semibold text-gray-900 truncate">{{ currentPageTitle }}</span>
      </div>
      <div class="flex sm:hidden items-center text-sm min-w-0">
        <span class="font-semibold text-gray-900 truncate">{{ currentPageTitle }}</span>
      </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
      <!-- Селект магазина -->
      <div v-if="shops.length > 0" class="hidden sm:block">
        <select
          v-model="selectedShopId"
          @change="handleShopChange"
          class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors min-w-[180px]"
        >
          <option :value="null">Выберите магазин</option>
          <option v-for="shop in shops" :key="shop.id" :value="shop.id">
            {{ shop.name }}
          </option>
        </select>
      </div>
      <!-- Мобильная версия селекта -->
      <div v-if="shops.length > 0" class="sm:hidden">
        <select
          v-model="selectedShopId"
          @change="handleShopChange"
          class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors max-w-[120px]"
        >
          <option :value="null">Магазин</option>
          <option v-for="shop in shops" :key="shop.id" :value="shop.id">
            {{ shop.name.length > 15 ? shop.name.substring(0, 15) + '...' : shop.name }}
          </option>
        </select>
      </div>
      <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-blue-600 border border-blue-500 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
        {{ userInitials }}
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, inject, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useShopStore } from '@/stores/shop';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const shopStore = useShopStore();
const mobileMenu = inject('mobileMenu', null);

const user = computed(() => authStore.user);
const shops = computed(() => shopStore.shops);
const selectedShopId = computed({
  get: () => shopStore.selectedShopId,
  set: (value) => shopStore.setSelectedShop(value)
});

const userInitials = computed(() => {
  if (!user.value?.name) return 'U';
  const names = user.value.name.split(' ');
  return names.map(n => n[0]).join('').toUpperCase().substring(0, 2);
});

const currentPageTitle = computed(() => {
  return route.meta?.title || 'Панель управления';
});

const toggleMobileMenu = () => {
  if (mobileMenu) {
    mobileMenu.toggle();
  }
};

const handleShopChange = () => {
  // При смене магазина перезагружаем текущую страницу для обновления данных
  const currentPath = route.path;
  router.push({ path: currentPath, query: { ...route.query, _t: Date.now() } });
};

// Загружаем магазины при монтировании компонента
onMounted(async () => {
  if (authStore.isAuthenticated) {
    await shopStore.fetchShops();
  }
});

// Следим за изменением авторизации
watch(() => authStore.isAuthenticated, async (isAuthenticated) => {
  if (isAuthenticated) {
    await shopStore.fetchShops();
  } else {
    shopStore.clearSelectedShop();
  }
});
</script>
