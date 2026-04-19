<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'is_default' => $this->is_default,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'upazila_id' => $this->upazila_id,
            'union_id' => $this->union_id,
            'area' => $this->area,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'division' => $this->whenLoaded('division', fn () => [
                'id' => $this->division?->id,
                'name' => $this->division?->name,
                'bn_name' => $this->division?->bn_name,
            ]),
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district?->id,
                'name' => $this->district?->name,
                'bn_name' => $this->district?->bn_name,
            ]),
            'upazila' => $this->whenLoaded('upazila', fn () => [
                'id' => $this->upazila?->id,
                'name' => $this->upazila?->name,
                'bn_name' => $this->upazila?->bn_name,
            ]),
            'union' => $this->whenLoaded('union', fn () => [
                'id' => $this->union?->id,
                'name' => $this->union?->name,
                'bn_name' => $this->union?->bn_name,
            ]),
            'instructions' => $this->instructions,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'full_address' => $this->full_address,
            'formatted_address' => $this->formatted_address,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
