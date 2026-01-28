import { useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { useTheme } from '@/contexts/ThemeContext';
import { Info } from 'lucide-react';
import { cn } from '@/lib/utils';

export function AboutPage() {
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { colors } = useTheme();

  return (
    <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient.light} dark:${colors.bgGradient.dark} pb-20`)}>
      <MiniAppHeader title="О нас" showBack={false} showSearch={false} />

      <div className="px-4 py-6">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className={cn(`${colors.cardBg.light} dark:${colors.cardBg.dark} rounded-3xl p-8 shadow-2xl border-2 ${colors.border.light} dark:${colors.border.dark}`)}
        >
          <div className="flex flex-col items-center justify-center py-12">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.2, type: 'spring' }}
              className="mb-6"
            >
              <Info className={cn(`h-24 w-24 ${colors.active.light} dark:${colors.active.dark}`)} />
            </motion.div>
            <h2 className="text-2xl font-black text-gray-900 dark:text-white mb-4">
              О нас
            </h2>
            <p className="text-gray-600 dark:text-gray-400 text-center max-w-md">
              Информация о магазине будет отображаться здесь
            </p>
          </div>
        </motion.div>
      </div>

      <BottomNavigation />
    </div>
  );
}
