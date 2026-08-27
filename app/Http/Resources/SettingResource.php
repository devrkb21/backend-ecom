<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->value;

        // Convert image paths to full URLs
        if ($this->type === 'image' && $value) {
            $normalized = ltrim((string) $value, '/');
            $value = (str_starts_with($normalized, 'media/') || str_starts_with($normalized, 'storage/'))
                ? asset($normalized)
                : asset('storage/'.$normalized);
        }

        // Cast value based on type
        $value = $this->castValue($value);

        return [
            'id' => $this->id,
            'group' => $this->group,
            'key' => $this->key,
            'value' => $value,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function castValue(mixed $value): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }
}
