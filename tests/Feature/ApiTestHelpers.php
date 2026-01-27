<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use Laravel\Sanctum\Sanctum;

trait ApiTestHelpers
{
    /**
     * Создать пользователя с ролью
     */
    protected function createUser(int $roleLevel = Role::LEVEL_ADMIN): User
    {
        $role = Role::firstOrCreate(
            ['level' => $roleLevel],
            ['name' => $this->getRoleName($roleLevel), 'level' => $roleLevel]
        );

        $user = User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        // Загружаем роль для корректной работы методов isAdmin(), isManager() и т.д.
        $user->load('role');

        return $user;
    }

    /**
     * Создать магазин для пользователя
     */
    protected function createShop(User $user, array $attributes = []): Shop
    {
        return Shop::create(array_merge([
            'name' => 'Test Shop ' . uniqid(),
            'admin_id' => $user->id,
        ], $attributes));
    }

    /**
     * Аутентифицировать пользователя
     */
    protected function actingAsUser(User $user): self
    {
        Sanctum::actingAs($user);
        return $this;
    }

    /**
     * Получить название роли по уровню
     */
    protected function getRoleName(int $level): string
    {
        return match($level) {
            Role::LEVEL_USER => 'Пользователь',
            Role::LEVEL_MANAGER => 'Менеджер',
            Role::LEVEL_ADMIN => 'Администратор',
            Role::LEVEL_DEVELOPER => 'Разработчик',
            default => 'Пользователь',
        };
    }
}
