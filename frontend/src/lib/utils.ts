import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/** Из строки оставить только цифры */
function digitsOnly(value: string): string {
  return value.replace(/\D/g, '')
}

/**
 * Форматирование телефона в маску +7 (XXX) XXX-XX-XX (только цифры, макс. 10 после +7)
 * Ввод: 8 или 7 в начале — код страны, далее до 10 цифр номера.
 */
export function formatPhoneMask(value: string): string {
  let digits = digitsOnly(value)
  if (digits.startsWith('8')) digits = '7' + digits.slice(1)
  if (digits.startsWith('7')) digits = digits.slice(0, 11)
  else digits = digits.slice(0, 10)
  const rest = digits.startsWith('7') ? digits.slice(1) : digits // 0–10 цифр номера
  if (rest.length === 0) return ''
  if (rest.length <= 3) return '+7 (' + rest
  if (rest.length <= 6) return '+7 (' + rest.slice(0, 3) + ') ' + rest.slice(3)
  if (rest.length <= 8) return '+7 (' + rest.slice(0, 3) + ') ' + rest.slice(3, 6) + '-' + rest.slice(6)
  return '+7 (' + rest.slice(0, 3) + ') ' + rest.slice(3, 6) + '-' + rest.slice(6, 8) + '-' + rest.slice(8, 10)
}

/**
 * Проверка телефона: 10 цифр (без +7) или 11 с ведущей 7
 */
export function validatePhone(phone: string): boolean {
  const digits = digitsOnly(phone)
  const normalized = digits.startsWith('7') ? digits.slice(1) : digits
  return normalized.length === 10
}

/** Из отформатированного телефона получить только цифры (для отправки на бэк) */
export function phoneToDigits(phone: string): string {
  return digitsOnly(phone).replace(/^8/, '7').slice(0, 11)
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
 * Если изображения нет, возвращает временный URL, который будет заменен на фото по умолчанию
 */
export function getImageUrl(image?: { path?: string; url?: string }): string {
  if (!image) {
    // Если изображения нет, возвращаем специальный маркер, который будет обработан в handleImageError
    // или используем фото по умолчанию, если оно уже загружено
    return getDefaultImageUrlSync() || '/system/no-image.png';
  }
  if (image.url) return image.url;
  if (image.path) {
    // Если путь относительный, добавляем базовый URL
    if (image.path.startsWith('http')) {
      return image.path;
    }
    return `${import.meta.env.VITE_API_BASE_URL || 'https://crm.neeklo.ru'}/storage/${image.path}`;
  }
  return getDefaultImageUrlSync() || '/system/no-image.png';
}

/**
 * Получить URL фото по умолчанию (синхронно, если уже загружено)
 */
export function getDefaultImageUrlSync(): string | null {
  return defaultImageUrl;
}

/**
 * Установить фото по умолчанию в кэш (для использования в компонентах)
 */
export function setDefaultImageUrl(url: string | null): void {
  defaultImageUrl = url;
}

/**
 * Обработчик ошибки загрузки изображения
 * Заменяет изображение на фото по умолчанию из настроек или no-image.png
 */
export async function handleImageError(
  event: React.SyntheticEvent<HTMLImageElement, Event>,
  fallbackUrl?: string
): Promise<void> {
  const img = event.currentTarget;
  
  // Если уже пытаемся загрузить фото по умолчанию или no-image.png, не делаем ничего
  if (img.src === defaultImageUrl || img.src.includes('no-image.png') || img.src.includes('placeholder.jpg')) {
    return;
  }

  // Пытаемся получить фото по умолчанию из настроек
  const defaultUrl = fallbackUrl || await getDefaultImageUrl() || '/system/no-image.png';
  
  if (img.src !== defaultUrl) {
    img.src = defaultUrl;
  }
}
