import { cn } from '@/lib/utils';
import { useTheme } from '@/contexts/ThemeContext';

interface DeliveryModeToggleProps {
  value: 'pickup' | 'delivery';
  onChange: (value: 'pickup' | 'delivery') => void;
  className?: string;
}

export function DeliveryModeToggle({ value, onChange, className }: DeliveryModeToggleProps) {
  const { colors } = useTheme();
  return (
    <div className={cn('flex items-center gap-2 px-4 py-2', colors.cardBg, className)}>
      <div className="flex rounded-full bg-gray-100 dark:bg-gray-800 p-1 w-full">
        <button
          type="button"
          onClick={() => onChange('delivery')}
          className={cn(
            'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-all',
            value === 'delivery'
              ? cn('bg-gradient-to-r text-white shadow-sm', colors.buttonGradient)
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
              ? cn('bg-gradient-to-r text-white shadow-sm', colors.buttonGradient)
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
