import { useNavigate, useParams, useLocation } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { MiniAppHeader } from '@/components/MiniAppHeader';
import { BottomNavigation } from '@/components/BottomNavigation';
import { DeliveryModeToggle } from '@/components/DeliveryModeToggle';
import { useCartStore } from '@/store/cartStore';
import { useTheme } from '@/contexts/ThemeContext';
import { CheckCircle } from 'lucide-react';
import { shopApi, deliverySettingsApi, paymentMethodsApi, ordersApi, getTelegramInitData, type PaymentMethodSetting, type AddressSuggestion } from '@/services/api';
import { cn } from '@/lib/utils';

type DeliveryType = 'pickup' | 'delivery';

export function CheckoutPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { shopSlug } = useParams<{ shopSlug: string }>();
  const { colors } = useTheme();
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
  const [minDeliveryOrderTotal, setMinDeliveryOrderTotal] = useState<number | null>(null);
  const [deliveryError, setDeliveryError] = useState<string | null>(null);
  const [phoneError, setPhoneError] = useState<string | null>(null);
  const [addressSuggestions, setAddressSuggestions] = useState<AddressSuggestion[]>([]);
  const [isSuggestLoading, setIsSuggestLoading] = useState(false);
  const [isSuggestOpen, setIsSuggestOpen] = useState(false);
  const [selectedSuggestion, setSelectedSuggestion] = useState<AddressSuggestion | null>(null);
  const [lastValidatedAddress, setLastValidatedAddress] = useState<string | null>(null);
  const [isAddressValid, setIsAddressValid] = useState(false);

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
        // Загружаем минимальную сумму заказа для доставки
        if (settings.min_delivery_order_total_rub !== undefined && settings.min_delivery_order_total_rub !== null) {
          setMinDeliveryOrderTotal(Number(settings.min_delivery_order_total_rub));
        } else {
          setMinDeliveryOrderTotal(null);
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
      }
    };

    loadSettings();
  }, [shopIdState]);

  // Подсказки адресов (Yandex Suggest через backend)
  useEffect(() => {
    if (!shopIdState) return;
    if (deliveryType !== 'delivery') {
      setAddressSuggestions([]);
      setIsSuggestOpen(false);
      return;
    }
    // Подсказки нужны в основном для зон (там же требуется точный адрес)
    if (deliveryTypeSettings !== 'zones') {
      setAddressSuggestions([]);
      setIsSuggestOpen(false);
      return;
    }

    const query = address.trim();
    if (query.length < 2) {
      setAddressSuggestions([]);
      setIsSuggestOpen(false);
      return;
    }

    const timeoutId = setTimeout(async () => {
      setIsSuggestLoading(true);
      try {
        const resp = await deliverySettingsApi.getAddressSuggestions(
          query,
          defaultCity || 'Екатеринбург',
          shopIdState
        );
        if (resp.success) {
          setAddressSuggestions(resp.suggestions || []);
          setIsSuggestOpen(true);
        } else {
          setAddressSuggestions([]);
          setIsSuggestOpen(false);
        }
      } catch {
        setAddressSuggestions([]);
        setIsSuggestOpen(false);
      } finally {
        setIsSuggestLoading(false);
      }
    }, 350);

    return () => clearTimeout(timeoutId);
  }, [address, deliveryType, deliveryTypeSettings, defaultCity, shopIdState]);

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
        
        // Устанавливаем способ оплаты: по умолчанию или первый доступный; если нет методов — сбрасываем выбор
        if (availableMethods.length === 0) {
          setSelectedPaymentMethod(null);
        } else {
          const defaultMethod = availableMethods.find(m => m.is_default) || availableMethods[0];
          setSelectedPaymentMethod(defaultMethod.payment_method_code);
        }
      } catch (error) {
        console.error('Error loading payment methods:', error);
        setPaymentMethods([]);
        setSelectedPaymentMethod(null);
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
    setDeliveryError(null);
    // Любое ручное изменение адреса сбрасывает подтверждение
    setIsAddressValid(false);
    setLastValidatedAddress(null);
    if (deliveryType === 'delivery' && shopIdState) {
      const timeoutId = setTimeout(async () => {
        setIsCalculatingDelivery(true);
        try {
          const settings = await deliverySettingsApi.getSettings(shopIdState);
          
          if (settings.delivery_type === 'fixed') {
            if (settings.free_delivery_threshold && totalAmount >= settings.free_delivery_threshold) {
              setDeliveryCost(0);
            } else {
              setDeliveryCost(settings.fixed_delivery_cost || 0);
            }
            setIsCalculatingDelivery(false);
          } else {
            const trimmedAddress = address.trim();
            if (trimmedAddress.length > 5) {
              const result = await deliverySettingsApi.calculateCost(trimmedAddress, totalAmount, shopIdState);
              if (result.valid && result.cost !== undefined) {
                setDeliveryCost(result.cost);
                setDeliveryError(null);
                setIsAddressValid(true);
                setLastValidatedAddress(result.address ?? trimmedAddress);
                if (result.address) setAddress(result.address);
              } else {
                setDeliveryCost(null);
                setDeliveryError(result.error ?? 'Адрес не найден');
                setIsAddressValid(false);
                setLastValidatedAddress(null);
              }
            } else {
              setDeliveryCost(null);
            }
            setIsCalculatingDelivery(false);
          }
        } catch (error) {
          const message = error instanceof Error ? error.message : 'Не удалось рассчитать стоимость доставки';
          setDeliveryCost(null);
          setDeliveryError(message);
          setIsCalculatingDelivery(false);
        }
      }, 1000);

      return () => clearTimeout(timeoutId);
    } else {
      setDeliveryCost(null);
    }
  }, [address, deliveryType, totalAmount, shopIdState]);

  const handleSelectSuggestion = async (s: AddressSuggestion) => {
    if (!shopIdState) return;
    setSelectedSuggestion(s);
    setIsSuggestOpen(false);
    setAddress(s.value);

    // После выбора подсказки сразу делаем серверную валидацию/расчёт
    setIsCalculatingDelivery(true);
    setDeliveryError(null);
    try {
      const result = await deliverySettingsApi.calculateCost(s.value, totalAmount, shopIdState);
      if (result.valid) {
        if (result.cost !== undefined) setDeliveryCost(result.cost);
        setDeliveryError(null);
        setIsAddressValid(true);
        setLastValidatedAddress(result.address ?? s.value);
        if (result.address) setAddress(result.address);
      } else {
        setDeliveryCost(null);
        setDeliveryError(result.error ?? 'Адрес не найден');
        setIsAddressValid(false);
        setLastValidatedAddress(null);
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Не удалось проверить адрес';
      setDeliveryCost(null);
      setDeliveryError(message);
      setIsAddressValid(false);
      setLastValidatedAddress(null);
    } finally {
      setIsCalculatingDelivery(false);
    }
  };

  // Имя по умолчанию из Telegram
  useEffect(() => {
    const user = (window as any).Telegram?.WebApp?.initDataUnsafe?.user;
    if (user) {
      setName(`${user.first_name || ''} ${user.last_name || ''}`.trim());
    }
  }, []);

  // Проверка минимальной суммы заказа
  const isMinOrderMet = (): boolean => {
    if (deliveryType === 'delivery' && minDeliveryOrderTotal !== null) {
      return totalAmount >= minDeliveryOrderTotal;
    }
    // Для самовывоза минимальная сумма не требуется (или можно добавить отдельную настройку)
    return true;
  };

  const getRemainingAmount = (): number => {
    if (deliveryType === 'delivery' && minDeliveryOrderTotal !== null && totalAmount < minDeliveryOrderTotal) {
      return minDeliveryOrderTotal - totalAmount;
    }
    return 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!shopIdState) {
      alert('Магазин не определён');
      return;
    }
    const initData = getTelegramInitData();
    if (!initData.trim()) {
      alert('Откройте приложение через Telegram Mini App для оформления заказа');
      return;
    }

    if (!isMinOrderMet()) {
      const remaining = getRemainingAmount();
      alert(`Минимальная сумма заказа для доставки: ${minDeliveryOrderTotal?.toLocaleString('ru-RU')} ₽\nДобавьте товаров на ${remaining.toLocaleString('ru-RU')} ₽`);
      return;
    }

    if (!selectedPaymentMethod) {
      alert('Выберите способ оплаты');
      return;
    }

    setIsSubmitting(true);

    try {
      // Обязательная серверная проверка адреса перед созданием заказа (для доставки по зонам)
      if (deliveryType === 'delivery' && deliveryTypeSettings === 'zones') {
        const trimmedAddress = address.trim();
        if (trimmedAddress.length < 6) {
          setDeliveryError('Введите корректный адрес доставки');
          return;
        }
        // Если адрес не валидирован или изменился — валидируем прямо сейчас
        if (!isAddressValid || !lastValidatedAddress || lastValidatedAddress !== trimmedAddress) {
          setIsCalculatingDelivery(true);
          setDeliveryError(null);
          try {
            const result = await deliverySettingsApi.calculateCost(trimmedAddress, totalAmount, shopIdState);
            if (!result.valid) {
              setDeliveryError(result.error ?? 'Адрес не найден');
              return;
            }
            if (result.cost !== undefined) setDeliveryCost(result.cost);
            setIsAddressValid(true);
            setLastValidatedAddress(result.address ?? trimmedAddress);
            if (result.address) setAddress(result.address);
          } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось проверить адрес';
            setDeliveryError(message);
            return;
          } finally {
            setIsCalculatingDelivery(false);
          }
        }
      }

      const orderItems = items.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
        price: item.product.price,
      }));

      const createdOrder = await ordersApi.create(shopIdState, {
        init_data: initData,
        customer_name: name.trim(),
        customer_address: deliveryType === 'delivery' ? address.trim() || undefined : undefined,
        notes: comment.trim() || undefined,
        total_amount: Math.round(finalAmount * 100) / 100,
        items: orderItems,
      });

      // Онлайн-оплата ЮКасса: создаём платёж и редиректим на страницу оплаты
      if (selectedPaymentMethod === 'yookassa') {
        const payment = await ordersApi.createYooKassaPayment(shopIdState, createdOrder.id, initData);
        if (payment?.confirmation_url) {
          // Заказ уже создан — очищаем корзину, чтобы после возврата из оплаты она была пустой
          clearCart();
          window.location.href = payment.confirmation_url;
          return;
        }
      }

      setIsSuccess(true);
      clearCart();

      setTimeout(() => {
        navigate(`/${shopSlug}/orders`);
      }, 3000);
    } catch (error) {
      console.error('Ошибка оформления заказа:', error);
      alert(error instanceof Error ? error.message : 'Ошибка при оформлении заказа. Попробуйте позже.');
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
    <div className={cn(`min-h-screen bg-gradient-to-b ${colors.bgGradient} pb-28`)}>
      <MiniAppHeader title="Оформление заказа" showBack={true} showSearch={false} />

      {/* Delivery Mode Toggle */}
      <div className={cn(`sticky top-14 z-30 bg-white/95 dark:bg-gray-950/95 backdrop-blur-xl border-b-2 ${colors.border}`)}>
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

              {deliveryType === 'delivery' && (
                <div>
                  <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Адрес доставки {deliveryTypeSettings === 'zones' ? '*' : ''}
                  </label>
                  <div className="relative">
                    <textarea
                      required={deliveryTypeSettings === 'zones'}
                      value={address}
                      onChange={(e) => {
                        setAddress(e.target.value);
                        setSelectedSuggestion(null);
                      }}
                      onFocus={() => {
                        if (addressSuggestions.length > 0) setIsSuggestOpen(true);
                      }}
                      rows={3}
                      className="w-full px-4 py-3 rounded-xl border-2 border-amber-200 dark:border-amber-900 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                      placeholder={deliveryTypeSettings === 'fixed' 
                        ? `Укажите адрес доставки (необязательно)${defaultCity ? ` (${defaultCity})` : ''}`
                        : `Укажите адрес доставки${defaultCity ? ` (${defaultCity})` : ''}`
                      }
                    />

                    {deliveryTypeSettings === 'zones' && isSuggestOpen && (isSuggestLoading || addressSuggestions.length > 0) && (
                      <div className="absolute z-40 left-0 right-0 mt-2 bg-white dark:bg-gray-900 rounded-2xl border-2 border-amber-200 dark:border-amber-900 shadow-2xl overflow-hidden">
                        {isSuggestLoading && (
                          <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            Ищем подсказки...
                          </div>
                        )}
                        {!isSuggestLoading && addressSuggestions.length === 0 && (
                          <div className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            Подсказки не найдены
                          </div>
                        )}
                        {!isSuggestLoading && addressSuggestions.length > 0 && (
                          <div className="max-h-64 overflow-auto">
                            {addressSuggestions.map((sug, idx) => (
                              <button
                                key={`${sug.value}-${idx}`}
                                type="button"
                                onClick={() => handleSelectSuggestion(sug)}
                                className="w-full text-left px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors border-b border-amber-100 dark:border-amber-900/40 last:border-b-0"
                              >
                                <div className="text-sm font-bold text-gray-900 dark:text-white">
                                  {sug.display || sug.value}
                                </div>
                                {sug.subtitle && (
                                  <div className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                    {sug.subtitle}
                                  </div>
                                )}
                              </button>
                            ))}
                          </div>
                        )}
                      </div>
                    )}
                  </div>
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
                  {deliveryTypeSettings === 'zones' && !deliveryError && address.trim().length > 5 && isAddressValid && (
                    <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                      Адрес подтверждён
                      {selectedSuggestion ? ' (выбран из подсказок)' : ''}
                    </p>
                  )}
                  {deliveryError && (
                    <p className="text-xs text-red-600 dark:text-red-400 mt-1">{deliveryError}</p>
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
                <span className={cn(`text-3xl font-black bg-gradient-to-r ${colors.titleGradient} bg-clip-text text-transparent`)}>
                  {finalAmount.toLocaleString('ru-RU')} ₽
                </span>
              </div>
            </div>
            <motion.button
              type="submit"
              disabled={isSubmitting || !isMinOrderMet()}
              whileHover={{ scale: (isSubmitting || !isMinOrderMet()) ? 1 : 1.02 }}
              whileTap={{ scale: (isSubmitting || !isMinOrderMet()) ? 1 : 0.98 }}
              className={cn(`w-full py-4 rounded-2xl bg-gradient-to-r ${colors.buttonGradient} text-white font-black text-lg shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed`)}
            >
              {isSubmitting ? 'Оформление...' : !isMinOrderMet() ? `Минимум ${minDeliveryOrderTotal?.toLocaleString('ru-RU')} ₽` : 'Подтвердить заказ'}
            </motion.button>
          </motion.div>
        </form>
      </div>

      <BottomNavigation />
    </div>
  );
}
