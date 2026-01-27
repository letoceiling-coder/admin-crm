import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

// Кэш для фото по умолчанию
let defaultImageUrl: string | null = null;
let defaultImagePromise: Promise<string | null> | null = null;

/**
 * Получить URL фото по умолчанию из настроек
 */
async function getDefaultImageUrl(): Promise<string | null> {
  if (defaultImageUrl !== null) {
    return defaultImageUrl;
  }

  if (defaultImagePromise) {
    return defaultImagePromise;
  }

  defaultImagePromise = (async () => {
    try {
      const { settingsApi } = await import('@/services/api');
      const response = await settingsApi.getDefaultImage();
      if (response.image?.url) {
        defaultImageUrl = response.image.url;
        return defaultImageUrl;
      }
    } catch (error) {
      console.warn('Failed to fetch default image:', error);
    }
    return null;
  })();

  return defaultImagePromise;
}

/**
 * Получить URL изображения с fallback на фото по умолчанию
 */
export function getImageUrl(image?: { path?: string; url?: string }): string {
  if (!image) {
    // Если изображения нет, возвращаем placeholder, который будет заменен на фото по умолчанию при ошибке
    return '/placeholder.jpg';
  }
  if (image.url) return image.url;
  if (image.path) {
    // Если путь относительный, добавляем базовый URL
    if (image.path.startsWith('http')) {
      return image.path;
    }
    return `${import.meta.env.VITE_API_BASE_URL || 'https://crm.neeklo.ru'}/storage/${image.path}`;
  }
  return '/placeholder.jpg';
}

/**
 * Получить URL фото по умолчанию (синхронно, если уже загружено)
 */
export function getDefaultImageUrlSync(): string | null {
  return defaultImageUrl;
}

/**
 * Обработчик ошибки загрузки изображения
 * Заменяет изображение на фото по умолчанию
 */
export async function handleImageError(
  event: React.SyntheticEvent<HTMLImageElement, Event>,
  fallbackUrl?: string
): Promise<void> {
  const img = event.currentTarget;
  
  // Если уже пытаемся загрузить фото по умолчанию, не делаем ничего
  if (img.src === defaultImageUrl || img.src.includes('placeholder.jpg')) {
    return;
  }

  // Пытаемся получить фото по умолчанию
  const defaultUrl = fallbackUrl || await getDefaultImageUrl() || '/placeholder.jpg';
  
  if (img.src !== defaultUrl) {
    img.src = defaultUrl;
  }
}
