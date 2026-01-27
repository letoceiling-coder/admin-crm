import { ChevronLeft, Search } from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';

interface MiniAppHeaderProps {
  title?: string;
  showBack?: boolean;
  showSearch?: boolean;
  className?: string;
}

export function MiniAppHeader({ 
  title = "Каталог", 
  showBack = false, 
  showSearch = true,
  className 
}: MiniAppHeaderProps) {
  const navigate = useNavigate();
  const { shopSlug } = useParams<{ shopSlug?: string }>();

  const handleSearchClick = () => {
    if (shopSlug) {
      navigate(`/${shopSlug}/search`);
    } else {
      navigate('/search');
    }
  };

  return (
    <motion.header
      initial={{ y: -100 }}
      animate={{ y: 0 }}
      className={cn(
        "sticky top-0 z-50 flex h-16 items-center justify-between border-b-2 border-amber-200 dark:border-amber-900 bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 backdrop-blur-xl px-4 safe-area-top shadow-lg w-full max-w-full overflow-hidden",
        className
      )}
    >
      <div className="flex items-center gap-2">
        {showBack && (
          <motion.button
            whileHover={{ scale: 1.1, rotate: -5 }}
            whileTap={{ scale: 0.9 }}
            onClick={() => navigate(-1)}
            className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg"
            aria-label="Назад"
          >
            <ChevronLeft className="h-6 w-6" />
          </motion.button>
        )}
      </div>

      <div className="flex items-center gap-2 flex-1 justify-center px-2 min-w-0 max-w-full overflow-hidden">
        <h1 className="text-xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent truncate max-w-full min-w-0 flex-1 text-center">
          {title}
        </h1>
      </div>

      <div className="flex items-center gap-2 justify-end">
        {showSearch && (
          <motion.button
            whileHover={{ scale: 1.1, rotate: 5 }}
            whileTap={{ scale: 0.9 }}
            onClick={handleSearchClick}
            className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 text-white shadow-lg"
            aria-label="Поиск"
          >
            <Search className="h-5 w-5" />
          </motion.button>
        )}
      </div>
    </motion.header>
  );
}
