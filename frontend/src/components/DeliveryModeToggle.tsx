import { cn } from '@/lib/utils';

interface DeliveryModeToggleProps {
  value: 'pickup' | 'delivery';
  onChange: (value: 'pickup' | 'delivery') => void;
  className?: string;
}

export function DeliveryModeToggle({ value, onChange, className }: DeliveryModeToggleProps) {
  return (
    <div className={cn('flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-900', className)}>
      <div className="flex rounded-full bg-gray-100 dark:bg-gray-800 p-1 w-full">
        <button
          type="button"
          onClick={() => onChange('delivery')}
          className={cn(
            'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-all',
            value === 'delivery'
              ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-sm'
              : 'bg-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
          )}
          aria-label="Доставка"
        >
          Доставка
        </button>
        <button
          type="button"
          onClick={() => onChange('pickup')}
          className={cn(
            'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-all',
            value === 'pickup'
              ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-sm'
              : 'bg-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
          )}
          aria-label="Самовывоз"
        >
          Самовывоз
        </button>
      </div>
    </div>
  );
}
