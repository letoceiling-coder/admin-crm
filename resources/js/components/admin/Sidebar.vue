<template>
  <aside
    class="relative flex flex-col bg-gray-900 text-white transition-all duration-300 border-r border-gray-800"
    :class="[
      'lg:flex',
      isMobileMenuOpen ? 'flex' : 'hidden',
      'lg:relative fixed lg:inset-auto inset-y-0 left-0 z-50 lg:z-auto',
      isCollapsed ? 'lg:w-16 w-72' : 'lg:w-72 w-72',
      'lg:translate-x-0 transition-transform duration-300 ease-in-out',
      isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
  >
    <div class="flex h-16 items-center border-b border-gray-800 justify-between px-6">
      <h1 v-if="!isCollapsed" class="text-xl font-bold text-white">CMS Admin</h1>
      <button
        @click="toggleCollapse"
        class="rounded-xl p-2 hover:bg-gray-800 transition-all"
        :title="isCollapsed ? 'Развернуть меню' : 'Свернуть меню'"
      >
        <svg
          class="h-5 w-5 transition-transform duration-300"
          :class="isCollapsed ? 'rotate-180' : ''"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto space-y-1 p-4">
      <router-link
        to="/admin/dashboard"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.dashboard' 
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        <span v-if="!isCollapsed">Панель управления</span>
      </router-link>
      <router-link
        to="/admin/shops"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.shops.index' || route.name === 'admin.shops.create' || route.name === 'admin.shops.edit'
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <span v-if="!isCollapsed">Магазины</span>
      </router-link>
      <router-link
        to="/admin/subscription"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.subscription' 
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <span v-if="!isCollapsed">Подписка</span>
      </router-link>
      <!-- Каталог -->
      <div class="space-y-1">
        <button
          @click="toggleCatalogMenu"
          class="w-full flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
          :class="[
            isCollapsed ? 'justify-center' : '',
            isCatalogMenuOpen || isCatalogRoute
              ? 'bg-gray-800 text-white' 
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'
          ]"
        >
          <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
          </svg>
          <span v-if="!isCollapsed" class="flex-1 text-left">Каталог</span>
          <svg
            v-if="!isCollapsed"
            class="h-4 w-4 transition-transform"
            :class="{ 'rotate-180': isCatalogMenuOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div
          v-if="!isCollapsed && isCatalogMenuOpen"
          class="ml-4 space-y-1 pl-4 border-l-2 border-gray-700"
        >
          <router-link
            to="/admin/categories"
            class="flex items-center rounded-lg text-sm font-medium transition-all px-4 py-2 gap-3"
            :class="[
              route.name === 'admin.categories.index' || route.name === 'admin.categories.create' || route.name === 'admin.categories.edit'
                ? 'bg-gray-700 text-white' 
                : 'text-gray-400 hover:bg-gray-800 hover:text-white'
            ]"
            @click="handleMobileMenuClick"
          >
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <span>Категории</span>
          </router-link>
          <router-link
            to="/admin/products"
            class="flex items-center rounded-lg text-sm font-medium transition-all px-4 py-2 gap-3"
            :class="[
              route.name === 'admin.products.index' || route.name === 'admin.products.create' || route.name === 'admin.products.edit'
                ? 'bg-gray-700 text-white' 
                : 'text-gray-400 hover:bg-gray-800 hover:text-white'
            ]"
            @click="handleMobileMenuClick"
          >
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <span>Товары</span>
          </router-link>
        </div>
      </div>
      <router-link
        to="/admin/units"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.units.index' || route.name === 'admin.units.create' || route.name === 'admin.units.edit'
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
        <span v-if="!isCollapsed">Единицы измерения</span>
      </router-link>
      <router-link
        to="/admin/media"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.media' 
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span v-if="!isCollapsed">Медиа</span>
      </router-link>
      <router-link
        to="/admin/settings"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.settings' 
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span v-if="!isCollapsed">Настройки</span>
      </router-link>
      <router-link
        to="/admin/payment-methods"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.payment-methods'
            ? 'bg-gray-800 text-white'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <span v-if="!isCollapsed">Способы оплаты</span>
      </router-link>
      <!-- Заказы, Доставки, Платежи -->
      <router-link
        to="/admin/orders"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.orders.index'
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        <span v-if="!isCollapsed">Заказы</span>
      </router-link>
      <router-link
        to="/admin/deliveries"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.deliveries.index'
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <span v-if="!isCollapsed">Доставки</span>
      </router-link>
      <router-link
        to="/admin/payments"
        class="flex items-center rounded-xl text-sm font-medium transition-all px-4 py-3 gap-3"
        :class="[
          isCollapsed ? 'justify-center' : '',
          route.name === 'admin.payments.index'
            ? 'bg-gray-800 text-white' 
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
        ]"
        @click="handleMobileMenuClick"
      >
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <span v-if="!isCollapsed">Платежи</span>
      </router-link>
    </nav>
    <div class="border-t border-gray-800 space-y-3 p-4">
      <div class="flex items-center gap-3 px-2" :class="isCollapsed ? 'justify-center' : ''">
        <div class="h-10 w-10 rounded-full bg-blue-600 border border-blue-500 flex items-center justify-center text-sm font-bold text-white shrink-0">
          {{ userInitials }}
        </div>
        <div v-if="!isCollapsed" class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white">{{ user?.name || 'Пользователь' }}</p>
          <p class="text-xs text-gray-400 truncate">{{ user?.email || '' }}</p>
        </div>
      </div>
      <button
        @click="handleLogout"
        class="w-full flex justify-start gap-2 px-4 py-2 text-gray-400 hover:text-white rounded-md hover:bg-gray-800"
        :class="isCollapsed ? 'justify-center' : ''"
        :title="isCollapsed ? 'Выйти' : ''"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
        <span v-if="!isCollapsed">Выйти</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const mobileMenu = inject('mobileMenu', null);

const isCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');
const isMobileMenuOpen = computed(() => mobileMenu?.isOpen?.value ?? false);
const isCatalogMenuOpen = ref(localStorage.getItem('catalogMenuOpen') === 'true');

const user = computed(() => authStore.user);

const isCatalogRoute = computed(() => {
  const catalogRoutes = [
    'admin.categories.index',
    'admin.categories.create',
    'admin.categories.edit',
    'admin.products.index',
    'admin.products.create',
    'admin.products.edit',
  ];
  return catalogRoutes.includes(route.name);
});

const toggleCatalogMenu = () => {
  if (isCollapsed.value) return;
  isCatalogMenuOpen.value = !isCatalogMenuOpen.value;
  localStorage.setItem('catalogMenuOpen', isCatalogMenuOpen.value.toString());
};

const userInitials = computed(() => {
  if (!user.value?.name) return 'U';
  const names = user.value.name.split(' ');
  return names.map(n => n[0]).join('').toUpperCase().substring(0, 2);
});

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('sidebarCollapsed', isCollapsed.value.toString());
};

const handleLogout = async () => {
  await authStore.logout();
};

const handleMobileMenuClick = () => {
  if (mobileMenu && window.innerWidth < 1024) {
    mobileMenu.close();
  }
};

// Автоматически открываем меню каталога если мы на странице каталога
if (isCatalogRoute.value && !isCatalogMenuOpen.value) {
  isCatalogMenuOpen.value = true;
  localStorage.setItem('catalogMenuOpen', 'true');
}
</script>
