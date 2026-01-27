import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function getImageUrl(image?: { path?: string; url?: string }): string {
  if (!image) return '/placeholder.jpg';
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
