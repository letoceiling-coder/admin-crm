<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Пользователь',
                'level' => Role::LEVEL_USER,
            ],
            [
                'name' => 'Менеджер',
                'level' => Role::LEVEL_MANAGER,
            ],
            [
                'name' => 'Администратор',
                'level' => Role::LEVEL_ADMIN,
            ],
            [
                'name' => 'Разработчик',
                'level' => Role::LEVEL_DEVELOPER,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['level' => $role['level']],
                $role
            );
        }
    }
}
