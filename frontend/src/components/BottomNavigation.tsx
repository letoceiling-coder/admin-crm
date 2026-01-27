import { Home, ShoppingCart, User, Heart } from 'lucide-react';
import { useNavigate, useLocation } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { useCartStore } from '@/store/cartStore';

export function BottomNavigation() {
  const navigate = useNavigate();
  const location = useLocation();
  const totalItems = useCartStore((state) => state.getTotalItems());

  const isActive = (path: string) => location.pathname.startsWith(path);

  const navItems = [
    { icon: Home, label: 'Каталог', path: '/' },
    { icon: Heart, label: 'Избранное', path: '/favorites' },
    { icon: ShoppingCart, label: 'Корзина', path: '/cart', badge: totalItems > 0 ? totalItems : undefined },
    { icon: User, label: 'Профиль', path: '/profile' },
  ];

  return (
    <motion.nav
      initial={{ y: 100 }}
      animate={{ y: 0 }}
      className="fixed bottom-0 left-0 right-0 z-50 border-t-2 border-amber-200 dark:border-amber-900 bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 backdrop-blur-xl safe-area-bottom shadow-2xl"
    >
      <div className="flex h-16 items-center justify-around">
        {navItems.map((item) => {
          const Icon = item.icon;
          const active = isActive(item.path);
          
          return (
            <motion.button
              key={item.path}
              whileHover={{ scale: 1.15, y: -4 }}
              whileTap={{ scale: 0.9 }}
              onClick={() => navigate(item.path)}
              className={cn(
                'relative flex flex-col items-center justify-center gap-1 py-2 flex-1 transition-colors',
                active
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-gray-500 dark:text-gray-400'
              )}
            >
              <div className="relative">
                {active && (
                  <motion.div
                    layoutId="activeTab"
                    className="absolute inset-0 rounded-full bg-gradient-to-r from-amber-500 to-orange-600 opacity-20"
                  />
                )}
                <Icon className={cn("h-6 w-6 relative z-10", active && "drop-shadow-lg")} />
                {item.badge && item.badge > 0 && (
                  <motion.span
                    initial={{ scale: 0 }}
                    animate={{ scale: 1 }}
                    className="absolute -right-2 -top-2 z-20 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-pink-600 text-xs font-black text-white shadow-xl"
                  >
                    {item.badge > 9 ? '9+' : item.badge}
                  </motion.span>
                )}
              </div>
              <span className={cn("text-[10px] font-bold relative z-10", active && "drop-shadow-md")}>
                {item.label}
              </span>
              {active && (
                <motion.div
                  layoutId="activeIndicator"
                  className="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600 rounded-t-full"
                />
              )}
            </motion.button>
          );
        })}
      </div>
    </motion.nav>
  );
}
