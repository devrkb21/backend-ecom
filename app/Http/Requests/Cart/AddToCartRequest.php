<?php

namespace App\Http\Requests\Cart;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }

                    $productId = (int) $this->input('product_id');
                    $variantBelongsToProduct = ProductVariant::query()
                        ->where('id', (int) $value)
                        ->where('product_id', $productId)
                        ->exists();

                    if (! $variantBelongsToProduct) {
                        $fail('The selected variant is invalid for the selected product.');
                    }
                },
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
