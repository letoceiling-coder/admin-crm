<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

class ProductControllerTest extends TestCase
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
    public function it_requires_shop_id_when_creating_product(): void
    {
        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'price' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_id']);
    }

    /** @test */
    public function it_creates_product_with_valid_shop_id(): void
    {
        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'price' => 100,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Test Product',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_denies_access_when_shop_does_not_belong_to_user(): void
    {
        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'price' => 100,
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /** @test */
    public function it_filters_products_by_shop_id(): void
    {
        Product::create([
            'name' => 'Product Shop 1',
            'slug' => Str::slug('Product Shop 1'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Product Shop 2',
            'slug' => Str::slug('Product Shop 2'),
            'price' => 200,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 2,
            'is_active' => true,
        ]);

        $otherShop = $this->createShop($this->user);
        Product::create([
            'name' => 'Product Other Shop',
            'slug' => Str::slug('Product Other Shop'),
            'price' => 300,
            'user_id' => $this->user->id,
            'shop_id' => $otherShop->id,
            'position' => 3,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/admin/products?shop_id={$this->shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(2, $data);
        foreach ($data as $product) {
            $this->assertEquals($this->shop->id, $product['shop_id']);
        }
    }

    /** @test */
    public function it_shows_only_user_products(): void
    {
        Product::create([
            'name' => 'My Product',
            'slug' => Str::slug('My Product'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Other User Product',
            'slug' => Str::slug('Other User Product'),
            'price' => 200,
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('My Product', $data[0]['name']);
    }

    /** @test */
    public function it_denies_access_to_other_user_product(): void
    {
        $product = Product::create([
            'name' => 'Other User Product',
            'slug' => Str::slug('Other User Product'),
            'price' => 100,
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/admin/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_denies_access_to_product_with_inaccessible_shop(): void
    {
        $product = Product::create([
            'name' => 'Product',
            'slug' => Str::slug('Product'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/admin/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_product_with_valid_shop_id(): void
    {
        $product = Product::create([
            'name' => 'Original Name',
            'slug' => Str::slug('Original Name'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'price' => 150,
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);
    }

    /** @test */
    public function it_denies_updating_product_shop_to_inaccessible_one(): void
    {
        $product = Product::create([
            'name' => 'Product',
            'slug' => Str::slug('Product'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_product_with_valid_access(): void
    {
        $product = Product::create([
            'name' => 'Product to Delete',
            'slug' => Str::slug('Product to Delete'),
            'price' => 100,
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(200);
        // Проверяем soft delete
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_denies_deleting_other_user_product(): void
    {
        $product = Product::create([
            'name' => 'Other User Product',
            'slug' => Str::slug('Other User Product'),
            'price' => 100,
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(403);
    }
}
