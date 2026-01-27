<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

class PaymentControllerTest extends TestCase
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
    public function it_requires_shop_id_when_creating_payment(): void
    {
        $response = $this->postJson('/api/admin/payments', [
            'payer_name' => 'Test Payer',
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_id']);
    }

    /** @test */
    public function it_creates_payment_with_valid_shop_id(): void
    {
        $response = $this->postJson('/api/admin/payments', [
            'payer_name' => 'Test Payer',
            'amount' => 1000,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'payer_name' => 'Test Payer',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'payer_name' => 'Test Payer',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_denies_access_when_shop_does_not_belong_to_user(): void
    {
        $response = $this->postJson('/api/admin/payments', [
            'payer_name' => 'Test Payer',
            'amount' => 1000,
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /** @test */
    public function it_filters_payments_by_shop_id(): void
    {
        Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer 1',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer 2',
            'amount' => 2000,
            'payment_method' => 'card',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $otherShop = $this->createShop($this->user);
        Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer 3',
            'amount' => 3000,
            'payment_method' => 'online',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/payments?shop_id={$this->shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(2, $data);
        foreach ($data as $payment) {
            $this->assertEquals($this->shop->id, $payment['shop_id']);
        }
    }

    /** @test */
    public function it_shows_only_user_payments(): void
    {
        Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'My Payer',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Other User Payer',
            'amount' => 2000,
            'payment_method' => 'card',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson('/api/admin/payments');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('My Payer', $data[0]['payer_name']);
    }

    /** @test */
    public function it_denies_access_to_other_user_payment(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Other User Payer',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_denies_access_to_payment_with_inaccessible_shop(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_payment_with_valid_shop_id(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Original Name',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/payments/{$payment->id}", [
            'payer_name' => 'Updated Name',
            'amount' => 1500,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'payer_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payer_name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);
    }

    /** @test */
    public function it_denies_updating_payment_shop_to_inaccessible_one(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->putJson("/api/admin/payments/{$payment->id}", [
            'payer_name' => 'Updated Name',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_payment_with_valid_access(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Payer to Delete',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        $response = $this->deleteJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(200);
        // Проверяем soft delete
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    /** @test */
    public function it_denies_deleting_other_user_payment(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
            'payer_name' => 'Other User Payer',
            'amount' => 1000,
            'payment_method' => 'cash',
            'status' => 'pending',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->deleteJson("/api/admin/payments/{$payment->id}");

        $response->assertStatus(403);
    }
}
