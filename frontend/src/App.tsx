import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { CatalogPage } from './pages/CatalogPage';
import { ProductDetailPage } from './pages/ProductDetailPage';
import { CartPage } from './pages/CartPage';
import { CheckoutPage } from './pages/CheckoutPage';
import { SearchPage } from './pages/SearchPage';
import { OrdersPage } from './pages/OrdersPage';
import { AboutPage } from './pages/AboutPage';
import './index.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
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
      </BrowserRouter>
    </QueryClientProvider>
  );
}

export default App;
