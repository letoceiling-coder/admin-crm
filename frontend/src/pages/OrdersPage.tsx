import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { useTheme } from '@/contexts/ThemeContext';
import { Package, ChevronRight, Calendar, Hash } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ordersApi, shopApi, getTelegramInitData } from '@/services/api';
import type { Order, OrderStatus } from '@/types';

const STATUS_LABELS: Record<OrderStatus, string> = {
  new: 'Новый',
  pending: 'Ожидает',
  processing: 'В работе',
  completed: 'Выполнен',
  cancelled: 'Отменён',
};

const STATUS_COLORS: Record<OrderStatus, string> = {
  new: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

function formatDate(val: string | undefined): string {
  if (!val) return '—';
  const d = new Date(val);
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatMoney(amount: string | number): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount;
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(n);
}

export function OrdersPage() {
  const navigate = useNavigate();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { colors } = useTheme();
  const [shopId, setShopId] = useState<number | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (shopSlug) {
      shopApi.getBySlug(shopSlug).then((s) => setShopId(s.id)).catch(() => setShopId(null));
    }
  }, [shopSlug]);

  useEffect(() => {
    if (!shopId) {
      setLoading(false);
      return;
    }
    const initData = getTelegramInitData();
    if (!initData.trim()) {
      setOrders([]);
      setError('Откройте приложение через Telegram для просмотра заказов');
      setLoading(false);
      return;
    }
    setError(null);
    setLoading(true);
    ordersApi
      .list(shopId, initData)
      .then(setOrders)
      .catch((e: unknown) => {
        setOrders([]);
        setError(e instanceof Error ? e.message : 'Ошибка загрузки заказов');
      })
      .finally(() => setLoading(false));
  }, [shopId]);

  return (
    <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient} pb-28`)}>
      <MiniAppHeader title="Мои заказы" showBack={false} showSearch={false} />

      <div className="px-4 py-4 space-y-4">
        {loading && (
          <div className={cn(`${colors.cardBg} rounded-2xl p-8 border-2 ${colors.border} text-center`)}>
            <div className={cn('animate-spin rounded-full h-12 w-12 border-b-2 mx-auto', colors.border)} />
            <p className="mt-4 text-gray-600 dark:text-gray-400">Загрузка заказов...</p>
          </div>
        )}

        {!loading && error && (
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(`${colors.cardBg} rounded-2xl p-8 border-2 ${colors.border} text-center`)}
          >
            <Package className="h-16 w-16 mx-auto mb-4 text-gray-400" />
            <p className="text-gray-600 dark:text-gray-400">{error}</p>
          </motion.div>
        )}

        {!loading && !error && orders.length === 0 && (
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(`${colors.cardBg} rounded-2xl p-8 border-2 ${colors.border} text-center`)}
          >
            <Package className="h-16 w-16 mx-auto mb-4 text-gray-400" />
            <p className="text-gray-600 dark:text-gray-400">У вас пока нет заказов</p>
          </motion.div>
        )}

        {!loading && !error && orders.length > 0 && (
          <div className="space-y-3">
            {orders.map((order, index) => (
              <motion.div
                key={order.id}
                initial={{ opacity: 0, y: 16 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: index * 0.05 }}
                onClick={() => navigate(`/${shopSlug}/orders/${order.id}`)}
                className={cn(
                  `${colors.cardBg} rounded-2xl p-4 border-2 ${colors.border} shadow-lg active:scale-[0.99] transition-transform`,
                  'cursor-pointer'
                )}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-mono text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <Hash className="h-4 w-4" />
                        {order.order_number}
                      </span>
                      <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full', STATUS_COLORS[order.status])}>
                        {STATUS_LABELS[order.status]}
                      </span>
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-sm text-gray-600 dark:text-gray-400">
                      <Calendar className="h-4 w-4 shrink-0" />
                      {formatDate(order.created_at)}
                    </div>
                    <p className="mt-2 font-bold text-lg text-gray-900 dark:text-white">
                      {formatMoney(order.total_amount)}
                    </p>
                  </div>
                  <ChevronRight className="h-6 w-6 text-gray-400 shrink-0" />
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}
