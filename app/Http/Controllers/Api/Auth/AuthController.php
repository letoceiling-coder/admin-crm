<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Models\Role;
use App\Services\AdminApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Получить CSRF cookie для SPA
     */
    public function csrfCookie(): JsonResponse
    {
        return response()->json(['message' => 'CSRF cookie set']);
    }

    /**
     * Авторизация пользователя
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load('role');

        return response()->json([
            'user' => $user,
            'message' => 'Успешный вход в систему',
        ]);
    }

    /**
     * Регистрация нового администратора.
     * После успешной регистрации отправляет заявку на подписку в ADMIN,
     * сохраняет api_token в settings.
     * 
     * Если ADMIN не ответил или произошла ошибка:
     * - Удаляет созданного пользователя (откат транзакции)
     * - Выходит из сессии
     * - Возвращает ошибку с деталями
     */
    public function register(RegisterRequest $request, AdminApiService $adminApi): JsonResponse
    {
        $data = $request->validated();

        // Используем транзакцию для атомарности операции
        return DB::transaction(function () use ($data, $adminApi, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => Role::LEVEL_ADMIN,
            ]);

            // Авторизуем пользователя
            Auth::login($user);
            $user->load('role');

            // Отправляем заявку в ADMIN
            $domain = config('integration.crm_domain')
                ?: (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
            
            $apiResult = $adminApi->sendSubscriptionApplication($domain, $user->name, $user->email);

            // Если ADMIN не ответил или произошла ошибка - откатываем регистрацию
            if (!$apiResult['success']) {
                // Выходим из сессии
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Логируем ошибку
                Log::error('CRM register: ошибка при отправке заявки в ADMIN, регистрация отменена', [
                    'domain' => $domain,
                    'email' => $data['email'],
                    'error' => $apiResult['error'] ?? 'unknown',
                ]);

                // Выбрасываем исключение для отката транзакции
                // Пользователь будет автоматически удален при откате
                // Используем ValidationException для правильного формата ответа
                throw ValidationException::withMessages([
                    'admin_api' => [
                        $apiResult['error'] ?? 'Ошибка связи с сервером администрации',
                    ],
                ]);
            }

            // Успешная регистрация
            return response()->json([
                'user' => $user,
                'message' => 'Регистрация успешна',
            ], 201);
        });
    }

    /**
     * Выход из системы
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Успешный выход из системы']);
    }

    /**
     * Получить текущего пользователя
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role',
            'ownedShops',
            'shops' => function ($query) {
                $query->with('role');
            }
        ]);

        return response()->json(['user' => $user]);
    }

    /**
     * Отправка ссылки для восстановления пароля
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Ссылка для восстановления пароля отправлена на email',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Сброс пароля
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Пароль успешно изменен',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
