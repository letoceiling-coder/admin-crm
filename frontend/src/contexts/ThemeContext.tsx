import { createContext, useContext, useState, useEffect, useMemo } from 'react';
import type { ReactNode } from 'react';
import { getResolvedTemplateColors, type TemplateName, type ResolvedTemplateColors } from '@/lib/templates';

interface ThemeContextType {
  template: TemplateName;
  isDark: boolean;
  colors: ResolvedTemplateColors;
  toggleTheme: () => void;
  setTemplate: (template: TemplateName) => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ 
  children, 
  initialTemplate = 'amber' 
}: { 
  children: ReactNode;
  initialTemplate?: TemplateName;
}) {
  const [template, setTemplateState] = useState<TemplateName>(initialTemplate);
  const [isDark, setIsDark] = useState<boolean>(() => {
    // Проверяем localStorage или системную тему
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') {
      return saved === 'dark';
    }
    // Проверяем системную тему
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return true;
    }
    return false;
  });

  const colors = useMemo(() => getResolvedTemplateColors(template), [template]);

  // Обновляем template при изменении initialTemplate
  useEffect(() => {
    if (initialTemplate) {
      setTemplateState(initialTemplate);
    }
  }, [initialTemplate]);

  useEffect(() => {
    // Сохраняем тему в localStorage
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Применяем класс dark к html элементу
    if (isDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }, [isDark]);

  const toggleTheme = () => {
    setIsDark(prev => !prev);
  };

  const setTemplate = (newTemplate: TemplateName) => {
    setTemplateState(newTemplate);
  };

  return (
    <ThemeContext.Provider value={{ template, isDark, colors, toggleTheme, setTemplate }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}
