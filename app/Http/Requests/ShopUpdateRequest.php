<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopUpdateRequest extends FormRequest
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
        $shop = $this->route('shop');
        $shopId = $shop instanceof \App\Models\Shop ? $shop->id : $shop;
        
        return [
            'name' => ['required', 'string', 'max:255', 'unique:shops,name,' . $shopId],
            'inn' => ['nullable', 'string', 'max:20'],
            'ogrn' => ['nullable', 'string', 'max:20'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'addresses' => ['nullable', 'array'],
            'addresses.*' => ['required', 'string', 'max:500'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['required', 'string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.field_name' => ['required', 'string', 'max:255'],
            'custom_fields.*.field_value' => ['nullable', 'string'],
        ];
    }
}
