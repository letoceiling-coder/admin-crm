export interface Category {
  id: number;
  name: string;
  slug?: string;
  description?: string;
  parent_id?: number;
  image_id?: number;
  position?: number;
  is_active?: boolean;
  shop_id: number;
  user_id: number;
  created_at: string;
  updated_at: string;
  image?: Media;
  parent?: Category;
  children?: Category[];
}

export interface Product {
  id: number;
  name: string;
  slug?: string;
  description?: string;
  sku?: string;
  price: number;
  weight?: number;
  protein?: number;
  fat?: number;
  carbs?: number;
  calories?: number;
  category_id?: number;
  unit_id?: number;
  image_id?: number;
  stock?: number;
  position?: number;
  is_active?: boolean;
  shop_id: number;
  user_id: number;
  created_at: string;
  updated_at: string;
  category?: Category;
  unit?: Unit;
  image?: Media;
  images?: Media[];
}

export interface Unit {
  id: number;
  name: string;
  short_name?: string;
  user_id: number;
  created_at: string;
  updated_at: string;
}

export interface Media {
  id: number;
  name: string;
  path: string;
  mime_type: string;
  size: number;
  created_at: string;
  updated_at: string;
  url?: string;
}

export interface Shop {
  id: number;
  name: string;
  slug?: string;
  template?: string;
  inn?: string;
  ogrn?: string;
  telegram_bot_token?: string;
  admin_id: number;
  created_at: string;
  updated_at: string;
  addresses?: ShopAddress[];
  phones?: ShopPhone[];
  custom_fields?: ShopCustomField[];
}

export interface ShopAddress {
  id: number;
  shop_id: number;
  address: string;
}

export interface ShopPhone {
  id: number;
  shop_id: number;
  phone: string;
}

export interface ShopCustomField {
  id: number;
  shop_id: number;
  field_name: string;
  field_value?: string;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface ApiResponse<T> {
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export type OrderStatus = 'new' | 'pending' | 'processing' | 'completed' | 'cancelled';

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  quantity: number;
  price: string | number;
  product?: Product;
}

export interface Order {
  id: number;
  shop_id: number;
  order_number: string;
  customer_name: string;
  customer_phone?: string;
  customer_address?: string;
  notes?: string;
  total_amount: string | number;
  status: OrderStatus;
  order_date?: string;
  created_at: string;
  updated_at: string;
  items?: OrderItem[];
}
