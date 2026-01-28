import { useNavigate, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { useTheme } from '@/contexts/ThemeContext';
import { useCartStore } from '@/store/cartStore';
import { Package, Calendar, Hash, MapPin, RotateCcw } from 'lucide-react';
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
  return new Date(val).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatMoney(amount: string | number): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount;
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(n);
}

export function OrderDetailPage() {
  const navigate = useNavigate();
  const { shopSlug, orderId } = useParams<{ shopSlug: string; orderId: string }>();
  const { colors } = useTheme();
  const addItem = useCartStore((s) => s.addItem);
  const setShopId = useCartStore((s) => s.setShopId);
  const initData = getTelegramInitData();

  const { data: shop } = useQuery({
    queryKey: ['shop', shopSlug],
    queryFn: () => (shopSlug ? shopApi.getBySlug(shopSlug) : Promise.reject()),
    enabled: !!shopSlug,
  });

  const shopId = shop?.id ?? null;

  const { data: order, isLoading, isError, error } = useQuery({
    queryKey: ['order', shopId, orderId, initData],
    queryFn: () => ordersApi.getById(shopId!, Number(orderId!), initData),
    enabled: !!shopId && !!orderId && !!initData.trim(),
  });

  const handleRepeatOrder = () => {
    if (!order?.items?.length || !shopId) return;
    setShopId(shopId);
    order.items.forEach((item) => {
      if (item.product) {
        addItem(item.product, item.quantity);
      }
    });
    navigate(`/${shopSlug}/cart`);
  };

  const hasItems = order?.items && order.items.length > 0;

  if (isLoading || !order) {
    return (
      <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient} pb-28 flex flex-col`)}>
        <MiniAppHeader title="Заказ" showBack showSearch={false} />
        <div className="flex-1 flex items-center justify-center text-center py-12 px-4">
          {isLoading && (
            <>
              <div className={cn('animate-spin rounded-full h-12 w-12 border-b-2 mx-auto', colors.border)} />
              <p className="mt-4 text-gray-600 dark:text-gray-400">Загрузка...</p>
            </>
          )}
          {!isLoading && !initData.trim() && (
            <>
              <Package className="h-16 w-16 mx-auto text-gray-400" />
              <p className="mt-4 text-gray-600 dark:text-gray-400">
                Откройте приложение через Telegram для просмотра заказа.
              </p>
              <button
                type="button"
                onClick={() => navigate(`/${shopSlug}/orders`)}
                className={cn('mt-4 px-4 py-2 rounded-xl font-medium', colors.buttonGradient, 'text-white')}
              >
                К списку заказов
              </button>
            </>
          )}
          {!isLoading && initData.trim() && isError && (
            <>
              <Package className="h-16 w-16 mx-auto text-gray-400" />
              <p className="mt-4 text-gray-600 dark:text-gray-400">
                {error instanceof Error ? error.message : 'Заказ не найден'}
              </p>
              <button
                type="button"
                onClick={() => navigate(`/${shopSlug}/orders`)}
                className={cn('mt-4 px-4 py-2 rounded-xl font-medium', colors.buttonGradient, 'text-white')}
              >
                К списку заказов
              </button>
            </>
          )}
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient} pb-28`)}>
      <MiniAppHeader title="Заказ" showBack showSearch={false} />

      <div className="px-4 py-4 space-y-4">
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          className={cn(`${colors.cardBg} rounded-2xl p-4 border-2 ${colors.border} shadow-sm`)}
        >
          <div className="flex items-center justify-between flex-wrap gap-2">
            <span className="font-mono text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
              <Hash className="h-4 w-4 shrink-0" />
              {order.order_number}
            </span>
            <span className={cn('text-sm font-medium px-2 py-1 rounded-full', STATUS_COLORS[order.status])}>
              {STATUS_LABELS[order.status]}
            </span>
          </div>
          <div className="flex items-center gap-1 mt-2 text-sm text-gray-600 dark:text-gray-400">
            <Calendar className="h-4 w-4 shrink-0" />
            {formatDate(order.created_at)}
          </div>
          <p className="mt-2 text-xl font-bold text-gray-900 dark:text-white">
            Итого: {formatMoney(order.total_amount)}
          </p>
        </motion.div>

        {order.customer_address && (
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(`${colors.cardBg} rounded-2xl p-4 border-2 ${colors.border} shadow-sm`)}
          >
            <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
              <MapPin className="h-4 w-4 shrink-0" />
              Адрес доставки
            </h3>
            <p className="text-gray-900 dark:text-white">{order.customer_address}</p>
          </motion.div>
        )}

        {hasItems && (
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className={cn(`${colors.cardBg} rounded-2xl p-4 border-2 ${colors.border} shadow-sm`)}
          >
            <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Состав заказа</h3>
            <ul className="space-y-2">
              {order.items!.map((item) => (
                <li key={item.id} className="flex items-center justify-between gap-2 py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                  <span className="text-gray-900 dark:text-white">
                    {item.product?.name ?? `Товар #${item.product_id}`} × {item.quantity}
                  </span>
                  <span className="font-medium text-gray-800 dark:text-gray-300 shrink-0">
                    {formatMoney(Number(item.price) * item.quantity)}
                  </span>
                </li>
              ))}
            </ul>
          </motion.div>
        )}

        {hasItems && order.status !== 'cancelled' && (
          <motion.button
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            type="button"
            onClick={handleRepeatOrder}
            className={cn(
              'w-full flex items-center justify-center gap-2 py-4 rounded-2xl font-bold text-white shadow-lg bg-gradient-to-r',
              colors.buttonGradient
            )}
          >
            <RotateCcw className="h-5 w-5 shrink-0" aria-hidden />
            Повторить заказ
          </motion.button>
        )}

        {(!hasItems || order.items!.length === 0) && (
          <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
            Состав заказа недоступен для повтора
          </p>
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}
