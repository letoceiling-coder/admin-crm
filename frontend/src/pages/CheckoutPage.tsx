import { useNavigate, useParams, useLocation } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { DeliveryModeToggle } from '@/components/DeliveryModeToggle';
import { useCartStore } from '@/store/cartStore';
import { CheckCircle } from 'lucide-react';
import { shopApi, deliverySettingsApi } from '@/services/api';

type DeliveryType = 'pickup' | 'delivery';

export function CheckoutPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { items, getTotalAmount, clearCart, shopId } = useCartStore();
  const [shopIdState, setShopIdState] = useState<number | null>(null);
  
  // Получение orderMode из state или localStorage
  const getInitialDeliveryType = (): DeliveryType => {
    if (location.state?.orderMode === 'delivery') return 'delivery';
    if (location.state?.orderMode === 'pickup') return 'pickup';
    const saved = localStorage.getItem('orderMode');
    if (saved === 'delivery') return 'delivery';
    if (saved === 'pickup') return 'pickup';
    return 'pickup';
  };

  const [deliveryType, setDeliveryType] = useState<DeliveryType>(getInitialDeliveryType());
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [comment, setComment] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [deliveryCost, setDeliveryCost] = useState<number | null>(null);
  const [isCalculatingDelivery, setIsCalculatingDelivery] = useState(false);
  const [defaultCity, setDefaultCity] = useState<string>('Екатеринбург');

  const totalAmount = getTotalAmount();
  const finalAmount = deliveryType === 'delivery' && deliveryCost !== null 
    ? totalAmount + deliveryCost 
    : totalAmount;

  // Сохранение deliveryType в localStorage
  useEffect(() => {
    localStorage.setItem('orderMode', deliveryType);
  }, [deliveryType]);

  // Получение shopId из shopSlug
  useEffect(() => {
    if (shopSlug) {
      shopApi.getBySlug(shopSlug)
        .then((shop: any) => {
          setShopIdState(shop.id);
        })
        .catch((error: any) => {
          console.error('Ошибка загрузки магазина:', error);
        });
    } else if (shopId) {
      setShopIdState(shopId);
    }
  }, [shopSlug, shopId]);

  // Загрузка настроек доставки
  useEffect(() => {
    if (!shopIdState) return;
    
    const loadSettings = async () => {
      try {
        const settings = await deliverySettingsApi.getSettings(shopIdState);
        if (settings.default_city) {
          setDefaultCity(settings.default_city);
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
      }
    };

    loadSettings();
  }, [shopIdState]);

  // Расчет стоимости доставки при изменении адреса
  useEffect(() => {
    if (deliveryType === 'delivery' && address.trim().length > 5 && shopIdState) {
      const timeoutId = setTimeout(async () => {
        setIsCalculatingDelivery(true);
        try {
          const result = await deliverySettingsApi.calculateCost(address, totalAmount, shopIdState);
          if (result.valid && result.cost !== undefined) {
            setDeliveryCost(result.cost);
          } else {
            setDeliveryCost(null);
          }
        } catch (error) {
          console.error('Error calculating delivery cost:', error);
          setDeliveryCost(null);
        } finally {
          setIsCalculatingDelivery(false);
        }
      }, 1000); // Debounce 1 секунда

      return () => clearTimeout(timeoutId);
    } else {
      setDeliveryCost(null);
    }
  }, [address, deliveryType, totalAmount, shopIdState]);

  // Получаем данные пользователя из Telegram
  useEffect(() => {
    if ((window as any).Telegram?.WebApp?.initDataUnsafe?.user) {
      const user = (window as any).Telegram.WebApp.initDataUnsafe.user;
      setName(`${user.first_name} ${user.last_name || ''}`.trim());
      if (user.username) {
        // Можно использовать username как дополнительную информацию
      }
    }
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      // TODO: Отправить заказ на бекенд
      // const response = await orderApi.create({
      //   shop_id: shopIdState,
      //   items: items.map(item => ({
      //     product_id: item.product.id,
      //     quantity: item.quantity,
      //   })),
      //   customer_name: name,
      //   customer_phone: phone,
      //   delivery_address: deliveryType === 'delivery' ? address : null,
      //   delivery_type: deliveryType,
      //   delivery_cost: deliveryType === 'delivery' ? deliveryCost : null,
      //   comment: comment,
      //   total_amount: finalAmount,
      // });

      // Имитация отправки
      await new Promise(resolve => setTimeout(resolve, 1500));

      setIsSuccess(true);
      clearCart();

      setTimeout(() => {
        navigate(`/${shopSlug}`);
      }, 3000);
    } catch (error) {
      console.error('Ошибка оформления заказа:', error);
      alert('Ошибка при оформлении заказа. Попробуйте позже.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isSuccess) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-20">
        <MiniAppHeader title="Заказ оформлен" showBack={false} showSearch={false} />
        <div className="flex flex-col items-center justify-center px-4 py-20">
          <motion.div
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{ type: 'spring', stiffness: 200 }}
          >
            <CheckCircle className="h-24 w-24 text-green-500 mb-4" />
          </motion.div>
          <h2 className="text-2xl font-black text-gray-900 dark:text-white mb-2">
            Заказ успешно оформлен!
          </h2>
          <p className="text-gray-600 dark:text-gray-400 text-center mb-6">
            Мы свяжемся с вами в ближайшее время
          </p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-amber-50 via-orange-50 to-red-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 pb-28">
      <MiniAppHeader title="Оформление заказа" showBack={true} showSearch={false} />

      {/* Delivery Mode Toggle */}
      <div className="sticky top-14 z-30 bg-white/95 dark:bg-gray-950/95 backdrop-blur-xl border-b-2 border-amber-200 dark:border-amber-900">
        <DeliveryModeToggle 
          value={deliveryType === 'delivery' ? 'delivery' : 'pickup'} 
          onChange={(value) => setDeliveryType(value)} 
        />
      </div>

      <div className="px-4 py-4">
        <form onSubmit={handleSubmit} className="space-y-4">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-2xl border-2 border-amber-200 dark:border-amber-900"
          >
            <h3 className="text-lg font-black text-gray-900 dark:text-white mb-4">
              Контактная информация
            </h3>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Имя *
                </label>
                <input
                  type="text"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                  placeholder="Ваше имя"
                />
              </div>

              <div>
                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Телефон *
                </label>
                <input
                  type="tel"
                  required
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                  placeholder="+7 (999) 123-45-67"
                />
              </div>

              {deliveryType === 'delivery' && (
                <div>
                  <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Адрес доставки *
                  </label>
                  <textarea
                    required
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    rows={3}
                    className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                    placeholder={`Укажите адрес доставки${defaultCity ? ` (${defaultCity})` : ''}`}
                  />
                  {isCalculatingDelivery && (
                    <p className="text-xs text-gray-500 mt-1">Расчет стоимости доставки...</p>
                  )}
                  {!isCalculatingDelivery && deliveryCost !== null && (
                    <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                      Стоимость доставки: {deliveryCost.toLocaleString('ru-RU')} ₽
                    </p>
                  )}
                </div>
              )}

              <div>
                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                  Комментарий к заказу
                </label>
                <textarea
                  value={comment}
                  onChange={(e) => setComment(e.target.value)}
                  rows={3}
                  className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                  placeholder="Дополнительная информация (необязательно)"
                />
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-2xl border-2 border-amber-200 dark:border-amber-900"
          >
            <div className="space-y-2 mb-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-600 dark:text-gray-400">Товары:</span>
                <span className="text-lg font-bold text-gray-900 dark:text-white">
                  {totalAmount.toLocaleString('ru-RU')} ₽
                </span>
              </div>
              {deliveryType === 'delivery' && deliveryCost !== null && (
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-gray-600 dark:text-gray-400">Доставка:</span>
                  <span className="text-lg font-bold text-gray-900 dark:text-white">
                    {deliveryCost.toLocaleString('ru-RU')} ₽
                  </span>
                </div>
              )}
              <div className="flex items-center justify-between pt-2 border-t border-amber-200 dark:border-amber-900">
                <span className="text-lg font-bold text-gray-700 dark:text-gray-300">Итого:</span>
                <span className="text-3xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 bg-clip-text text-transparent">
                  {finalAmount.toLocaleString('ru-RU')} ₽
                </span>
              </div>
            </div>
            <motion.button
              type="submit"
              disabled={isSubmitting}
              whileHover={{ scale: isSubmitting ? 1 : 1.02 }}
              whileTap={{ scale: isSubmitting ? 1 : 0.98 }}
              className="w-full py-4 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-black text-lg shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting ? 'Оформление...' : 'Подтвердить заказ'}
            </motion.button>
          </motion.div>
        </form>
      </div>

      <BottomNavigation />
    </div>
  );
}
