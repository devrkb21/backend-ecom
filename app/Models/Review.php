<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'images',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'unhelpful_count',
        'admin_reply',
        'admin_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'pros' => 'array',
            'cons' => 'array',
            'images' => 'array',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'helpful_count' => 'integer',
            'unhelpful_count' => 'integer',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for featured reviews
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for verified purchases
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope by rating
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Check if user has voted on this review
     */
    public function hasVotedBy(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's vote on this review
     */
    public function getUserVote(int $userId): ?bool
    {
        $vote = $this->votes()->where('user_id', $userId)->first();
        return $vote ? $vote->is_helpful : null;
    }

    /**
     * Calculate helpfulness percentage
     */
    public function getHelpfulnessPercentageAttribute(): ?float
    {
        $total = $this->helpful_count + $this->unhelpful_count;
        if ($total === 0) {
            return null;
        }
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Add admin reply
     */
    public function addAdminReply(string $reply): void
    {
        $this->update([
            'admin_reply' => $reply,
            'admin_replied_at' => now(),
        ]);
    }

    /**
     * Vote on review
     */
    public function vote(int $userId, bool $isHelpful): void
    {
        $existingVote = $this->votes()->where('user_id', $userId)->first();

        if ($existingVote) {
            // Update existing vote
            if ($existingVote->is_helpful !== $isHelpful) {
                // Switching vote
                if ($isHelpful) {
                    $this->increment('helpful_count');
                    $this->decrement('unhelpful_count');
                } else {
                    $this->decrement('helpful_count');
                    $this->increment('unhelpful_count');
                }
                $existingVote->update(['is_helpful' => $isHelpful]);
            }
        } else {
            // New vote
            $this->votes()->create([
                'user_id' => $userId,
                'is_helpful' => $isHelpful,
            ]);

            if ($isHelpful) {
                $this->increment('helpful_count');
            } else {
                $this->increment('unhelpful_count');
            }
        }
    }

    /**
     * Remove vote
     */
    public function removeVote(int $userId): void
    {
        $vote = $this->votes()->where('user_id', $userId)->first();

        if ($vote) {
            if ($vote->is_helpful) {
                $this->decrement('helpful_count');
            } else {
                $this->decrement('unhelpful_count');
            }
            $vote->delete();
        }
    }
}
