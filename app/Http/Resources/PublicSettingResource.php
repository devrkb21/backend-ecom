<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSettingResource extends JsonResource
{
    /**
     * Transform settings for public/frontend consumption
     * Grouped by setting group with only key-value pairs
     */
    public static function formatGrouped(array $settings): array
    {
        $formatted = [];

        foreach ($settings as $group => $items) {
            $formatted[$group] = collect($items)->mapWithKeys(function ($value, $key) use ($group) {
                return [$key => $value];
            })->toArray();
        }

        return $formatted;
    }

    /**
     * Format a single group's settings
     */
    public static function formatGroup(array $settings): array
    {
        return collect($settings)->mapWithKeys(function ($value, $key) {
            return [$key => $value];
        })->toArray();
    }

    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->getValue(),
        ];
    }

    protected function getValue(): mixed
    {
        $value = $this->value;

        // Convert image paths to full URLs
        if ($this->type === 'image' && $value) {
            $normalized = ltrim((string) $value, '/');

            return (str_starts_with($normalized, 'media/') || str_starts_with($normalized, 'storage/'))
                ? asset($normalized)
                : asset('storage/' . $normalized);
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }
}
