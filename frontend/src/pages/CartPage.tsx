import { useNavigate, useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { ProductCard } from '@/components/ProductCard';
import { useCartStore } from '@/store/cartStore';
import { Trash2, ShoppingBag } from 'lucide-react';

export function CartPage() {
  const navigate = useNavigate();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { items, clearCart, getTotalItems, getTotalAmount } = useCartStore();
  const totalItems = getTotalItems();
  const totalAmount = getTotalAmount();

  if (totalItems === 0) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-20">
        <MiniAppHeader title="Корзина" showBack={true} showSearch={false} />
        <div className="flex flex-col items-center justify-center px-4 py-20">
          <ShoppingBag className="h-24 w-24 text-gray-300 dark:text-gray-700 mb-4" />
          <p className="text-xl font-black text-gray-900 dark:text-white mb-2">Корзина пуста</p>
          <p className="text-gray-600 dark:text-gray-400 mb-6 text-center">
            Добавьте товары из каталога
          </p>
          <motion.button
            whileHover={{ scale: 1.05 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => navigate(`/${shopSlug}`)}
            className="px-6 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold shadow-lg"
          >
            Перейти в каталог
          </motion.button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-28">
      <MiniAppHeader title="Корзина" showBack={true} showSearch={false} />

      <div className="px-4 py-4">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-black text-gray-900 dark:text-white">
            Товаров: {totalItems}
          </h2>
          {items.length > 0 && (
            <motion.button
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
              onClick={clearCart}
              className="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 font-bold text-sm"
            >
              <Trash2 className="h-4 w-4" />
              Очистить
            </motion.button>
          )}
        </div>

        <div className="space-y-4 mb-4">
          {items.map((item, index) => (
            <motion.div
              key={item.product.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.05 }}
            >
              <ProductCard
                product={item.product}
                variant="list"
                onClick={() => navigate(`/${shopSlug}/product/${item.product.id}`)}
              />
            </motion.div>
          ))}
        </div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white dark:bg-gray-900 rounded-3xl p-6 mb-4 shadow-2xl border-2 border-amber-200 dark:border-amber-900"
        >
          <div className="flex items-center justify-between mb-4">
            <span className="text-lg font-bold text-gray-700 dark:text-gray-300">Итого:</span>
            <span className="text-3xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent">
              {totalAmount.toLocaleString('ru-RU')} ₽
            </span>
          </div>
          <motion.button
            whileHover={{ scale: 1.02, boxShadow: '0 20px 40px rgba(245, 158, 11, 0.4)' }}
            whileTap={{ scale: 0.98 }}
            onClick={() => navigate(`/${shopSlug}/checkout`)}
            className="w-full py-4 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-black text-lg shadow-2xl"
          >
            Оформить заказ
          </motion.button>
        </motion.div>
      </div>

      <BottomNavigation />
    </div>
  );
}
