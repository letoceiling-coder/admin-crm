<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

class DeliveryControllerTest extends TestCase
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
    public function it_requires_shop_id_when_creating_delivery(): void
    {
        $response = $this->postJson('/api/admin/deliveries', [
            'recipient_name' => 'Test Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Test Address',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_id']);
    }

    /** @test */
    public function it_creates_delivery_with_valid_shop_id(): void
    {
        $response = $this->postJson('/api/admin/deliveries', [
            'recipient_name' => 'Test Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Test Address',
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'recipient_name' => 'Test Recipient',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('deliveries', [
            'recipient_name' => 'Test Recipient',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_denies_access_when_shop_does_not_belong_to_user(): void
    {
        $response = $this->postJson('/api/admin/deliveries', [
            'recipient_name' => 'Test Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Test Address',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /** @test */
    public function it_filters_deliveries_by_shop_id(): void
    {
        Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient 1',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address 1',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient 2',
            'recipient_phone' => '+79991234568',
            'delivery_address' => 'Address 2',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $otherShop = $this->createShop($this->user);
        Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient 3',
            'recipient_phone' => '+79991234569',
            'delivery_address' => 'Address 3',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/deliveries?shop_id={$this->shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(2, $data);
        foreach ($data as $delivery) {
            $this->assertEquals($this->shop->id, $delivery['shop_id']);
        }
    }

    /** @test */
    public function it_shows_only_user_deliveries(): void
    {
        Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'My Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'My Address',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Other User Recipient',
            'recipient_phone' => '+79991234568',
            'delivery_address' => 'Other Address',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson('/api/admin/deliveries');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('My Recipient', $data[0]['recipient_name']);
    }

    /** @test */
    public function it_denies_access_to_other_user_delivery(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Other User Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/deliveries/{$delivery->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_denies_access_to_delivery_with_inaccessible_shop(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/deliveries/{$delivery->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_delivery_with_valid_shop_id(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Original Name',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Original Address',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/deliveries/{$delivery->id}", [
            'recipient_name' => 'Updated Name',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Updated Address',
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'recipient_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'recipient_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);
    }

    /** @test */
    public function it_denies_updating_delivery_shop_to_inaccessible_one(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/deliveries/{$delivery->id}", [
            'recipient_name' => 'Updated Name',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_delivery_with_valid_access(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Recipient to Delete',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->deleteJson("/api/admin/deliveries/{$delivery->id}");

        $response->assertStatus(200);
        // Проверяем soft delete
        $this->assertSoftDeleted('deliveries', ['id' => $delivery->id]);
    }

    /** @test */
    public function it_denies_deleting_other_user_delivery(): void
    {
        $delivery = Delivery::create([
            'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
            'recipient_name' => 'Other User Recipient',
            'recipient_phone' => '+79991234567',
            'delivery_address' => 'Address',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->deleteJson("/api/admin/deliveries/{$delivery->id}");

        $response->assertStatus(403);
    }
}
