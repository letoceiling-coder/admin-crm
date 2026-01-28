// Система шаблонов (тем) для магазинов
export type TemplateName = 'amber' | 'blue' | 'green' | 'purple' | 'monochrome';

export interface TemplateColors {
  // Основные цвета фона
  bgGradient: {
    light: string;
    dark: string;
  };
  // Цвета границ
  border: {
    light: string;
    dark: string;
  };
  // Цвета заголовков
  titleGradient: {
    light: string;
    dark: string;
  };
  // Цвета кнопок
  buttonGradient: {
    light: string;
    dark: string;
  };
  // Цвета акцентов
  accent: {
    light: string;
    dark: string;
  };
  // Цвета карточек
  cardBg: {
    light: string;
    dark: string;
  };
  // Цвета навигации
  navBg: {
    light: string;
    dark: string;
  };
  // Цвета активных элементов
  active: {
    light: string;
    dark: string;
  };
}

export const templates: Record<TemplateName, TemplateColors> = {
  // Оригинальный шаблон (янтарный/оранжевый)
  amber: {
    bgGradient: {
      light: 'from-amber-50 via-orange-50 to-red-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    border: {
      light: 'border-amber-200',
      dark: 'border-amber-900',
    },
    titleGradient: {
      light: 'from-amber-600 via-orange-600 to-red-600',
      dark: 'from-amber-400 via-orange-400 to-red-400',
    },
    buttonGradient: {
      light: 'from-amber-500 to-orange-600',
      dark: 'from-amber-600 to-orange-700',
    },
    accent: {
      light: 'from-red-500 to-pink-600',
      dark: 'from-red-600 to-pink-700',
    },
    cardBg: {
      light: 'bg-white',
      dark: 'bg-gray-900',
    },
    navBg: {
      light: 'from-amber-50 via-orange-50 to-red-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    active: {
      light: 'text-amber-600',
      dark: 'text-amber-400',
    },
  },

  // Синий шаблон
  blue: {
    bgGradient: {
      light: 'from-blue-50 via-cyan-50 to-indigo-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    border: {
      light: 'border-blue-200',
      dark: 'border-blue-900',
    },
    titleGradient: {
      light: 'from-blue-600 via-cyan-600 to-indigo-600',
      dark: 'from-blue-400 via-cyan-400 to-indigo-400',
    },
    buttonGradient: {
      light: 'from-blue-500 to-cyan-600',
      dark: 'from-blue-600 to-cyan-700',
    },
    accent: {
      light: 'from-indigo-500 to-purple-600',
      dark: 'from-indigo-600 to-purple-700',
    },
    cardBg: {
      light: 'bg-white',
      dark: 'bg-gray-900',
    },
    navBg: {
      light: 'from-blue-50 via-cyan-50 to-indigo-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    active: {
      light: 'text-blue-600',
      dark: 'text-blue-400',
    },
  },

  // Зеленый шаблон
  green: {
    bgGradient: {
      light: 'from-green-50 via-emerald-50 to-teal-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    border: {
      light: 'border-green-200',
      dark: 'border-green-900',
    },
    titleGradient: {
      light: 'from-green-600 via-emerald-600 to-teal-600',
      dark: 'from-green-400 via-emerald-400 to-teal-400',
    },
    buttonGradient: {
      light: 'from-green-500 to-emerald-600',
      dark: 'from-green-600 to-emerald-700',
    },
    accent: {
      light: 'from-teal-500 to-cyan-600',
      dark: 'from-teal-600 to-cyan-700',
    },
    cardBg: {
      light: 'bg-white',
      dark: 'bg-gray-900',
    },
    navBg: {
      light: 'from-green-50 via-emerald-50 to-teal-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    active: {
      light: 'text-green-600',
      dark: 'text-green-400',
    },
  },

  // Фиолетовый шаблон
  purple: {
    bgGradient: {
      light: 'from-purple-50 via-pink-50 to-fuchsia-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    border: {
      light: 'border-purple-200',
      dark: 'border-purple-900',
    },
    titleGradient: {
      light: 'from-purple-600 via-pink-600 to-fuchsia-600',
      dark: 'from-purple-400 via-pink-400 to-fuchsia-400',
    },
    buttonGradient: {
      light: 'from-purple-500 to-pink-600',
      dark: 'from-purple-600 to-pink-700',
    },
    accent: {
      light: 'from-fuchsia-500 to-rose-600',
      dark: 'from-fuchsia-600 to-rose-700',
    },
    cardBg: {
      light: 'bg-white',
      dark: 'bg-gray-900',
    },
    navBg: {
      light: 'from-purple-50 via-pink-50 to-fuchsia-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    active: {
      light: 'text-purple-600',
      dark: 'text-purple-400',
    },
  },

  // Черно-белый шаблон
  monochrome: {
    bgGradient: {
      light: 'from-gray-50 via-gray-100 to-gray-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    border: {
      light: 'border-gray-300',
      dark: 'border-gray-700',
    },
    titleGradient: {
      light: 'from-gray-900 via-gray-800 to-gray-900',
      dark: 'from-gray-100 via-gray-200 to-gray-100',
    },
    buttonGradient: {
      light: 'from-gray-800 to-gray-900',
      dark: 'from-gray-700 to-gray-800',
    },
    accent: {
      light: 'from-gray-700 to-gray-800',
      dark: 'from-gray-600 to-gray-700',
    },
    cardBg: {
      light: 'bg-white',
      dark: 'bg-gray-900',
    },
    navBg: {
      light: 'from-gray-50 via-gray-100 to-gray-50',
      dark: 'from-gray-950 via-gray-900 to-gray-950',
    },
    active: {
      light: 'text-gray-900',
      dark: 'text-gray-100',
    },
  },
};

export const getTemplateColors = (template: TemplateName, isDark: boolean): TemplateColors => {
  return templates[template];
};
