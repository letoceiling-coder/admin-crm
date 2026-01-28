import type { Category, Product, Shop, PaginatedResponse, Order } from '@/types';

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

  // Проверяем Content-Type перед парсингом JSON
  const contentType = response.headers.get('content-type');
  const isJson = contentType && contentType.includes('application/json');

  if (!response.ok) {
    let error: { message?: string; error?: string; errors?: Record<string, string[]> };
    if (isJson) {
      try {
        error = await response.json();
      } catch (e) {
        error = { message: `HTTP error! status: ${response.status}` };
      }
    } else {
      const text = await response.text();
      error = { message: `HTTP error! status: ${response.status}. Server returned: ${text.substring(0, 100)}` };
    }
    // Для 422 Laravel возвращает errors: { field: ["текст"] } — показываем первый текст валидации
    const firstValidationError =
      error?.errors && typeof error.errors === 'object'
        ? (Object.values(error.errors).flat().find(Boolean) as string | undefined)
        : undefined;
    const message =
      firstValidationError ?? error?.error ?? error?.message ?? `HTTP error! status: ${response.status}`;
    throw new Error(message);
  }

  if (!isJson) {
    const text = await response.text();
    throw new Error(`Expected JSON but got: ${text.substring(0, 100)}`);
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

// Product API (публичные эндпоинты для Mini App: /shops/{shopId}/products, /products/{id})
export const productApi = {
  getAll: async (shopId: number, categoryId?: number): Promise<Product[]> => {
    let url = `/shops/${shopId}/products?per_page=100`;
    if (categoryId !== undefined && categoryId !== null) {
      url += `&category_id=${categoryId}`;
    }
    const response = await fetchApi<PaginatedResponse<Product>>(url);
    return response.data || [];
  },

  getById: async (id: number): Promise<Product> => {
    return fetchApi<Product>(`/products/${id}`);
  },

  search: async (shopId: number, query: string): Promise<Product[]> => {
    const response = await fetchApi<PaginatedResponse<Product>>(
      `/shops/${shopId}/products?search=${encodeURIComponent(query)}&per_page=100`
    );
    return response.data || [];
  },
};

// Получить initData из Telegram Web App (для Mini App)
export function getTelegramInitData(): string {
  const tg = (window as any).Telegram?.WebApp;
  return (tg?.initData ?? '') as string;
}

// Orders API: по initData (Mini App) или по телефону (fallback)
export interface CreateOrderPayload {
  init_data: string;
  customer_name: string;
  customer_address?: string;
  notes?: string;
  total_amount: number;
  items: Array<{ product_id: number; quantity: number; price: number }>;
}

export const ordersApi = {
  list: async (shopId: number, initData: string): Promise<Order[]> => {
    if (!initData.trim()) return [];
    return fetchApi<Order[]>(`/shops/${shopId}/orders`, {
      headers: { 'X-Telegram-Init-Data': initData },
    });
  },

  listByPhone: async (shopId: number, phone: string): Promise<Order[]> => {
    const digits = phone.replace(/\D/g, '');
    if (digits.length < 10) return [];
    return fetchApi<Order[]>(`/shops/${shopId}/orders?phone=${encodeURIComponent(phone)}`);
  },

  getById: async (shopId: number, orderId: number, initData: string): Promise<Order> => {
    return fetchApi<Order>(`/shops/${shopId}/orders/${orderId}`, {
      headers: { 'X-Telegram-Init-Data': initData },
    });
  },

  getByIdWithPhone: async (shopId: number, orderId: number, phone: string): Promise<Order> => {
    return fetchApi<Order>(`/shops/${shopId}/orders/${orderId}?phone=${encodeURIComponent(phone)}`);
  },

  create: async (shopId: number, payload: CreateOrderPayload): Promise<Order> => {
    return fetchApi<Order>(`/shops/${shopId}/orders`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  createYooKassaPayment: async (
    shopId: number,
    orderId: number,
    initData: string
  ): Promise<{ confirmation_url: string; payment_id: number; provider_payment_id?: string; status?: string }> => {
    return fetchApi<{ confirmation_url: string; payment_id: number; provider_payment_id?: string; status?: string }>(
      `/shops/${shopId}/orders/${orderId}/pay/yookassa`,
      {
        method: 'POST',
        headers: { 'X-Telegram-Init-Data': initData },
      }
    );
  },
};

// Settings API
export const settingsApi = {
  getDefaultImage: async (): Promise<{ image: { id: number; url: string; name: string } | null }> => {
    return fetchApi<{ image: { id: number; url: string; name: string } | null }>('/settings/default-image');
  },
};

// Delivery Settings API
export interface DeliverySettings {
  id?: number;
  shop_id?: number;
  origin_address?: string;
  origin_latitude?: number;
  origin_longitude?: number;
  default_city?: string;
  free_delivery_threshold?: number;
  delivery_type?: 'fixed' | 'zones';
  fixed_delivery_cost?: number;
  delivery_zones?: Array<{ max_distance: number | null; cost: number }>;
  is_enabled?: boolean;
  min_delivery_order_total_rub?: number;
  delivery_min_lead_hours?: number;
}

export interface DeliveryCostResult {
  valid: boolean;
  cost?: number;
  distance?: number;
  address?: string;
  zone?: string;
  coordinates?: { latitude: number; longitude: number };
  error?: string;
  error_code?: string;
}

export interface AddressSuggestion {
  value: string;
  display: string;
  subtitle?: string;
}

export const deliverySettingsApi = {
  getSettings: async (shopId: number): Promise<DeliverySettings> => {
    const response = await fetchApi<{ data: DeliverySettings }>(`/v1/delivery-settings?shop_id=${shopId}`);
    return response.data;
  },

  calculateCost: async (address: string, cartTotal: number, shopId: number): Promise<DeliveryCostResult> => {
    return fetchApi<DeliveryCostResult>('/v1/delivery/calculate-cost', {
      method: 'POST',
      body: JSON.stringify({ address, cart_total: cartTotal, shop_id: shopId }),
    });
  },

  getAddressSuggestions: async (query: string, city: string, shopId: number): Promise<{ success: boolean; suggestions: AddressSuggestion[]; error?: string }> => {
    return fetchApi<{ success: boolean; suggestions: AddressSuggestion[]; error?: string }>('/v1/delivery/address-suggestions', {
      method: 'POST',
      body: JSON.stringify({ query, city, shop_id: shopId }),
    });
  },
};

// Payment Methods API
export interface PaymentMethodSetting {
  id?: number;
  payment_method_code: 'cash' | 'yookassa';
  name: string;
  description?: string;
  is_enabled: boolean;
  is_default: boolean;
  available_for_delivery: boolean;
  available_for_pickup: boolean;
  sort_order: number;
  discount_type: 'none' | 'percentage' | 'fixed';
  discount_value?: number;
  min_cart_amount?: number;
  show_notification: boolean;
  notification_text?: string;
  settings?: Record<string, any>;
}

export interface PaymentMethodDiscount {
  discount: number;
  final_amount: number;
  applied: boolean;
}

export const paymentMethodsApi = {
  getSettings: async (shopId: number): Promise<PaymentMethodSetting[]> => {
    const response = await fetchApi<{ data: PaymentMethodSetting[] }>(`/v1/payment-methods?shop_id=${shopId}`);
    return response.data;
  },
};
