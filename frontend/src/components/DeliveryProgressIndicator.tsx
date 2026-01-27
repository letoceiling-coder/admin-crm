import { cn } from '@/lib/utils';

interface DeliveryProgressIndicatorProps {
  cartTotal: number;
  minDeliveryTotal: number;
  freeDeliveryThreshold?: number;
  className?: string;
}

export function DeliveryProgressIndicator({
  cartTotal,
  minDeliveryTotal,
  freeDeliveryThreshold,
  className,
}: DeliveryProgressIndicatorProps) {
  // Определяем текущую цель прогресса
  let targetAmount: number;
  let remaining: number;
  let progress: number;
  let isComplete: boolean;
  let message: string;
  let progressLabel: string;

  if (cartTotal < minDeliveryTotal) {
    // Показываем прогресс до минимального заказа
    targetAmount = minDeliveryTotal;
    remaining = Math.max(0, minDeliveryTotal - cartTotal);
    progress = Math.min(cartTotal / minDeliveryTotal, 1);
    isComplete = false;
    message = `Ещё ${formatNumber(remaining)} ₽ до доставки`;
    progressLabel = `${formatNumber(cartTotal)} / ${formatNumber(minDeliveryTotal)} ₽`;
  } else if (freeDeliveryThreshold && freeDeliveryThreshold > minDeliveryTotal && cartTotal < freeDeliveryThreshold) {
    // Показываем прогресс до бесплатной доставки (только если freeDeliveryThreshold задан и больше минимального заказа)
    targetAmount = freeDeliveryThreshold;
    remaining = Math.max(0, freeDeliveryThreshold - cartTotal);
    progress = Math.min((cartTotal - minDeliveryTotal) / (freeDeliveryThreshold - minDeliveryTotal), 1);
    isComplete = false;
    message = `Ещё ${formatNumber(remaining)} ₽ до бесплатной доставки`;
    progressLabel = `${formatNumber(cartTotal)} / ${formatNumber(Math.round(freeDeliveryThreshold))} ₽`;
  } else if (freeDeliveryThreshold && cartTotal >= freeDeliveryThreshold) {
    // Доставка бесплатна (только если freeDeliveryThreshold задан и достигнут)
    targetAmount = freeDeliveryThreshold;
    remaining = 0;
    progress = 1;
    isComplete = true;
    message = 'Доставка бесплатна';
    progressLabel = `${formatNumber(cartTotal)} ₽`;
  } else {
    // Доставка доступна, но не бесплатна (если freeDeliveryThreshold не задан или меньше минимального заказа)
    targetAmount = minDeliveryTotal;
    remaining = 0;
    progress = 1;
    isComplete = true;
    message = 'Доставка доступна';
    progressLabel = `${formatNumber(cartTotal)} ₽`;
  }

  // Форматирование чисел с пробелами (2 920 вместо 2920)
  function formatNumber(num: number): string {
    // Округляем до целого числа перед форматированием
    const rounded = Math.round(num);
    return rounded.toLocaleString('ru-RU', { 
      minimumFractionDigits: 0, 
      maximumFractionDigits: 0,
      useGrouping: true
    });
  }

  return (
    <div
      className={cn(
        'fixed left-0 right-0 z-40 pointer-events-none',
        className
      )}
      style={{
        bottom: 'calc(64px + env(safe-area-inset-bottom, 0px) + 8px)',
      }}
    >
      <div className="mx-4 bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-900 rounded-lg px-3 py-2 pointer-events-none shadow-lg">
        <div className="flex items-center justify-between mb-1.5">
          <span className="text-xs font-medium text-gray-900 dark:text-white">
            {message}
          </span>
          <span className="text-[10px] text-gray-600 dark:text-gray-400">
            {progressLabel}
          </span>
        </div>
        
        <div className="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
          <div
            className={cn(
              'h-full rounded-full transition-all duration-300 ease-out',
              isComplete ? 'bg-green-500' : 'bg-gradient-to-r from-amber-500 to-orange-600'
            )}
            style={{ width: `${progress * 100}%` }}
            aria-valuenow={progress * 100}
            aria-valuemin={0}
            aria-valuemax={100}
            role="progressbar"
          />
        </div>
      </div>
    </div>
  );
}
