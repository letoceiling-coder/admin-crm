import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.tsx'

// Инициализация Telegram Mini App
if ((window as any).Telegram?.WebApp) {
  const tg = (window as any).Telegram.WebApp;
  tg.ready();
  tg.expand();
  
  // Устанавливаем цветовую схему Telegram
  if (tg.colorScheme === 'dark') {
    document.documentElement.classList.add('dark');
  }
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
