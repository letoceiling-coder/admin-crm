import type { Category, Product, Shop, PaginatedResponse } from '@/types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://crm.neeklo.ru/api';

// Получить токен из Telegram Mini App
function getAuthToken(): string | null {
  // В Telegram Mini App токен будет передаваться через initData
  // Пока используем localStorage для разработки
  // Для публичных endpoints токен не требуется
  return localStorage.getItem('auth_token');
}

async function fetchApi<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getAuthToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  // Добавляем существующие headers из options
  if (options.headers) {
    if (options.headers instanceof Headers) {
      options.headers.forEach((value, key) => {
        headers[key] = value;
      });
    } else if (Array.isArray(options.headers)) {
      options.headers.forEach(([key, value]) => {
        headers[key] = value;
      });
    } else {
      Object.assign(headers, options.headers as Record<string, string>);
    }
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: headers as HeadersInit,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Ошибка запроса' }));
    throw new Error(error.message || `HTTP error! status: ${response.status}`);
  }

  return response.json();
}

// Shop API
export const shopApi = {
  getBySlug: async (slug: string): Promise<Shop> => {
    return fetchApi<Shop>(`/shops/slug/${slug}`);
  },

  getById: async (id: number): Promise<Shop> => {
    return fetchApi<Shop>(`/admin/shops/${id}`);
  },
};

// Category API
export const categoryApi = {
  getAll: async (shopId: number): Promise<Category[]> => {
    return fetchApi<Category[]>(`/shops/${shopId}/categories`);
  },

  getById: async (id: number): Promise<Category> => {
    return fetchApi<Category>(`/admin/categories/${id}`);
  },
};

// Product API
export const productApi = {
  getAll: async (shopId: number, categoryId?: number): Promise<Product[]> => {
    let url = `/admin/products?shop_id=${shopId}&is_active=1&per_page=100`;
    if (categoryId) {
      url += `&category_id=${categoryId}`;
    }
    const response = await fetchApi<PaginatedResponse<Product>>(url);
    return response.data || [];
  },

  getById: async (id: number): Promise<Product> => {
    return fetchApi<Product>(`/admin/products/${id}`);
  },

  search: async (shopId: number, query: string): Promise<Product[]> => {
    const response = await fetchApi<PaginatedResponse<Product>>(
      `/admin/products?shop_id=${shopId}&search=${encodeURIComponent(query)}&is_active=1&per_page=100`
    );
    return response.data || [];
  },
};

// Settings API
export const settingsApi = {
  getDefaultImage: async (): Promise<{ image: { id: number; url: string; name: string } | null }> => {
    return fetchApi<{ image: { id: number; url: string; name: string } | null }>('/settings/default-image');
  },
};
