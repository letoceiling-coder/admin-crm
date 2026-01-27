<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Payment;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    private $users = [];
    private $shops = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Создание тестовых данных...');
            $this->command->info('');
        }

        // Создаем роли если их нет
        $adminRole = Role::firstOrCreate(
            ['level' => Role::LEVEL_ADMIN],
            ['name' => 'Администратор', 'level' => Role::LEVEL_ADMIN]
        );

        // Создаем двух пользователей
        if ($this->command) {
            $this->command->info('Создание пользователей...');
        }
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'name' => "Test User {$i}",
                'email' => "testuser{$i}@test.com",
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
            ]);
            $this->users[] = $user;
            if ($this->command) {
                $this->command->info("  ✓ Пользователь {$i}: {$user->name} (ID: {$user->id})");
            }
        }
        if ($this->command) {
            $this->command->info('');
        }

        // Создаем по 2 магазина для каждого пользователя
        if ($this->command) {
            $this->command->info('Создание магазинов...');
        }
        foreach ($this->users as $userIndex => $user) {
            $userNum = $userIndex + 1;
            for ($shopIndex = 1; $shopIndex <= 2; $shopIndex++) {
                $shop = Shop::create([
                    'name' => "Shop {$userNum}-{$shopIndex}",
                    'admin_id' => $user->id,
                    'inn' => '123456789' . $userIndex . $shopIndex,
                    'ogrn' => '123456789012' . $userIndex . $shopIndex,
                ]);
                $this->shops[] = [
                    'shop' => $shop,
                    'user' => $user,
                    'user_index' => $userNum,
                    'shop_index' => $shopIndex,
                ];
                if ($this->command) {
                    $this->command->info("  ✓ Магазин: {$shop->name} (ID: {$shop->id}, User: {$user->name})");
                }
            }
        }
        if ($this->command) {
            $this->command->info('');
        }

        // Создаем категории и товары для каждого магазина
        if ($this->command) {
            $this->command->info('Создание категорий и товаров...');
        }
        foreach ($this->shops as $shopData) {
            $shop = $shopData['shop'];
            $user = $shopData['user'];
            $userIndex = $shopData['user_index'];
            $shopIndex = $shopData['shop_index'];

            // Создаем 3 категории для каждого магазина
            for ($catIndex = 1; $catIndex <= 3; $catIndex++) {
                $category = Category::create([
                    'name' => "Category U{$userIndex}-S{$shopIndex}-C{$catIndex}",
                    'slug' => Str::slug("category-u{$userIndex}-s{$shopIndex}-c{$catIndex}"),
                    'description' => "Description for category U{$userIndex}-S{$shopIndex}-C{$catIndex}",
                    'position' => $catIndex,
                    'is_active' => true,
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);

                // Создаем 2 товара для каждой категории
                for ($prodIndex = 1; $prodIndex <= 2; $prodIndex++) {
                    Product::create([
                        'name' => "Product U{$userIndex}-S{$shopIndex}-C{$catIndex}-P{$prodIndex}",
                        'slug' => Str::slug("product-u{$userIndex}-s{$shopIndex}-c{$catIndex}-p{$prodIndex}"),
                        'description' => "Description for product U{$userIndex}-S{$shopIndex}-C{$catIndex}-P{$prodIndex}",
                        'sku' => "SKU-U{$userIndex}-S{$shopIndex}-C{$catIndex}-P{$prodIndex}",
                        'price' => ($userIndex * 100) + ($shopIndex * 10) + ($catIndex * 5) + $prodIndex,
                        'stock' => 100,
                        'position' => $prodIndex,
                        'is_active' => true,
                        'category_id' => $category->id,
                        'user_id' => $user->id,
                        'shop_id' => $shop->id,
                    ]);
                }
            }

            if ($this->command) {
                $this->command->info("  ✓ Магазин {$shop->name}: 3 категории, 6 товаров");
            }
        }
        if ($this->command) {
            $this->command->info('');
        }

        // Создаем заказы, доставки и платежи для каждого магазина
        if ($this->command) {
            $this->command->info('Создание заказов, доставок и платежей...');
        }
        foreach ($this->shops as $shopData) {
            $shop = $shopData['shop'];
            $user = $shopData['user'];
            $userIndex = $shopData['user_index'];
            $shopIndex = $shopData['shop_index'];

            // Создаем 2 заказа
            for ($orderIndex = 1; $orderIndex <= 2; $orderIndex++) {
                Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                    'customer_name' => "Customer U{$userIndex}-S{$shopIndex}-O{$orderIndex}",
                    'customer_email' => "customer{$userIndex}{$shopIndex}{$orderIndex}@test.com",
                    'customer_phone' => '+7999123456' . $orderIndex,
                    'customer_address' => "Address U{$userIndex}-S{$shopIndex}-O{$orderIndex}",
                    'total_amount' => ($userIndex * 1000) + ($shopIndex * 100) + ($orderIndex * 50),
                    'status' => 'pending',
                    'order_date' => now(),
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);
            }

            // Создаем 2 доставки
            for ($delIndex = 1; $delIndex <= 2; $delIndex++) {
                Delivery::create([
                    'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
                    'recipient_name' => "Recipient U{$userIndex}-S{$shopIndex}-D{$delIndex}",
                    'recipient_phone' => '+7999123457' . $delIndex,
                    'delivery_address' => "Delivery Address U{$userIndex}-S{$shopIndex}-D{$delIndex}",
                    'status' => 'pending',
                    'delivery_date' => now(),
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);
            }

            // Создаем 2 платежа
            for ($payIndex = 1; $payIndex <= 2; $payIndex++) {
                Payment::create([
                    'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
                    'payer_name' => "Payer U{$userIndex}-S{$shopIndex}-P{$payIndex}",
                    'payer_email' => "payer{$userIndex}{$shopIndex}{$payIndex}@test.com",
                    'payer_phone' => '+7999123458' . $payIndex,
                    'amount' => ($userIndex * 500) + ($shopIndex * 50) + ($payIndex * 25),
                    'payment_method' => 'cash',
                    'status' => 'pending',
                    'payment_date' => now(),
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                ]);
            }

            if ($this->command) {
                $this->command->info("  ✓ Магазин {$shop->name}: 2 заказа, 2 доставки, 2 платежа");
            }
        }
        if ($this->command) {
            $this->command->info('');
            $this->command->info('✅ Тестовые данные созданы успешно!');
            $this->command->info('');
            $this->command->info('Статистика:');
            $this->command->info("  - Пользователей: " . count($this->users));
            $this->command->info("  - Магазинов: " . count($this->shops));
            $this->command->info("  - Категорий: " . (count($this->shops) * 3));
            $this->command->info("  - Товаров: " . (count($this->shops) * 6));
            $this->command->info("  - Заказов: " . (count($this->shops) * 2));
            $this->command->info("  - Доставок: " . (count($this->shops) * 2));
            $this->command->info("  - Платежей: " . (count($this->shops) * 2));
        }
    }

    /**
     * Получить ID созданных пользователей
     */
    public function getUserIds(): array
    {
        return array_map(fn($user) => $user->id, $this->users);
    }

    /**
     * Получить ID созданных магазинов
     */
    public function getShopIds(): array
    {
        return array_map(fn($data) => $data['shop']->id, $this->shops);
    }
}
