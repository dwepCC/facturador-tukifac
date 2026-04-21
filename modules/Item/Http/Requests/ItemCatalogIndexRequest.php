<?php

namespace Modules\Item\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Item\Models\Category;

class ItemCatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            // No usar exists:categories,id: en multi-tenant apunta a la conexión por defecto (BD central).
            'category_id' => ['sometimes', 'nullable', 'integer', $this->categoryExistsRule()],
            'apply_store' => ['sometimes', 'boolean'],
            'include_inactive' => ['sometimes', 'boolean'],
            'include_services' => ['sometimes', 'boolean'],
            'with_gallery' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Valida contra la tabla categories en la conexión del tenant (ModelTenant).
     */
    private function categoryExistsRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }
            if (! Category::query()->whereKey((int) $value)->exists()) {
                $fail('La categoría indicada no existe.');
            }
        };
    }
}
