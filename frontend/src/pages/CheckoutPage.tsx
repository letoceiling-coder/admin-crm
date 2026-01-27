import { useNavigate, useParams, useLocation } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { DeliveryModeToggle } from '@/components/DeliveryModeToggle';
import { useCartStore } from '@/store/cartStore';
import { CheckCircle } from 'lucide-react';
import { shopApi, deliverySettingsApi, paymentMethodsApi, type PaymentMethodSetting } from '@/services/api';

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
  const [deliveryTypeSettings, setDeliveryTypeSettings] = useState<'fixed' | 'zones' | null>(null);
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethodSetting[]>([]);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string | null>(null);
  const [paymentDiscount, setPaymentDiscount] = useState<{ discount: number; final_amount: number; applied: boolean } | null>(null);
  const [paymentNotification, setPaymentNotification] = useState<string | null>(null);

  const totalAmount = getTotalAmount();
  
  // Рассчитываем итоговую сумму с учетом доставки и скидки способа оплаты
  const deliveryCostValue = (deliveryType === 'delivery' && deliveryCost !== null) ? deliveryCost : 0;
  const finalAmount = paymentDiscount && paymentDiscount.applied 
    ? paymentDiscount.final_amount 
    : totalAmount + deliveryCostValue;

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
        if (settings.delivery_type) {
          setDeliveryTypeSettings(settings.delivery_type);
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
      }
    };

    loadSettings();
  }, [shopIdState]);

  // Загрузка способов оплаты
  useEffect(() => {
    if (!shopIdState) return;
    
    const loadPaymentMethods = async () => {
      try {
        const methods = await paymentMethodsApi.getSettings(shopIdState);
        
        // Фильтруем по доступности в зависимости от типа доставки
        const availableMethods = methods.filter(method => {
          if (deliveryType === 'delivery') {
            return method.available_for_delivery;
          } else {
            return method.available_for_pickup;
          }
        });
        
        setPaymentMethods(availableMethods);
        
        // Устанавливаем способ оплаты по умолчанию
        const defaultMethod = availableMethods.find(m => m.is_default) || availableMethods[0];
        if (defaultMethod) {
          setSelectedPaymentMethod(defaultMethod.payment_method_code);
        }
      } catch (error) {
        console.error('Error loading payment methods:', error);
      }
    };

    loadPaymentMethods();
  }, [shopIdState, deliveryType]);

  // Расчет скидки при изменении способа оплаты или суммы
  useEffect(() => {
    if (!selectedPaymentMethod || !shopIdState) {
      setPaymentDiscount(null);
      setPaymentNotification(null);
      return;
    }

    const method = paymentMethods.find(m => m.payment_method_code === selectedPaymentMethod);
    if (!method) {
      setPaymentDiscount(null);
      setPaymentNotification(null);
      return;
    }

    // Скидка рассчитывается от суммы товаров (без доставки)
    const cartAmount = totalAmount;
    
    // Расчет скидки
    let discount = 0;
    let applied = false;

    if (method.discount_type === 'none') {
      discount = 0;
      applied = false;
    } else {
      // Проверяем минимальную сумму корзины
      if (method.min_cart_amount && cartAmount < method.min_cart_amount) {
        discount = 0;
        applied = false;
      } else {
        // Рассчитываем скидку
        if (method.discount_type === 'percentage' && method.discount_value) {
          discount = (cartAmount * method.discount_value) / 100;
          applied = true;
        } else if (method.discount_type === 'fixed' && method.discount_value) {
          discount = Math.min(method.discount_value, cartAmount);
          applied = true;
        }
      }
    }

    // Итоговая сумма = товары - скидка + доставка
    const deliveryCostValue = (deliveryType === 'delivery' && deliveryCost !== null) ? deliveryCost : 0;
    const finalAmount = Math.max(0, cartAmount - discount + deliveryCostValue);

    setPaymentDiscount({
      discount: Math.round(discount * 100) / 100,
      final_amount: Math.round(finalAmount * 100) / 100,
      applied,
    });

    // Получаем уведомление
    if (method.show_notification && method.notification_text && applied) {
      let notification = method.notification_text;
      notification = notification.replace(/{discount}/g, discount.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      notification = notification.replace(/{final_amount}/g, (cartAmount - discount).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      notification = notification.replace(/{cart_amount}/g, cartAmount.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      if (method.discount_type === 'percentage' && method.discount_value) {
        notification = notification.replace(/{discount_percent}/g, method.discount_value.toString());
      }
      setPaymentNotification(notification);
    } else {
      setPaymentNotification(null);
    }
  }, [selectedPaymentMethod, totalAmount, deliveryType, deliveryCost, paymentMethods, shopIdState]);

  // Расчет стоимости доставки при изменении адреса или типа доставки
  useEffect(() => {
    if (deliveryType === 'delivery' && shopIdState) {
      const timeoutId = setTimeout(async () => {
        setIsCalculatingDelivery(true);
        try {
          // Для фиксированной доставки адрес не обязателен, но можно использовать минимальную длину
          // Для зон доставки адрес обязателен
          const settings = await deliverySettingsApi.getSettings(shopIdState);
          
          if (settings.delivery_type === 'fixed') {
            // Для фиксированной доставки просто используем фиксированную стоимость
            // Проверяем порог бесплатной доставки
            if (settings.free_delivery_threshold && totalAmount >= settings.free_delivery_threshold) {
              setDeliveryCost(0);
            } else {
              setDeliveryCost(settings.fixed_delivery_cost || 0);
            }
            setIsCalculatingDelivery(false);
          } else {
            // Для зон доставки требуется адрес
            if (address.trim().length > 5) {
              const result = await deliverySettingsApi.calculateCost(address, totalAmount, shopIdState);
              if (result.valid && result.cost !== undefined) {
                setDeliveryCost(result.cost);
              } else {
                setDeliveryCost(null);
              }
            } else {
              setDeliveryCost(null);
            }
            setIsCalculatingDelivery(false);
          }
        } catch (error) {
          console.error('Error calculating delivery cost:', error);
          setDeliveryCost(null);
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
    
    // Проверка обязательных полей
    if (!selectedPaymentMethod) {
      alert('Выберите способ оплаты');
      return;
    }
    
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
                    Адрес доставки {deliveryTypeSettings === 'zones' ? '*' : ''}
                  </label>
                  <textarea
                    required={deliveryTypeSettings === 'zones'}
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    rows={3}
                    className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                    placeholder={deliveryTypeSettings === 'fixed' 
                      ? `Укажите адрес доставки (необязательно)${defaultCity ? ` (${defaultCity})` : ''}`
                      : `Укажите адрес доставки${defaultCity ? ` (${defaultCity})` : ''}`
                    }
                  />
                  {deliveryTypeSettings === 'zones' && isCalculatingDelivery && (
                    <p className="text-xs text-gray-500 mt-1">Расчет стоимости доставки...</p>
                  )}
                  {!isCalculatingDelivery && deliveryCost !== null && (
                    <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                      Стоимость доставки: {deliveryCost.toLocaleString('ru-RU')} ₽
                    </p>
                  )}
                  {deliveryTypeSettings === 'fixed' && deliveryCost !== null && (
                    <p className="text-xs text-gray-500 mt-1">
                      Фиксированная стоимость доставки
                    </p>
                  )}
                </div>
              )}

              {/* Выбор способа оплаты */}
              {paymentMethods.length > 0 && (
                <div>
                  <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Способ оплаты *
                  </label>
                  <div className="space-y-2">
                    {paymentMethods.map((method) => (
                      <label
                        key={method.payment_method_code}
                        className="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                        style={{
                          borderColor: selectedPaymentMethod === method.payment_method_code
                            ? 'rgb(217 119 6)' // amber-600
                            : 'rgb(251 191 36)', // amber-200
                          backgroundColor: selectedPaymentMethod === method.payment_method_code
                            ? 'rgb(255 247 237)' // amber-50
                            : 'transparent',
                        }}
                      >
                        <input
                          type="radio"
                          name="payment_method"
                          value={method.payment_method_code}
                          checked={selectedPaymentMethod === method.payment_method_code}
                          onChange={() => setSelectedPaymentMethod(method.payment_method_code)}
                          className="w-4 h-4 text-amber-600 focus:ring-amber-500"
                        />
                        <div className="flex-1">
                          <div className="font-medium text-gray-900 dark:text-white">
                            {method.name}
                          </div>
                          {method.description && (
                            <div className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                              {method.description}
                            </div>
                          )}
                        </div>
                      </label>
                    ))}
                  </div>
                  {paymentNotification && (
                    <div className="mt-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-900 rounded-lg">
                      <p className="text-sm text-amber-800 dark:text-amber-200">
                        {paymentNotification}
                      </p>
                    </div>
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
              {paymentDiscount && paymentDiscount.applied && (
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium text-green-600 dark:text-green-400">Скидка:</span>
                  <span className="text-lg font-bold text-green-600 dark:text-green-400">
                    -{paymentDiscount.discount.toLocaleString('ru-RU')} ₽
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
