<?php

namespace App\Http\Requests\Cart;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }

                    $productId = (int) $this->route('productId');
                    $variantBelongsToProduct = ProductVariant::query()
                        ->where('id', (int) $value)
                        ->where('product_id', $productId)
                        ->exists();

                    if (! $variantBelongsToProduct) {
                        $fail('The selected variant is invalid for the selected product.');
                    }
                },
            ],
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
