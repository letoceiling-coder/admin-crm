import { useState, useMemo, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { CategoryTabs } from '@/components/CategoryTabs';
import { ProductCard } from '@/components/ProductCard';
import { DeliveryModeToggle } from '@/components/DeliveryModeToggle';
import { DeliveryProgressIndicator } from '@/components/DeliveryProgressIndicator';
import { useCartStore } from '@/store/cartStore';
import { useTheme } from '@/contexts/ThemeContext';
import { ShoppingCart } from 'lucide-react';
import type { Product, Category } from '@/types';
import { categoryApi, productApi, shopApi, deliverySettingsApi } from '@/services/api';
import { useQuery } from '@tanstack/react-query';
import { cn } from '@/lib/utils';

export function CatalogPage() {
  const navigate = useNavigate();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { colors } = useTheme();
  const [activeCategory, setActiveCategory] = useState<number | null>(null);
  const [shopId, setShopId] = useState<number | null>(null);
  const totalItems = useCartStore((state) => state.getTotalItems());
  const totalAmount = useCartStore((state) => state.getTotalAmount());
  const setShopIdInCart = useCartStore((state) => state.setShopId);
  
  // Состояние выбора типа доставки
  const [orderMode, setOrderMode] = useState<'pickup' | 'delivery'>(() => {
    const saved = localStorage.getItem('orderMode');
    return (saved === 'delivery' || saved === 'pickup') ? saved : 'pickup';
  });

  // Сохранение orderMode в localStorage
  useEffect(() => {
    localStorage.setItem('orderMode', orderMode);
  }, [orderMode]);

  // Настройки доставки
  const [minDeliveryTotal, setMinDeliveryTotal] = useState<number>(3000);
  const [freeDeliveryThreshold, setFreeDeliveryThreshold] = useState<number | undefined>(undefined);
  
  // Загрузка настроек доставки
  useEffect(() => {
    if (!shopId) return;
    
    const loadSettings = async () => {
      try {
        const settings = await deliverySettingsApi.getSettings(shopId);
        
        // Загружаем минимальный заказ
        if (settings.min_delivery_order_total_rub !== undefined && settings.min_delivery_order_total_rub !== null) {
          const minTotal = Number(settings.min_delivery_order_total_rub);
          setMinDeliveryTotal(minTotal);
        }

        // Загружаем порог бесплатной доставки
        const thresholdValue = settings.free_delivery_threshold;
        // Используем значение из настроек, а не из состояния, чтобы избежать проблем с зависимостями
        const currentMinTotal = Number(settings.min_delivery_order_total_rub || 3000);
        
        if (thresholdValue !== undefined && thresholdValue !== null) {
          const threshold = Number(thresholdValue);
          
          // freeDeliveryThreshold должен быть положительным и строго больше минимального заказа
          if (!isNaN(threshold) && threshold > 0 && threshold > currentMinTotal) {
            setFreeDeliveryThreshold(threshold);
          } else {
            setFreeDeliveryThreshold(undefined);
          }
        } else {
          setFreeDeliveryThreshold(undefined);
        }
      } catch (error) {
        console.error('[CatalogPage] Error loading delivery settings:', error);
      }
    };

    loadSettings();
  }, [shopId]);

  // Загружаем категории
  const { data: categories = [], isLoading: categoriesLoading } = useQuery<Category[]>({
    queryKey: ['categories', shopId],
    queryFn: () => shopId ? categoryApi.getAll(shopId) : [],
    enabled: !!shopId,
  });

  // Загружаем товары
  const { data: products = [], isLoading: productsLoading } = useQuery<Product[]>({
    queryKey: ['products', shopId, activeCategory],
    queryFn: () => shopId ? productApi.getAll(shopId, activeCategory || undefined) : [],
    enabled: !!shopId,
  });

  // Получаем shopId из shopSlug
  useEffect(() => {
    if (shopSlug) {
      shopApi.getBySlug(shopSlug)
        .then((shop: any) => {
          setShopId(shop.id);
          setShopIdInCart(shop.id);
        })
        .catch((error: any) => {
          console.error('Ошибка загрузки магазина:', error);
        });
    }
  }, [shopSlug, setShopIdInCart]);

  const filteredProducts = useMemo(() => {
    if (!activeCategory) return products;
    return products.filter((product) => product.category_id === activeCategory);
  }, [activeCategory, products]);

  const groupedProducts = useMemo(() => {
    if (activeCategory) return null;
    const groups: { [key: number]: Product[] } = {};
    filteredProducts.forEach((product) => {
      const catId = product.category_id || 0;
      if (!groups[catId]) {
        groups[catId] = [];
      }
      groups[catId].push(product);
    });
    return groups;
  }, [activeCategory, filteredProducts]);

  const getCategoryName = (categoryId: number) => {
    return categories.find((c) => c.id === categoryId)?.name || '';
  };

  if (categoriesLoading || productsLoading || !shopId) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Загрузка...</p>
        </div>
      </div>
    );
  }

  return (
    <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient.light} dark:${colors.bgGradient.dark} pb-28`)}>
      <MiniAppHeader title="Каталог" />

      {/* Sticky Menu: Delivery Mode Toggle + Category Tabs */}
      <motion.div
        initial={{ y: -20, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        className={cn(`sticky top-16 z-30 bg-white/95 dark:bg-gray-950/95 backdrop-blur-xl border-b-2 ${colors.border.light} dark:${colors.border.dark}`)}
      >
        {/* Delivery Mode Toggle */}
        <DeliveryModeToggle value={orderMode} onChange={setOrderMode} />

        {/* Category Tabs */}
        <div className="flex items-center justify-between px-4 py-3">
          <CategoryTabs
            categories={categories}
            activeCategory={activeCategory}
            onCategoryChange={setActiveCategory}
          />
        </div>
      </motion.div>

      <div className="px-3 sm:px-4 pt-4">
        <AnimatePresence mode="wait">
          {activeCategory ? (
            <motion.div
              key="grid"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="grid grid-cols-2 gap-4"
            >
              {filteredProducts.map((product, index) => (
                <motion.div
                  key={product.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.03 }}
                >
                  <ProductCard
                    product={product}
                    onClick={() => navigate(`/${shopSlug}/product/${product.id}`)}
                  />
                </motion.div>
              ))}
            </motion.div>
          ) : (
            <motion.div
              key="grouped"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
            >
              {groupedProducts &&
                Object.entries(groupedProducts)
                  .sort(([a], [b]) => {
                    const catA = categories.find((c) => c.id === Number(a));
                    const catB = categories.find((c) => c.id === Number(b));
                    return (catA?.position || 0) - (catB?.position || 0);
                  })
                  .map(([categoryId, catProducts]) => (
                    <motion.div
                      key={categoryId}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      className="mb-10"
                    >
                      <div className="flex items-center justify-between py-5 px-3 mb-4 gap-3 max-w-full">
                        <h2 className="text-lg font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent flex-1 min-w-0 break-words">
                          {getCategoryName(Number(categoryId))}
                        </h2>
                        <motion.button
                          whileHover={{ scale: 1.05 }}
                          whileTap={{ scale: 0.95 }}
                          onClick={() => setActiveCategory(Number(categoryId))}
                          className="text-sm font-bold text-amber-600 dark:text-amber-400 hover:underline flex-shrink-0 whitespace-nowrap"
                        >
                          Показать все →
                        </motion.button>
                      </div>
                      <div className="grid grid-cols-2 gap-4">
                        {catProducts.slice(0, 4).map((product, index) => (
                          <motion.div
                            key={product.id}
                            initial={{ opacity: 0, scale: 0.9 }}
                            animate={{ opacity: 1, scale: 1 }}
                            transition={{ delay: index * 0.1 }}
                          >
                            <ProductCard
                              product={product}
                              onClick={() => navigate(`/${shopSlug}/product/${product.id}`)}
                            />
                          </motion.div>
                        ))}
                      </div>
                    </motion.div>
                  ))}
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Delivery Progress Indicator - показываем только если выбран режим доставки */}
      {orderMode === 'delivery' && (
        <DeliveryProgressIndicator
          cartTotal={totalAmount}
          minDeliveryTotal={minDeliveryTotal}
          freeDeliveryThreshold={freeDeliveryThreshold}
        />
      )}

      {totalItems > 0 && (
        <motion.div
          initial={{ y: 100, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          className="fixed left-4 right-4 z-40"
          style={{ bottom: orderMode === 'delivery' ? '120px' : '72px' }}
        >
          <motion.button
            whileHover={{ scale: 1.02, boxShadow: '0 20px 40px rgba(245, 158, 11, 0.4)' }}
            whileTap={{ scale: 0.98 }}
            onClick={() => navigate(`/${shopSlug}/cart`, { state: { orderMode } })}
            className="flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 h-16 text-lg font-black text-white shadow-2xl transition-all"
          >
            <ShoppingCart className="h-6 w-6" />
            <span>Корзина ({totalItems})</span>
            <span className="text-xl">·</span>
            <span className="text-xl">{totalAmount.toLocaleString('ru-RU')} ₽</span>
          </motion.button>
        </motion.div>
      )}

      <BottomNavigation />
    </div>
  );
}
