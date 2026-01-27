<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\TestDataSeeder;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private array $users = [];
    private array $shops = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовые данные
        $seeder = new TestDataSeeder();
        $seeder->run();

        // Получаем созданных пользователей и магазины
        $this->users = User::where('email', 'like', 'testuser%@test.com')->orderBy('id')->get()->toArray();
        $this->shops = Shop::whereIn('admin_id', array_column($this->users, 'id'))->orderBy('id')->get()->toArray();

        $this->assertCount(2, $this->users, 'Должно быть создано 2 пользователя');
        $this->assertCount(4, $this->shops, 'Должно быть создано 4 магазина (по 2 на пользователя)');
    }

    /**
     * Тест: Пользователь видит только свои магазины
     */
    public function test_user_sees_only_own_shops(): void
    {
        $user1 = User::find($this->users[0]['id']);
        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/admin/shops');
        $response->assertStatus(200);

        $shops = $response->json('data') ?? $response->json();
        $shopIds = array_column($shops, 'id');

        // Пользователь 1 должен видеть только свои 2 магазина
        $user1ShopIds = array_column(
            array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id),
            'id'
        );

        $this->assertCount(2, $shopIds);
        foreach ($user1ShopIds as $shopId) {
            $this->assertContains($shopId, $shopIds, "Пользователь должен видеть свой магазин ID: {$shopId}");
        }
    }

    /**
     * Тест: Категории изолированы по пользователям и магазинам
     */
    public function test_categories_are_isolated_by_user_and_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);

        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 видит только свои категории
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/categories?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $categories = $response->json('data') ?? $response->json();
        $this->assertCount(3, $categories, 'Пользователь 1 должен видеть 3 категории в своем магазине');
        foreach ($categories as $category) {
            $this->assertEquals($user1->id, $category['user_id']);
            $this->assertEquals($user1Shop1->id, $category['shop_id']);
        }

        // Пользователь 1 НЕ видит категории пользователя 2
        $response = $this->getJson("/api/admin/categories?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $categories = $response->json('data') ?? $response->json();
        $this->assertCount(0, $categories, 'Пользователь 1 не должен видеть категории пользователя 2');

        // Пользователь 2 видит только свои категории
        Sanctum::actingAs($user2);
        $response = $this->getJson("/api/admin/categories?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $categories = $response->json('data') ?? $response->json();
        $this->assertCount(3, $categories, 'Пользователь 2 должен видеть 3 категории в своем магазине');
        foreach ($categories as $category) {
            $this->assertEquals($user2->id, $category['user_id']);
            $this->assertEquals($user2Shop1->id, $category['shop_id']);
        }
    }

    /**
     * Тест: Товары изолированы по пользователям и магазинам
     */
    public function test_products_are_isolated_by_user_and_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);

        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 видит только свои товары
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/products?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $products = $response->json('data') ?? $response->json();
        $this->assertCount(6, $products, 'Пользователь 1 должен видеть 6 товаров в своем магазине');
        foreach ($products as $product) {
            $this->assertEquals($user1->id, $product['user_id']);
            $this->assertEquals($user1Shop1->id, $product['shop_id']);
        }

        // Пользователь 1 НЕ видит товары пользователя 2
        $response = $this->getJson("/api/admin/products?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $products = $response->json('data') ?? $response->json();
        $this->assertCount(0, $products, 'Пользователь 1 не должен видеть товары пользователя 2');
    }

    /**
     * Тест: Заказы изолированы по пользователям и магазинам
     */
    public function test_orders_are_isolated_by_user_and_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);

        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 видит только свои заказы
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/orders?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $orders = $response->json('data') ?? $response->json();
        $this->assertCount(2, $orders, 'Пользователь 1 должен видеть 2 заказа в своем магазине');
        foreach ($orders as $order) {
            $this->assertEquals($user1->id, $order['user_id']);
            $this->assertEquals($user1Shop1->id, $order['shop_id']);
        }

        // Пользователь 1 НЕ видит заказы пользователя 2
        $response = $this->getJson("/api/admin/orders?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $orders = $response->json('data') ?? $response->json();
        $this->assertCount(0, $orders, 'Пользователь 1 не должен видеть заказы пользователя 2');
    }

    /**
     * Тест: Доставки изолированы по пользователям и магазинам
     */
    public function test_deliveries_are_isolated_by_user_and_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);

        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 видит только свои доставки
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/deliveries?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $deliveries = $response->json('data') ?? $response->json();
        $this->assertCount(2, $deliveries, 'Пользователь 1 должен видеть 2 доставки в своем магазине');
        foreach ($deliveries as $delivery) {
            $this->assertEquals($user1->id, $delivery['user_id']);
            $this->assertEquals($user1Shop1->id, $delivery['shop_id']);
        }

        // Пользователь 1 НЕ видит доставки пользователя 2
        $response = $this->getJson("/api/admin/deliveries?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $deliveries = $response->json('data') ?? $response->json();
        $this->assertCount(0, $deliveries, 'Пользователь 1 не должен видеть доставки пользователя 2');
    }

    /**
     * Тест: Платежи изолированы по пользователям и магазинам
     */
    public function test_payments_are_isolated_by_user_and_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);

        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 видит только свои платежи
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/payments?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $payments = $response->json('data') ?? $response->json();
        $this->assertCount(2, $payments, 'Пользователь 1 должен видеть 2 платежа в своем магазине');
        foreach ($payments as $payment) {
            $this->assertEquals($user1->id, $payment['user_id']);
            $this->assertEquals($user1Shop1->id, $payment['shop_id']);
        }

        // Пользователь 1 НЕ видит платежи пользователя 2
        $response = $this->getJson("/api/admin/payments?shop_id={$user2Shop1->id}");
        $response->assertStatus(200);
        $payments = $response->json('data') ?? $response->json();
        $this->assertCount(0, $payments, 'Пользователь 1 не должен видеть платежи пользователя 2');
    }

    /**
     * Тест: Пользователь не может создать запись для чужого магазина
     */
    public function test_user_cannot_create_record_for_other_user_shop(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Пользователь 1 пытается создать категорию для магазина пользователя 2
        Sanctum::actingAs($user1);
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Unauthorized Category',
            'shop_id' => $user2Shop1->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /**
     * Тест: Пользователь не может получить доступ к чужой записи
     */
    public function test_user_cannot_access_other_user_record(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Получаем категорию пользователя 2
        $category = Category::where('shop_id', $user2Shop1->id)
            ->where('user_id', $user2->id)
            ->first();

        $this->assertNotNull($category, 'Категория пользователя 2 должна существовать');

        // Пользователь 1 пытается получить доступ к категории пользователя 2
        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /**
     * Тест: Пользователь не может редактировать чужую запись
     */
    public function test_user_cannot_update_other_user_record(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Получаем категорию пользователя 2
        $category = Category::where('shop_id', $user2Shop1->id)
            ->where('user_id', $user2->id)
            ->first();

        // Пользователь 1 пытается редактировать категорию пользователя 2
        Sanctum::actingAs($user1);
        $response = $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Hacked Category',
            'shop_id' => $user2Shop1->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Тест: Пользователь не может удалить чужую запись
     */
    public function test_user_cannot_delete_other_user_record(): void
    {
        $user1 = User::find($this->users[0]['id']);
        $user2 = User::find($this->users[1]['id']);

        $user2Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user2->id);
        $user2Shop1 = Shop::find(array_values($user2Shops)[0]['id']);

        // Получаем категорию пользователя 2
        $category = Category::where('shop_id', $user2Shop1->id)
            ->where('user_id', $user2->id)
            ->first();

        // Пользователь 1 пытается удалить категорию пользователя 2
        Sanctum::actingAs($user1);
        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /**
     * Тест: Пользователь может создавать записи только для своих магазинов
     */
    public function test_user_can_create_records_only_for_own_shops(): void
    {
        $user1 = User::find($this->users[0]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);

        Sanctum::actingAs($user1);

        // Создание категории для своего магазина - должно быть успешно
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'My Category',
            'shop_id' => $user1Shop1->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'My Category',
            'user_id' => $user1->id,
            'shop_id' => $user1Shop1->id,
        ]);

        // Создание товара для своего магазина - должно быть успешно
        $category = Category::where('shop_id', $user1Shop1->id)
            ->where('user_id', $user1->id)
            ->first();

        $response = $this->postJson('/api/admin/products', [
            'name' => 'My Product',
            'price' => 100,
            'category_id' => $category->id,
            'shop_id' => $user1Shop1->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'My Product',
            'user_id' => $user1->id,
            'shop_id' => $user1Shop1->id,
        ]);

        // Создание заказа для своего магазина - должно быть успешно
        $response = $this->postJson('/api/admin/orders', [
            'customer_name' => 'My Customer',
            'total_amount' => 1000,
            'shop_id' => $user1Shop1->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'My Customer',
            'user_id' => $user1->id,
            'shop_id' => $user1Shop1->id,
        ]);

        // Создание доставки для своего магазина - должно быть успешно
        $response = $this->postJson('/api/admin/deliveries', [
            'recipient_name' => 'My Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'My Address',
            'shop_id' => $user1Shop1->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('deliveries', [
            'recipient_name' => 'My Recipient',
            'user_id' => $user1->id,
            'shop_id' => $user1Shop1->id,
        ]);

        // Создание платежа для своего магазина - должно быть успешно
        $response = $this->postJson('/api/admin/payments', [
            'payer_name' => 'My Payer',
            'amount' => 500,
            'shop_id' => $user1Shop1->id,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'payer_name' => 'My Payer',
            'user_id' => $user1->id,
            'shop_id' => $user1Shop1->id,
        ]);
    }

    /**
     * Тест: Данные изолированы между разными магазинами одного пользователя
     */
    public function test_data_is_isolated_between_user_shops(): void
    {
        $user1 = User::find($this->users[0]['id']);

        $user1Shops = array_filter($this->shops, fn($s) => $s['admin_id'] == $user1->id);
        $user1Shop1 = Shop::find(array_values($user1Shops)[0]['id']);
        $user1Shop2 = Shop::find(array_values($user1Shops)[1]['id']);

        Sanctum::actingAs($user1);

        // Проверяем категории магазина 1
        $response = $this->getJson("/api/admin/categories?shop_id={$user1Shop1->id}");
        $response->assertStatus(200);
        $categoriesShop1 = $response->json('data') ?? $response->json();
        $this->assertCount(3, $categoriesShop1);

        // Проверяем категории магазина 2
        $response = $this->getJson("/api/admin/categories?shop_id={$user1Shop2->id}");
        $response->assertStatus(200);
        $categoriesShop2 = $response->json('data') ?? $response->json();
        $this->assertCount(3, $categoriesShop2);

        // Категории магазинов не должны пересекаться
        $shop1Ids = array_column($categoriesShop1, 'id');
        $shop2Ids = array_column($categoriesShop2, 'id');
        $intersection = array_intersect($shop1Ids, $shop2Ids);
        $this->assertEmpty($intersection, 'Категории разных магазинов не должны пересекаться');
    }
}
