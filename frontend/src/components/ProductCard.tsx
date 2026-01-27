import { motion } from 'framer-motion';
import { Plus, Minus, Heart } from 'lucide-react';
import type { Product } from '@/types';
import { useCartStore } from '@/store/cartStore';
import { cn } from '@/lib/utils';
import { getImageUrl, handleImageError } from '@/lib/utils';
import { useState } from 'react';

interface ProductCardProps {
  product: Product;
  onClick?: () => void;
  variant?: 'grid' | 'list';
}

export function ProductCard({ product, onClick, variant = 'grid' }: ProductCardProps) {
  const { items, addItem, updateQuantity } = useCartStore();
  const cartItem = items.find((item) => item.product.id === product.id);
  const quantity = cartItem?.quantity || 0;
  const [isFavorite, setIsFavorite] = useState(false);

  const handleAddToCart = (e: React.MouseEvent) => {
    e.stopPropagation();
    addItem(product);
  };

  const handleIncrement = (e: React.MouseEvent) => {
    e.stopPropagation();
    addItem(product);
  };

  const handleDecrement = (e: React.MouseEvent) => {
    e.stopPropagation();
    updateQuantity(product.id, quantity - 1);
  };

  const imageUrl = getImageUrl(product.image);

  if (variant === 'list') {
    return (
      <motion.div
        whileHover={{ x: 4 }}
        className="group relative flex cursor-pointer gap-4 rounded-3xl bg-white dark:bg-gray-900 border-2 border-amber-200 dark:border-amber-900 p-4 shadow-lg hover:shadow-2xl transition-all duration-300"
        onClick={onClick}
      >
        <div className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900 dark:to-orange-900">
          <img
            src={imageUrl}
            alt={product.name}
            className="h-full w-full object-cover"
            loading="lazy"
            onError={handleImageError}
          />
        </div>

        <div className="flex flex-1 flex-col justify-between min-w-0">
          <div>
            <h3 className="line-clamp-2 text-base font-black leading-tight text-gray-900 dark:text-white mb-1">
              {product.name}
            </h3>
            <p className="line-clamp-1 text-xs text-gray-600 dark:text-gray-400">
              {product.description}
            </p>
          </div>
          <div className="flex items-center justify-between mt-2">
            <span className="text-xl font-black bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
              {product.price.toLocaleString('ru-RU')} ₽
            </span>
            {quantity > 0 ? (
              <div className="flex items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-1">
                <motion.button
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.9 }}
                  onClick={handleDecrement}
                  className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white"
                >
                  <Minus className="h-4 w-4" />
                </motion.button>
                <span className="w-6 text-center text-sm font-black text-white">{quantity}</span>
                <motion.button
                  whileHover={{ scale: 1.1 }}
                  whileTap={{ scale: 0.9 }}
                  onClick={handleIncrement}
                  className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white"
                >
                  <Plus className="h-4 w-4" />
                </motion.button>
              </div>
            ) : (
              <motion.button
                whileHover={{ scale: 1.1 }}
                whileTap={{ scale: 0.9 }}
                onClick={handleAddToCart}
                className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg"
              >
                <Plus className="h-5 w-5" />
              </motion.button>
            )}
          </div>
        </div>
      </motion.div>
    );
  }

  return (
    <motion.div
      whileHover={{ y: -8, scale: 1.02 }}
      className="group relative flex flex-col cursor-pointer rounded-3xl bg-white dark:bg-gray-900 border-2 border-amber-200 dark:border-amber-900 overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 h-full"
      onClick={onClick}
    >
      <motion.button
        whileHover={{ scale: 1.2, rotate: 360 }}
        whileTap={{ scale: 0.9 }}
        onClick={(e) => {
          e.stopPropagation();
          setIsFavorite(!isFavorite);
        }}
        className="absolute top-3 right-3 z-10 p-2.5 rounded-2xl bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm shadow-2xl"
        aria-label="Избранное"
      >
        <Heart
          className={cn(
            "h-5 w-5 transition-colors",
            isFavorite ? "fill-red-500 text-red-500" : "text-gray-400"
          )}
        />
      </motion.button>

      <div className="relative w-full bg-gradient-to-br from-amber-100 via-orange-100 to-red-100 dark:from-amber-900 dark:via-orange-900 dark:to-red-900 overflow-hidden flex-shrink-0 flex items-center justify-center">
        <motion.img
          src={imageUrl}
          alt={product.name}
          className="w-full h-auto object-contain"
          loading="lazy"
          whileHover={{ scale: 1.05 }}
          transition={{ duration: 0.3 }}
          onError={handleImageError}
        />
      </div>

      <div className="flex flex-1 flex-col p-4 min-w-0">
        <h3 className="line-clamp-2 text-sm font-black leading-tight text-gray-900 dark:text-white mb-2 min-h-[2.5rem]">
          {product.name}
        </h3>
        
        <p className="line-clamp-1 text-xs text-gray-600 dark:text-gray-400 mb-3 flex-shrink-0">
          {product.description}
        </p>
        
        <div className="mt-auto pt-2 flex items-center justify-between gap-2 min-w-0">
          <span className="text-lg font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent truncate">
            {product.price.toLocaleString('ru-RU')} ₽
          </span>
          
          {quantity > 0 ? (
            <motion.div
              initial={false}
              animate={{ scale: 1 }}
              className="flex items-center gap-1 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl p-1.5"
            >
              <motion.button
                whileHover={{ scale: 1.1 }}
                whileTap={{ scale: 0.9 }}
                onClick={handleDecrement}
                className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white"
                aria-label="Уменьшить"
              >
                <Minus className="h-3.5 w-3.5" />
              </motion.button>
              <span className="w-6 text-center text-xs font-black text-white">{quantity}</span>
              <motion.button
                whileHover={{ scale: 1.1 }}
                whileTap={{ scale: 0.9 }}
                onClick={handleIncrement}
                className="flex h-8 w-8 items-center justify-center rounded-xl bg-white/20 text-white"
                aria-label="Увеличить"
              >
                <Plus className="h-3.5 w-3.5" />
              </motion.button>
            </motion.div>
          ) : (
            <motion.button
              whileHover={{ scale: 1.1, rotate: 5 }}
              whileTap={{ scale: 0.9 }}
              onClick={handleAddToCart}
              className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-2xl flex-shrink-0"
              aria-label="Добавить в корзину"
            >
              <Plus className="h-5 w-5" />
            </motion.button>
          )}
        </div>
      </div>
    </motion.div>
  );
}
