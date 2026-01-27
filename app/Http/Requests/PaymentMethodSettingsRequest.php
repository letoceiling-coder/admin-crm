<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'available_for_delivery' => ['nullable', 'boolean'],
            'available_for_pickup' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['required', Rule::in(['none', 'percentage', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'min_cart_amount' => ['nullable', 'numeric', 'min:0'],
            'show_notification' => ['nullable', 'boolean'],
            'notification_text' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'discount_type.required' => 'Тип скидки обязателен',
            'discount_type.in' => 'Тип скидки должен быть "none", "percentage" или "fixed"',
            'discount_value.numeric' => 'Значение скидки должно быть числом',
            'discount_value.min' => 'Значение скидки не может быть отрицательным',
            'min_cart_amount.numeric' => 'Минимальная сумма корзины должна быть числом',
            'min_cart_amount.min' => 'Минимальная сумма корзины не может быть отрицательной',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
            'notification_text.max' => 'Текст уведомления не может превышать 500 символов',
        ];
    }
}
