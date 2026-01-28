import { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { ProductCard } from '@/components/ProductCard';
import { useTheme } from '@/contexts/ThemeContext';
import type { Product } from '@/types';
import { productApi, shopApi } from '@/services/api';
import { useQuery } from '@tanstack/react-query';
import { Search, X, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface SearchResult extends Product {
  priority: number;
  matchPosition: number;
}

export function SearchPage() {
  const navigate = useNavigate();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { colors } = useTheme();
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [shopId, setShopId] = useState<number | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Загружаем магазин по slug
  useEffect(() => {
    if (shopSlug) {
      shopApi.getBySlug(shopSlug)
        .then((shop: any) => setShopId(shop.id))
        .catch(() => setShopId(null));
    }
  }, [shopSlug]);

  // Загружаем все товары для локального поиска (как в express — поиск по всем товарам)
  const { data: allProducts = [], isLoading } = useQuery<Product[]>({
    queryKey: ['products', 'search', shopId],
    queryFn: () => (shopId ? productApi.getAll(shopId) : []),
    enabled: !!shopId,
  });

  // Debounce query
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedQuery(query);
    }, 200);
    return () => clearTimeout(timer);
  }, [query]);

  // Auto-focus input on mount
  useEffect(() => {
    const timer = setTimeout(() => {
      inputRef.current?.focus();
    }, 100);
    return () => clearTimeout(timer);
  }, []);

  // Normalize search string
  const normalizeString = (str: string): string => {
    return str
      .trim()
      .toLowerCase()
      .replace(/\s+/g, ' ');
  };

  // Search function with ranking (как в express)
  const searchProducts = (searchQuery: string, products: Product[]): SearchResult[] => {
    if (!searchQuery || searchQuery.length < 1) return [];

    const normalizedQuery = normalizeString(searchQuery);
    const results: SearchResult[] = [];

    products.forEach((product) => {
      const nameLower = normalizeString(product.name);
      const descLower = normalizeString(product.description || '');

      let priority = 0;
      let matchPosition = Infinity;

      if (nameLower.startsWith(normalizedQuery)) {
        priority = 1;
        matchPosition = 0;
      } else if (nameLower.includes(normalizedQuery)) {
        priority = 2;
        matchPosition = nameLower.indexOf(normalizedQuery);
      } else if (descLower.includes(normalizedQuery)) {
        priority = 3;
        matchPosition = descLower.indexOf(normalizedQuery);
      }

      if (priority > 0) {
        results.push({
          ...product,
          priority,
          matchPosition,
        });
      }
    });

    results.sort((a, b) => {
      if (a.priority !== b.priority) return a.priority - b.priority;
      if (a.matchPosition !== b.matchPosition) return a.matchPosition - b.matchPosition;
      return a.name.localeCompare(b.name);
    });

    return results.slice(0, 50);
  };

  const searchResults = useMemo(() => {
    if (!debouncedQuery || debouncedQuery.length < 1) return [];
    return searchProducts(debouncedQuery, allProducts);
  }, [debouncedQuery, allProducts]);

  const suggestions = useMemo(() => {
    if (!debouncedQuery || debouncedQuery.length < 2) return [];
    return searchResults.slice(0, 10);
  }, [debouncedQuery, searchResults]);

  const handleSuggestionClick = (productId: number) => {
    if (shopSlug) {
      navigate(`/${shopSlug}/product/${productId}`);
    }
  };

  const handleClear = () => {
    setQuery('');
    setDebouncedQuery('');
  };

  const handleProductClick = (productId: number) => {
    if (shopSlug) {
      navigate(`/${shopSlug}/product/${productId}`);
    }
  };

  if (!shopSlug) {
    return (
      <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient.light} dark:${colors.bgGradient.dark} flex items-center justify-center`)}>
        <p className="text-gray-600 dark:text-gray-400">Магазин не найден</p>
      </div>
    );
  }

  return (
    <div className={cn(`flex flex-col min-h-screen bg-gradient-to-b ${colors.bgGradient.light} dark:${colors.bgGradient.dark} overflow-hidden pb-20`)}>
      <MiniAppHeader title="Поиск" showBack={true} showSearch={false} />

      {/* Search Input */}
      <div className={cn(`px-4 pt-3 pb-2 border-b-2 ${colors.border.light} dark:${colors.border.dark} bg-white/80 dark:bg-gray-900/80`)}>
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-amber-600 dark:text-amber-400" />
          <input
            ref={inputRef}
            type="text"
            placeholder="Введите название блюда"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="w-full pl-10 pr-10 h-11 text-base rounded-2xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
          />
          {query && (
            <button
              onClick={handleClear}
              className="absolute right-3 top-1/2 -translate-y-1/2 flex h-6 w-6 items-center justify-center rounded-full hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
              aria-label="Очистить"
            >
              <X className="h-4 w-4 text-gray-500 dark:text-gray-400" />
            </button>
          )}
        </div>
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-y-auto px-4 py-4">
        {isLoading ? (
          <div className="flex flex-col items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-amber-600" />
            <p className="mt-4 text-gray-600 dark:text-gray-400">Загрузка...</p>
          </div>
        ) : (
          <>
            {!debouncedQuery && (
              <div className="flex flex-col items-center justify-center py-20">
                <Search className="h-12 w-12 text-amber-300 dark:text-amber-700 mb-4" />
                <p className="text-gray-600 dark:text-gray-400 text-center">
                  Начните вводить название блюда
                </p>
              </div>
            )}

            {suggestions.length > 0 && (
              <div className="pt-4 pb-2">
                <h3 className="text-sm font-bold text-gray-900 dark:text-white mb-3">Подсказки</h3>
                <div className="space-y-1">
                  {suggestions.map((product) => (
                    <button
                      key={product.id}
                      onClick={() => handleSuggestionClick(product.id)}
                      className={cn(`w-full flex items-center justify-between gap-3 p-3 rounded-2xl border-2 ${colors.border.light} dark:${colors.border.dark} bg-white dark:bg-gray-900 hover:opacity-80 transition-colors text-left`)}
                    >
                      <span className="flex-1 text-sm font-medium text-gray-900 dark:text-white truncate">
                        {product.name}
                      </span>
                      <span className={cn(`text-sm font-bold bg-gradient-to-r ${colors.titleGradient.light} dark:${colors.titleGradient.dark} bg-clip-text text-transparent flex-shrink-0`)}>
                        {product.price.toLocaleString('ru-RU')} ₽
                      </span>
                    </button>
                  ))}
                </div>
              </div>
            )}

            {debouncedQuery && debouncedQuery.length >= 1 && (
              <div className={suggestions.length > 0 ? 'pt-6' : 'pt-4'}>
                {searchResults.length > 0 ? (
                  <>
                    <h3 className="text-sm font-bold text-gray-900 dark:text-white mb-3">
                      Результаты поиска ({searchResults.length})
                    </h3>
                    <div className="grid grid-cols-2 gap-3">
                      {searchResults.map((product) => (
                        <ProductCard
                          key={product.id}
                          product={product}
                          variant="grid"
                          onClick={() => handleProductClick(product.id)}
                        />
                      ))}
                    </div>
                  </>
                ) : (
                  <div className="flex flex-col items-center justify-center py-20">
                    <Search className="h-12 w-12 text-amber-300 dark:text-amber-700 mb-4" />
                    <p className="text-gray-600 dark:text-gray-400 text-center">
                      Ничего не найдено
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-500 text-center mt-2">
                      Попробуйте изменить запрос
                    </p>
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}
