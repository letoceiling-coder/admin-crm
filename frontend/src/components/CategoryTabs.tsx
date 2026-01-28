import { useRef } from 'react';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { useTheme } from '@/contexts/ThemeContext';
import type { Category } from '@/types';

interface CategoryTabsProps {
  categories: Category[];
  activeCategory: number | null;
  onCategoryChange: (categoryId: number | null) => void;
}

export function CategoryTabs({
  categories,
  activeCategory,
  onCategoryChange,
}: CategoryTabsProps) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const { colors } = useTheme();

  return (
    <div
      ref={scrollRef}
      className="scrollbar-hide flex gap-3 overflow-x-auto py-2"
    >
      <motion.button
        whileHover={{ scale: 1.05, y: -2 }}
        whileTap={{ scale: 0.95 }}
        onClick={() => onCategoryChange(null)}
        className={cn(
          'flex-shrink-0 rounded-2xl px-5 h-10 text-sm font-black transition-all duration-300 border-2 shadow-lg',
          activeCategory === null
            ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white border-transparent'
            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-amber-200 dark:border-amber-900'
        )}
      >
        Все
      </motion.button>
      {categories.map((category, index) => (
        <motion.button
          key={category.id}
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: index * 0.05 }}
          whileHover={{ scale: 1.05, y: -2 }}
          whileTap={{ scale: 0.95 }}
          onClick={() => onCategoryChange(category.id)}
          className={cn(
            'flex-shrink-0 rounded-2xl px-5 h-10 text-sm font-black transition-all duration-300 whitespace-nowrap border-2 shadow-lg',
            activeCategory === category.id
              ? cn('bg-gradient-to-r text-white border-transparent', colors.buttonGradient)
              : cn(colors.cardBg, 'text-gray-700 dark:text-gray-300', colors.border)
          )}
        >
          {category.name}
        </motion.button>
      ))}
    </div>
  );
}
