import { useParams, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { ProductCard } from '@/components/ProductCard';
import { useQuery } from '@tanstack/react-query';
import { productApi } from '@/services/api';
import type { Product } from '@/types';
import { getImageUrl } from '@/lib/utils';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useState } from 'react';

export function ProductDetailPage() {
  const { shopSlug, productId } = useParams<{ shopSlug: string; productId: string }>();
  const navigate = useNavigate();
  const [selectedImage, setSelectedImage] = useState(0);

  const { data: product, isLoading } = useQuery<Product>({
    queryKey: ['product', productId],
    queryFn: () => productApi.getById(Number(productId)),
    enabled: !!productId,
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Загрузка...</p>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-20">
        <MiniAppHeader title="Товар не найден" showBack={true} />
        <div className="flex flex-col items-center justify-center px-4 py-20">
          <p className="text-lg font-black text-gray-900 dark:text-white mb-4">Товар не найден</p>
          <button
            onClick={() => navigate(`/${shopSlug}`)}
            className="px-6 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold"
          >
            Вернуться в каталог
          </button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  const images = product.images && product.images.length > 0
    ? product.images.map(img => getImageUrl(img))
    : product.image
    ? [getImageUrl(product.image)]
    : ['/placeholder.jpg'];

  return (
    <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-20 overflow-x-hidden">
      <MiniAppHeader title={product.name} showBack={true} showSearch={false} />

      <div className="px-4 py-4 max-w-full overflow-x-hidden">
        {/* Image Gallery */}
        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          className="mb-6"
        >
          <div className="w-full bg-white dark:bg-gray-900 rounded-3xl overflow-hidden mb-4 shadow-2xl border-2 border-amber-200 dark:border-amber-900" style={{ aspectRatio: '1 / 1' }}>
            <img
              src={images[selectedImage]}
              alt={product.name}
              className="w-full h-full object-cover"
              loading="eager"
            />
          </div>
          {images.length > 1 && (
            <div className="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
              {images.map((img, index) => (
                <motion.button
                  key={index}
                  whileHover={{ scale: 1.1, y: -4 }}
                  whileTap={{ scale: 0.95 }}
                  onClick={() => setSelectedImage(index)}
                  className={`flex-shrink-0 w-24 h-24 rounded-2xl overflow-hidden border-2 shadow-lg ${
                    selectedImage === index
                      ? 'border-amber-600 ring-2 ring-amber-200'
                      : 'border-amber-200 dark:border-amber-900'
                  }`}
                >
                  <img
                    src={img}
                    alt={`${product.name} ${index + 1}`}
                    className="w-full h-full object-cover"
                  />
                </motion.button>
              ))}
            </div>
          )}
        </motion.div>

        {/* Product Info */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="bg-white dark:bg-gray-900 rounded-3xl p-6 mb-4 shadow-2xl border-2 border-amber-200 dark:border-amber-900 max-w-full overflow-hidden"
        >
          <div className="flex items-start justify-between mb-4 gap-2">
            <h2 className="text-3xl font-black text-gray-900 dark:text-white flex-1 min-w-0 leading-tight break-words">
              {product.name}
            </h2>
          </div>

          {/* Price */}
          <div className="mb-4 p-4 rounded-2xl bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 dark:from-amber-900/20 dark:via-orange-900/20 dark:to-red-900/20">
            <span className="text-5xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent">
              {product.price.toLocaleString('ru-RU')} ₽
            </span>
            {product.unit && (
              <span className="text-lg text-gray-600 dark:text-gray-400 ml-2">
                / {product.unit.short_name || product.unit.name}
              </span>
            )}
          </div>

          {/* Nutritional Info */}
          {(product.weight || product.protein || product.fat || product.carbs || product.calories) && (
            <div className="mb-4 p-4 rounded-2xl bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-2 border-blue-200 dark:border-blue-900">
              <h3 className="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Пищевая ценность:</h3>
              <div className="grid grid-cols-2 gap-2 text-sm">
                {product.weight && (
                  <div>Вес: <span className="font-bold">{product.weight}г</span></div>
                )}
                {product.protein && (
                  <div>Белки: <span className="font-bold">{product.protein}г</span></div>
                )}
                {product.fat && (
                  <div>Жиры: <span className="font-bold">{product.fat}г</span></div>
                )}
                {product.carbs && (
                  <div>Углеводы: <span className="font-bold">{product.carbs}г</span></div>
                )}
                {product.calories && (
                  <div>Калории: <span className="font-bold">{product.calories} ккал</span></div>
                )}
              </div>
            </div>
          )}

          {/* Description */}
          {product.description && (
            <div className="mb-4">
              <h3 className="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Описание:</h3>
              <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                {product.description}
              </p>
            </div>
          )}

          {/* Add to Cart */}
          <div className="mb-4">
            <ProductCard product={product} variant="list" />
          </div>
        </motion.div>
      </div>

      <BottomNavigation />
    </div>
  );
}
