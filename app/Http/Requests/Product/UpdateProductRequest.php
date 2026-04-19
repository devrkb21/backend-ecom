<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $productId = $this->route('product');
        $stockRules = Product::isStockEnabled()
            ? ['sometimes', 'integer', 'min:0']
            : ['nullable', 'integer', 'min:0'];

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'regular_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products')->ignore($productId)],
            'stock_quantity' => $stockRules,
            'image' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }
}
