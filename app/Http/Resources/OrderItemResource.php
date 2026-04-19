<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variantAttributes = $this->resolveVariantAttributes();
        $variantSku = $this->resolveVariantSku();
        $variantName = $this->resolveVariantName($variantAttributes, $variantSku);
        $variantSummary = $this->resolveVariantSummary($variantAttributes, $variantSku);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->product_variant_id,
            'variant_name' => $variantName,
            'variant_sku' => $variantSku,
            'variant_attributes' => $variantAttributes,
            'variant_summary' => $variantSummary,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'total' => (float) $this->total,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveVariantSku(): ?string
    {
        if (!$this->product_variant_id) {
            return null;
        }

        $variantSku = $this->relationLoaded('variant')
            ? trim((string) ($this->variant?->sku ?? ''))
            : '';

        if ($variantSku !== '') {
            return $variantSku;
        }

        $snapshotSku = trim((string) ($this->product_sku ?? ''));

        return $snapshotSku !== '' ? $snapshotSku : null;
    }

    private function resolveVariantAttributes(): array
    {
        if (
            !$this->product_variant_id
            || !$this->relationLoaded('variant')
            || !$this->variant
            || !$this->variant->relationLoaded('attributeValues')
        ) {
            return [];
        }

        return $this->variant->attributeValues
            ->map(function ($attributeValue) {
                $attributeName = trim((string) ($attributeValue->attribute?->name ?? ''));
                $value = trim((string) ($attributeValue->value ?? ''));

                if ($value === '') {
                    return null;
                }

                return [
                    'attribute_name' => $attributeName !== '' ? $attributeName : null,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveVariantName(array $variantAttributes, ?string $variantSku): ?string
    {
        if (!$this->product_variant_id) {
            return null;
        }

        $variantName = $this->relationLoaded('variant')
            ? trim((string) ($this->variant?->name ?? ''))
            : '';

        if ($variantName !== '') {
            return $variantName;
        }

        if (!empty($variantAttributes)) {
            $attributeOnlyName = collect($variantAttributes)
                ->map(function (array $attribute): string {
                    $attributeName = trim((string) ($attribute['attribute_name'] ?? ''));
                    $value = trim((string) ($attribute['value'] ?? ''));

                    if ($value === '') {
                        return '';
                    }

                    return $attributeName !== '' ? "{$attributeName}: {$value}" : $value;
                })
                ->filter(static fn (string $value): bool => $value !== '')
                ->implode(', ');

            if ($attributeOnlyName !== '') {
                return $attributeOnlyName;
            }
        }

        if ($variantSku !== null) {
            return $variantSku;
        }

        return 'Variant #' . $this->product_variant_id;
    }

    private function resolveVariantSummary(array $variantAttributes, ?string $variantSku): ?string
    {
        if (!$this->product_variant_id) {
            return null;
        }

        $summaryParts = [];

        if (!empty($variantAttributes)) {
            $attributeSummary = collect($variantAttributes)
                ->map(function (array $attribute): string {
                    $attributeName = trim((string) ($attribute['attribute_name'] ?? ''));
                    $value = trim((string) ($attribute['value'] ?? ''));

                    if ($value === '') {
                        return '';
                    }

                    return $attributeName !== '' ? "{$attributeName}: {$value}" : $value;
                })
                ->filter(static fn (string $value): bool => $value !== '')
                ->implode(', ');

            if ($attributeSummary !== '') {
                $summaryParts[] = $attributeSummary;
            }
        }

        if ($variantSku !== null) {
            $summaryParts[] = "SKU: {$variantSku}";
        }

        if (empty($summaryParts)) {
            return 'Variant #' . $this->product_variant_id;
        }

        return implode(' | ', $summaryParts);
    }
}
