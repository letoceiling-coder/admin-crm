import { BrowserRouter, Routes, Route, Navigate, useParams } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useState, useEffect } from 'react';
import { CatalogPage } from './pages/CatalogPage';
import { ProductDetailPage } from './pages/ProductDetailPage';
import { CartPage } from './pages/CartPage';
import { CheckoutPage } from './pages/CheckoutPage';
import { SearchPage } from './pages/SearchPage';
import { OrdersPage } from './pages/OrdersPage';
import { AboutPage } from './pages/AboutPage';
import { ThemeProvider } from './contexts/ThemeContext';
import { shopApi } from './services/api';
import type { TemplateName } from './lib/templates';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function AppContent() {
  const { shopSlug } = useParams<{ shopSlug?: string }>();
  const [template, setTemplate] = useState<TemplateName>('amber');

  useEffect(() => {
    if (shopSlug) {
      shopApi.getBySlug(shopSlug)
        .then((shop) => {
          const shopTemplate = (shop.template as TemplateName) || 'amber';
          setTemplate(shopTemplate);
        })
        .catch((error) => {
          console.error('Error loading shop template:', error);
          setTemplate('amber');
        });
    }
  }, [shopSlug]);

  return (
    <ThemeProvider initialTemplate={template}>
      <Routes>
        <Route path="/:shopSlug" element={<CatalogPage />} />
        <Route path="/:shopSlug/search" element={<SearchPage />} />
        <Route path="/:shopSlug/product/:productId" element={<ProductDetailPage />} />
        <Route path="/:shopSlug/cart" element={<CartPage />} />
        <Route path="/:shopSlug/checkout" element={<CheckoutPage />} />
        <Route path="/:shopSlug/orders" element={<OrdersPage />} />
        <Route path="/:shopSlug/about" element={<AboutPage />} />
        <Route path="/" element={<Navigate to="/default" replace />} />
      </Routes>
    </ThemeProvider>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AppContent />
      </BrowserRouter>
    </QueryClientProvider>
  );
}

export default App;
