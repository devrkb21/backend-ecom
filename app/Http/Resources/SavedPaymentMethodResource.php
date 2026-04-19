<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedPaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'card_brand' => $this->card_brand,
            'card_last_four' => $this->card_last_four,
            'card_exp_month' => $this->card_exp_month,
            'card_exp_year' => $this->card_exp_year,
            'cardholder_name' => $this->cardholder_name,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
