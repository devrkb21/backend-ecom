<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'regular_price' => ['required', 'numeric', 'min:0.01'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
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
