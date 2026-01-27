<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    private User $user;
    private Shop $shop;
    private User $otherUser;
    private Shop $otherShop;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['level' => Role::LEVEL_ADMIN], ['name' => 'Администратор', 'level' => Role::LEVEL_ADMIN]);

        $this->user = $this->createUser(Role::LEVEL_ADMIN);
        $this->shop = $this->createShop($this->user);

        $this->otherUser = $this->createUser(Role::LEVEL_ADMIN);
        $this->otherShop = $this->createShop($this->otherUser);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_requires_shop_id_when_creating_order(): void
    {
        $response = $this->postJson('/api/admin/orders', [
            'customer_name' => 'Test Customer',
            'total_amount' => 1000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_id']);
    }

    /** @test */
    public function it_creates_order_with_valid_shop_id(): void
    {
        $response = $this->postJson('/api/admin/orders', [
            'customer_name' => 'Test Customer',
            'total_amount' => 1000,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'customer_name' => 'Test Customer',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test Customer',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_denies_access_when_shop_does_not_belong_to_user(): void
    {
        $response = $this->postJson('/api/admin/orders', [
            'customer_name' => 'Test Customer',
            'total_amount' => 1000,
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /** @test */
    public function it_filters_orders_by_shop_id(): void
    {
        Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer 1',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer 2',
            'total_amount' => 2000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $otherShop = $this->createShop($this->user);
        Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer 3',
            'total_amount' => 3000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/orders?shop_id={$this->shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(2, $data);
        foreach ($data as $order) {
            $this->assertEquals($this->shop->id, $order['shop_id']);
        }
    }

    /** @test */
    public function it_shows_only_user_orders(): void
    {
        Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'My Customer',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Other User Customer',
            'total_amount' => 2000,
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('My Customer', $data[0]['customer_name']);
    }

    /** @test */
    public function it_denies_access_to_other_user_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Other User Customer',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_denies_access_to_order_with_inaccessible_shop(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_order_with_valid_shop_id(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Original Name',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'customer_name' => 'Updated Name',
            'total_amount' => 1500,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'customer_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);
    }

    /** @test */
    public function it_denies_updating_order_shop_to_inaccessible_one(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/orders/{$order->id}", [
            'customer_name' => 'Updated Name',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_order_with_valid_access(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Customer to Delete',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->deleteJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200);
        // Проверяем soft delete
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_denies_deleting_other_user_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_name' => 'Other User Customer',
            'total_amount' => 1000,
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->deleteJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(403);
    }
}
