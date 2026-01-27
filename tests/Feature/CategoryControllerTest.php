<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    private User $user;
    private Shop $shop;
    private User $otherUser;
    private Shop $otherShop;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем роли
        Role::firstOrCreate(['level' => Role::LEVEL_ADMIN], ['name' => 'Администратор', 'level' => Role::LEVEL_ADMIN]);

        // Создаем пользователей и магазины
        $this->user = $this->createUser(Role::LEVEL_ADMIN);
        $this->shop = $this->createShop($this->user);

        $this->otherUser = $this->createUser(Role::LEVEL_ADMIN);
        $this->otherShop = $this->createShop($this->otherUser);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_requires_shop_id_when_creating_category(): void
    {
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Test Category',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shop_id']);
    }

    /** @test */
    public function it_creates_category_with_valid_shop_id(): void
    {
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Test Category',
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Test Category',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'shop_id' => $this->shop->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_denies_access_when_shop_does_not_belong_to_user(): void
    {
        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Test Category',
            'shop_id' => $this->otherShop->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Доступ к магазину запрещен']);
    }

    /** @test */
    public function it_filters_categories_by_shop_id(): void
    {
        // Создаем категории для разных магазинов
        Category::create([
            'name' => 'Category Shop 1',
            'slug' => Str::slug('Category Shop 1'),
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Category Shop 2',
            'slug' => Str::slug('Category Shop 2'),
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 2,
            'is_active' => true,
        ]);

        $otherShop = $this->createShop($this->user);
        Category::create([
            'name' => 'Category Other Shop',
            'slug' => Str::slug('Category Other Shop'),
            'user_id' => $this->user->id,
            'shop_id' => $otherShop->id,
            'position' => 3,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/admin/categories?shop_id={$this->shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(2, $data);
        foreach ($data as $category) {
            $this->assertEquals($this->shop->id, $category['shop_id']);
        }
    }

    /** @test */
    public function it_shows_only_user_categories(): void
    {
        Category::factory()->create([
            'name' => 'My Category',
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);

        Category::factory()->create([
            'name' => 'Other User Category',
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
        ]);

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        
        $this->assertCount(1, $data);
        $this->assertEquals('My Category', $data[0]['name']);
    }

    /** @test */
    public function it_denies_access_to_other_user_category(): void
    {
        $category = Category::create([
            'name' => 'Other User Category',
            'slug' => Str::slug('Other User Category'),
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_denies_access_to_category_with_inaccessible_shop(): void
    {
        $category = Category::factory()->create([
            'name' => 'Category',
            'user_id' => $this->user->id,
            'shop_id' => $this->otherShop->id, // Магазин другого пользователя
        ]);

        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_updates_category_with_valid_shop_id(): void
    {
        $category = Category::create([
            'name' => 'Original Name',
            'slug' => Str::slug('Original Name'),
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'shop_id' => $this->shop->id,
        ]);
    }

    /** @test */
    public function it_denies_updating_category_shop_to_inaccessible_one(): void
    {
        $category = Category::create([
            'name' => 'Category',
            'slug' => Str::slug('Category'),
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Name',
            'shop_id' => $this->otherShop->id, // Магазин другого пользователя
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_deletes_category_with_valid_access(): void
    {
        $category = Category::create([
            'name' => 'Category to Delete',
            'slug' => Str::slug('Category to Delete'),
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200);
        // Проверяем soft delete
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    /** @test */
    public function it_denies_deleting_other_user_category(): void
    {
        $category = Category::create([
            'name' => 'Other User Category',
            'slug' => Str::slug('Other User Category'),
            'user_id' => $this->otherUser->id,
            'shop_id' => $this->otherShop->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(403);
    }
}
