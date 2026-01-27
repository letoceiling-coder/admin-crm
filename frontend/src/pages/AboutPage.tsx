import { useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { Info } from 'lucide-react';

export function AboutPage() {
  const { shopSlug } = useParams<{ shopSlug: string }>();

  return (
    <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-20">
      <MiniAppHeader title="О нас" showBack={false} showSearch={false} />

      <div className="px-4 py-6">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-2xl border-2 border-amber-200 dark:border-amber-900"
        >
          <div className="flex flex-col items-center justify-center py-12">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.2, type: 'spring' }}
              className="mb-6"
            >
              <Info className="h-24 w-24 text-amber-500 dark:text-amber-400" />
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
