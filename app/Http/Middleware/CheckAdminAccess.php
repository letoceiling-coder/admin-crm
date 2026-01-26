<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class CheckAdminAccess
{
    /**
     * Handle an incoming request.
     * Проверяет, что пользователь является администратором или менеджером
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Не авторизован'], 401);
        }

        // Разработчик имеет доступ ко всему
        if ($user->isDeveloper()) {
            return $next($request);
        }

        // Администратор имеет доступ
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Менеджер имеет доступ
        if ($user->isManager()) {
            return $next($request);
        }

        // Остальные роли не имеют доступа
        return response()->json(['message' => 'Доступ запрещен'], 403);
    }
}
