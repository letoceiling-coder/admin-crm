<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Payment;

class CleanTestDataSeeder extends Seeder
{
    /**
     * Удаление всех тестовых данных
     */
    public function run(): void
    {
        $this->command->info('Удаление тестовых данных...');
        $this->command->info('');

        // Находим тестовых пользователей
        $testUsers = User::where('email', 'like', 'testuser%@test.com')->get();

        if ($testUsers->isEmpty()) {
            $this->command->info('Тестовые пользователи не найдены.');
            return;
        }

        $userIds = $testUsers->pluck('id')->toArray();
        $this->command->info("Найдено тестовых пользователей: " . count($userIds));

        // Находим магазины тестовых пользователей
        $testShops = Shop::whereIn('admin_id', $userIds)->get();
        $shopIds = $testShops->pluck('id')->toArray();
        $this->command->info("Найдено тестовых магазинов: " . count($shopIds));

        // Удаляем данные в правильном порядке (с учетом внешних ключей)
        $deletedCounts = [
            'products' => Product::whereIn('shop_id', $shopIds)->forceDelete(),
            'categories' => Category::whereIn('shop_id', $shopIds)->forceDelete(),
            'orders' => Order::whereIn('shop_id', $shopIds)->forceDelete(),
            'deliveries' => Delivery::whereIn('shop_id', $shopIds)->forceDelete(),
            'payments' => Payment::whereIn('shop_id', $shopIds)->forceDelete(),
        ];

        $this->command->info('Удалено:');
        foreach ($deletedCounts as $model => $count) {
            $this->command->info("  - {$model}: {$count} записей");
        }

        // Удаляем магазины
        $shopsDeleted = Shop::whereIn('id', $shopIds)->forceDelete();
        $this->command->info("  - shops: {$shopsDeleted} записей");

        // Удаляем пользователей
        $usersDeleted = User::whereIn('id', $userIds)->forceDelete();
        $this->command->info("  - users: {$usersDeleted} записей");

        $this->command->info('');
        $this->command->info('✅ Тестовые данные успешно удалены!');
    }
}
