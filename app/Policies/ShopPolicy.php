<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\Response;

class ShopPolicy
{
    /**
     * Determine whether the user can view any models.
     * Разработчик видит все, администратор - свои, менеджер - назначенные
     */
    public function viewAny(User $user): bool
    {
        return $user->isDeveloper() || $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     * Доступ только к своему магазину (администратор) или назначенному (менеджер)
     */
    public function view(User $user, Shop $shop): bool
    {
        // Разработчик имеет доступ ко всем магазинам
        if ($user->isDeveloper()) {
            return true;
        }

        // Администратор имеет доступ только к своим магазинам
        if ($user->isAdmin()) {
            return $shop->admin_id === $user->id;
        }

        // Менеджер имеет доступ только к назначенным магазинам
        if ($user->isManager()) {
            return $user->hasAccessToShop($shop->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Только администраторы могут создавать магазины
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDeveloper();
    }

    /**
     * Determine whether the user can update the model.
     * Только администратор магазина или разработчик
     */
    public function update(User $user, Shop $shop): bool
    {
        if ($user->isDeveloper()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $shop->admin_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * Только администратор магазина или разработчик
     */
    public function delete(User $user, Shop $shop): bool
    {
        if ($user->isDeveloper()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $shop->admin_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Shop $shop): bool
    {
        return $this->update($user, $shop);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Shop $shop): bool
    {
        return $this->delete($user, $shop);
    }
}
