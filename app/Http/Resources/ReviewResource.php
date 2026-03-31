<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'pros' => $this->pros ?? [],
            'cons' => $this->cons ?? [],
            'images' => $this->images ?? [],
            'image_urls' => $this->images ? array_map(function ($path) {
                return asset('storage/' . $path);
            }, $this->images) : [],
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_approved' => $this->is_approved,
            'is_featured' => $this->is_featured,
            'helpful_count' => $this->helpful_count,
            'unhelpful_count' => $this->unhelpful_count,
            'helpfulness_percentage' => $this->helpfulness_percentage,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'initial' => strtoupper(substr($this->user->name, 0, 1)),
            ],
            'product' => $this->when($this->relationLoaded('product'), function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'image' => $this->product->primary_image,
                    'image_url' => $this->product->primary_image_url,
                ];
            }),
            'admin_reply' => $this->admin_reply,
            'admin_replied_at' => $this->admin_replied_at?->toISOString(),
            'user_vote' => $currentUserId ? $this->getUserVote($currentUserId) : null,
            'is_own_review' => $currentUserId ? $this->user_id === $currentUserId : false,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
