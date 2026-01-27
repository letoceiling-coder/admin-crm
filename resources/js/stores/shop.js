import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '../api/axios';

export const useShopStore = defineStore('shop', () => {
  // State
  const selectedShopId = ref(localStorage.getItem('selectedShopId') ? parseInt(localStorage.getItem('selectedShopId')) : null);
  const shops = ref([]);
  const loading = ref(false);

  // Getters
  const selectedShop = computed(() => {
    return shops.value.find(shop => shop.id === selectedShopId.value) || null;
  });

  const hasSelectedShop = computed(() => selectedShopId.value !== null);

  // Actions
  /**
   * Загрузить список магазинов пользователя
   */
  const fetchShops = async () => {
    loading.value = true;
    try {
      const response = await apiClient.get('/admin/shops');
      shops.value = response.data || [];
      
      // Если магазин не выбран, выбираем первый доступный
      if (!selectedShopId.value && shops.value.length > 0) {
        setSelectedShop(shops.value[0].id);
      }
      
      return shops.value;
    } catch (error) {
      console.error('Error fetching shops:', error);
      return [];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Установить выбранный магазин
   */
  const setSelectedShop = (shopId) => {
    selectedShopId.value = shopId;
    if (shopId) {
      localStorage.setItem('selectedShopId', shopId.toString());
    } else {
      localStorage.removeItem('selectedShopId');
    }
  };

  /**
   * Очистить выбранный магазин
   */
  const clearSelectedShop = () => {
    selectedShopId.value = null;
    localStorage.removeItem('selectedShopId');
  };

  return {
    // State
    selectedShopId,
    shops,
    loading,
    // Getters
    selectedShop,
    hasSelectedShop,
    // Actions
    fetchShops,
    setSelectedShop,
    clearSelectedShop,
  };
});
