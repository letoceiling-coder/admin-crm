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

const user = computed(() => authStore.user);

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
</script>
