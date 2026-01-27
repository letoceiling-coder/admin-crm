import { Home, ShoppingCart, User, Heart, type LucideIcon } from 'lucide-react';
import { useNavigate, useLocation, useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { cn } from '@/lib/utils';
import { useCartStore } from '@/store/cartStore';

type NavItem = {
  icon: LucideIcon;
  label: string;
  path: string;
  exact: boolean;
  badge?: number;
};

export function BottomNavigation() {
  const navigate = useNavigate();
  const location = useLocation();
  const { shopSlug } = useParams<{ shopSlug?: string }>();
  const totalItems = useCartStore((state) => state.getTotalItems());

  const base = shopSlug ? `/${shopSlug}` : '';
  const navItems: NavItem[] = [
    { icon: Home, label: 'Каталог', path: base || '/', exact: true },
    { icon: Heart, label: 'Избранное', path: `${base}/favorites`, exact: false },
    { icon: ShoppingCart, label: 'Корзина', path: `${base}/cart`, exact: false, badge: totalItems > 0 ? totalItems : undefined },
    { icon: User, label: 'Профиль', path: `${base}/profile`, exact: false },
  ];

  const isActive = (path: string, exact?: boolean) => {
    if (!path) return false;
    if (exact) return location.pathname === path || location.pathname === path + '/';
    return location.pathname.startsWith(path);
  };

  return (
    <motion.nav
      initial={{ y: 100 }}
      animate={{ y: 0 }}
      className="fixed bottom-0 left-0 right-0 z-50 border-t-2 border-amber-200 dark:border-amber-900 bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 backdrop-blur-xl safe-area-bottom shadow-2xl"
    >
      <div className="flex h-16 items-center justify-around">
        {navItems.map((item) => {
          const Icon = item.icon;
          const active = isActive(item.path, item.exact);

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
                {item.badge != null && item.badge > 0 && (
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
