<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products'],
            'description' => ['nullable', 'string'],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['required', 'string', 'max:100', 'unique:products'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }
}
