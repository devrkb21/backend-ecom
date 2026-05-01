<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasSubmittedVariants = is_array($this->input('variants'))
            && !empty($this->input('variants'));
        $isVariableProduct = $this->boolean('is_variable') || $hasSubmittedVariants;

        $stockRules = ['nullable', 'integer', 'min:0'];

        if (Product::isStockEnabled() && !$isVariableProduct) {
            $stockRules[0] = 'required';
        }

        $regularPriceRules = $isVariableProduct
            ? ['nullable', 'numeric', 'min:0']
            : ['required', 'numeric', 'min:0.01'];

        $salePriceRules = ['nullable', 'numeric', 'min:0'];
        if (!$isVariableProduct) {
            $salePriceRules[] = 'lt:regular_price';
        }

        return [
            'category_id' => ['required', 'array', 'min:1'],
            'category_id.*' => ['integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:5000'],
            'regular_price' => $regularPriceRules,
            'sale_price' => $salePriceRules,
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'stock_quantity' => $stockRules,
            'is_variable' => ['nullable', 'boolean'],
            'free_delivery' => ['nullable', 'boolean'],
            'dynamic_discount_tiers' => ['nullable', 'array'],
            'dynamic_discount_tiers.*.min_quantity' => ['nullable', 'integer', 'min:1'],
            'dynamic_discount_tiers.*.unit_price' => ['nullable', 'numeric', 'min:0.01'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'additional_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_new' => ['boolean'],
            'is_bestseller' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'is_new' => $this->boolean('is_new'),
            'is_bestseller' => $this->boolean('is_bestseller'),
            'is_variable' => $this->boolean('is_variable'),
            'free_delivery' => $this->boolean('free_delivery'),
        ]);
    }

    public function messages(): array
    {
        return [
            'regular_price.min' => 'Regular price must be greater than 0.',
            'sale_price.lt' => 'Sale price must be less than regular price.',
            'stock_quantity.min' => 'Stock cannot be negative.',
            'additional_images.*.image' => 'All additional files must be images.',
            'additional_images.*.max' => 'Additional images must be less than 2MB.',
        ];
    }
}
