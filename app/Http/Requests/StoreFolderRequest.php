<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
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
        $userId = auth()->check() ? auth()->id() : null;
        $parentId = $this->input('parent_id');
        
        // Правило уникальности: имя должно быть уникальным в рамках одной папки (parent_id) и одного пользователя
        // Системные папки (user_id = NULL) могут иметь одинаковые имена с пользовательскими
        $uniqueRule = Rule::unique('folders', 'name')
            ->where(function ($query) use ($parentId, $userId) {
                if ($parentId) {
                    $query->where('parent_id', $parentId);
                } else {
                    $query->whereNull('parent_id');
                }
                
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->whereNull('user_id');
                }
            });
        
        return [
            'name' => ['required', $uniqueRule, 'string', 'max:255'],
            'slug' => ['nullable'],
            'parent_id' => ['nullable', 'int'],

        ];
    }
    protected function prepareForValidation() {
        $this->merge([
            'slug' => $this->slug ? $this->slug  : str(strtolower($this->name))->slug(),

        ]);
    }
    public function messages()
    {
        return [

            'name.unique' => "Папка с таким наименованием существует",
            'name.required' => "Заполните наименование папки",

        ];
    }
}
