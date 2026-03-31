<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_icon' => $this->status_icon,
            'location' => $this->location,
            'description' => $this->description,
            'carrier_status' => $this->carrier_status,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'occurred_at_human' => $this->occurred_at->diffForHumans(),
        ];
    }
}
