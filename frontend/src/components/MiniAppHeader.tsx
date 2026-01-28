import { ChevronLeft, Search, Sun, Moon } from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { useTheme } from '@/contexts/ThemeContext';

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
  const { isDark, toggleTheme, colors } = useTheme();

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
        "sticky top-0 z-50 flex h-16 items-center justify-between border-b-2 backdrop-blur-xl px-4 safe-area-top shadow-lg w-full max-w-full overflow-hidden bg-gradient-to-r",
        colors.border,
        colors.navBg,
        className
      )}
    >
      <div className="flex items-center gap-2">
        {showBack && (
          <motion.button
            whileHover={{ scale: 1.1, rotate: -5 }}
            whileTap={{ scale: 0.9 }}
            onClick={() => navigate(-1)}
            className={cn(`flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br ${colors.buttonGradient} text-white shadow-lg`)}
            aria-label="Назад"
          >
            <ChevronLeft className="h-6 w-6" />
          </motion.button>
        )}
      </div>

      <div className="flex items-center gap-2 flex-1 justify-center px-2 min-w-0 max-w-full overflow-hidden">
        <h1 className={cn(`text-xl font-black bg-gradient-to-r ${colors.titleGradient} bg-clip-text text-transparent truncate max-w-full min-w-0 flex-1 text-center`)}>
          {title}
        </h1>
      </div>

      <div className="flex items-center gap-2 justify-end">
        {/* Переключатель темы */}
        <motion.button
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.9 }}
          onClick={toggleTheme}
          className={cn(`flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br ${colors.buttonGradient} text-white shadow-lg`)}
          aria-label={isDark ? 'Светлая тема' : 'Темная тема'}
        >
          {isDark ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
        </motion.button>
        
        {showSearch && (
          <motion.button
            whileHover={{ scale: 1.1, rotate: 5 }}
            whileTap={{ scale: 0.9 }}
            onClick={handleSearchClick}
            className={cn(`flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br ${colors.accent} text-white shadow-lg`)}
            aria-label="Поиск"
          >
            <Search className="h-5 w-5" />
          </motion.button>
        )}
      </div>
    </motion.header>
  );
}
