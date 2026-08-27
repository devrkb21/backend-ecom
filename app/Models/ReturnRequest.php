<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'type',
        'status',
        'reason',
        'reason_details',
        'description',
        'refund_method',
        'refund_amount',
        'restocking_fee',
        'shipping_deduction',
        'final_refund_amount',
        'refund_transaction_id',
        'refund_status',
        'refunded_at',
        'refund_error',
        'return_tracking_number',
        'return_carrier',
        'shipped_at',
        'received_at',
        'images',
        'processed_by',
        'processed_at',
        'admin_notes',
        'rejection_reason',
        'customer_notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:2',
            'restocking_fee' => 'decimal:2',
            'shipping_deduction' => 'decimal:2',
            'final_refund_amount' => 'decimal:2',
            'images' => 'array',
            'refunded_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $return->return_number = self::generateReturnNumber();
            }
        });
    }

    public static function generateReturnNumber(): string
    {
        $prefix = 'RET';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['approved', 'processing', 'received']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRefundPending($query)
    {
        return $query->where('refund_status', 'pending')
            ->whereIn('status', ['approved', 'processing', 'received']);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'processing' => 'Processing',
            'received' => 'Product Received',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'processing' => 'primary',
            'received' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'damaged' => 'Damaged Product',
            'wrong_item' => 'Wrong Item Received',
            'not_as_described' => 'Not as Described',
            'changed_mind' => 'Changed Mind',
            'defective' => 'Defective Product',
            'size_issue' => 'Size Issue',
            'quality_issue' => 'Quality Issue',
            'late_delivery' => 'Late Delivery',
            'other' => 'Other',
            default => ucfirst($this->reason),
        };
    }

    public function getRefundStatusLabelAttribute(): string
    {
        return match ($this->refund_status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            default => ucfirst($this->refund_status),
        };
    }

    public function getRefundStatusColorAttribute(): string
    {
        return match ($this->refund_status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'return' ? 'Return & Refund' : 'Refund Only';
    }

    // ==================== METHODS ====================

    /**
     * Check if return can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if return can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund can be processed
     */
    public function canProcessRefund(): bool
    {
        // For refund-only, can process immediately after approval
        if ($this->type === 'refund') {
            return in_array($this->status, ['approved', 'processing'])
                && $this->refund_status !== 'completed';
        }

        // For returns, need to receive product first
        return in_array($this->status, ['received', 'processing'])
            && $this->refund_status !== 'completed';
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefundAmount(): float
    {
        $itemsTotal = (float) $this->items->sum('total_price');
        $restockingFee = (float) ($this->restocking_fee ?? 0);
        $shippingDeduction = (float) ($this->shipping_deduction ?? 0);
        $finalAmount = max(0.0, $itemsTotal - $restockingFee - $shippingDeduction);

        $this->update([
            'refund_amount' => $itemsTotal,
            'final_refund_amount' => $finalAmount,
        ]);

        return $finalAmount;
    }

    /**
     * Mark product as received
     */
    public function markAsReceived(?string $notes = null): self
    {
        $this->update([
            'status' => 'received',
            'received_at' => now(),
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);

        return $this;
    }

    /**
     * Mark refund as processing
     */
    public function markRefundProcessing(?string $transactionId = null): self
    {
        $this->update([
            'status' => 'processing',
            'refund_status' => 'processing',
            'refund_transaction_id' => $transactionId,
        ]);

        return $this;
    }

    /**
     * Mark refund as completed
     */
    public function markRefundCompleted(string $transactionId): self
    {
        $this->update([
            'status' => 'completed',
            'refund_status' => 'completed',
            'refund_transaction_id' => $transactionId,
            'refunded_at' => now(),
        ]);

        // Restore stock if return type
        if ($this->type === 'return') {
            foreach ($this->items as $item) {
                $item->product->incrementStock($item->quantity);
            }
        }

        return $this;
    }

    /**
     * Mark refund as failed
     */
    public function markRefundFailed(string $error): self
    {
        $this->update([
            'refund_status' => 'failed',
            'refund_error' => $error,
        ]);

        return $this;
    }

    /**
     * Get payment method from order
     */
    public function getPaymentMethod(): ?string
    {
        return $this->order->payment_method;
    }

    /**
     * Get original transaction ID from order
     */
    public function getOriginalTransactionId(): ?string
    {
        return $this->order->transaction_id;
    }

    /**
     * Check if eligible for auto refund
     */
    public function isEligibleForAutoRefund(): bool
    {
        $paymentMethod = $this->getPaymentMethod();

        return in_array($paymentMethod, ['stripe', 'bkash'])
            && $this->refund_method === 'original'
            && $this->getOriginalTransactionId();
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if received
     */
    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get the total amount
     */
    public function getTotalAmountAttribute(): float
    {
        if (isset($this->attributes['refund_amount']) && $this->attributes['refund_amount']) {
            return (float) $this->attributes['refund_amount'];
        }

        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get timeline for display
     */
    public function getTimeline(): array
    {
        $timeline = [];

        // Submitted
        $timeline[] = [
            'status' => 'submitted',
            'title' => 'Return Request Submitted',
            'description' => 'Return request was submitted by customer',
            'date' => $this->created_at->toIso8601String(),
            'completed' => true,
        ];

        // Approved/Rejected
        if ($this->status !== 'pending' && $this->status !== 'cancelled') {
            if ($this->isRejected()) {
                $timeline[] = [
                    'status' => 'rejected',
                    'title' => 'Request Rejected',
                    'description' => $this->rejection_reason ?? 'Return request was rejected',
                    'date' => $this->updated_at->toIso8601String(),
                    'completed' => true,
                ];
            } else {
                $timeline[] = [
                    'status' => 'approved',
                    'title' => 'Request Approved',
                    'description' => 'Return request was approved',
                    'date' => $this->updated_at->toIso8601String(),
                    'completed' => true,
                ];
            }
        }

        // Items Shipped (for returns)
        if ($this->type === 'return' && $this->shipped_at) {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'Items Shipped',
                'description' => $this->return_tracking_number
                    ? "Tracking: {$this->return_tracking_number}"
                    : 'Items shipped back',
                'date' => $this->shipped_at->toIso8601String(),
                'completed' => true,
            ];
        }

        // Items Received (for returns)
        if ($this->type === 'return' && $this->received_at) {
            $timeline[] = [
                'status' => 'received',
                'title' => 'Items Received',
                'description' => 'Returned items were received',
                'date' => $this->received_at->toIso8601String(),
                'completed' => true,
            ];
        }

        // Refund Processing
        if ($this->refund_status === 'processing') {
            $timeline[] = [
                'status' => 'refund_processing',
                'title' => 'Refund Processing',
                'description' => 'Refund is being processed',
                'date' => $this->updated_at->toIso8601String(),
                'completed' => true,
            ];
        }

        // Refund Completed
        if ($this->refunded_at) {
            $timeline[] = [
                'status' => 'refund_completed',
                'title' => 'Refund Completed',
                'description' => '৳'.number_format((float) ($this->final_refund_amount ?? 0), 2).' refunded',
                'date' => $this->refunded_at->toIso8601String(),
                'completed' => true,
            ];
        }

        // Refund Failed
        if ($this->refund_status === 'failed') {
            $timeline[] = [
                'status' => 'refund_failed',
                'title' => 'Refund Failed',
                'description' => $this->refund_error ?? 'Refund processing failed',
                'date' => $this->updated_at->toIso8601String(),
                'completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Approve return request (alternative signature for controller)
     */
    public function approve(array $data = []): self
    {
        $updateData = [
            'status' => 'approved',
            'refund_status' => 'pending',
            'processed_at' => now(),
        ];

        if (isset($data['processed_by'])) {
            $updateData['processed_by'] = $data['processed_by'];
        }
        if (isset($data['admin_notes'])) {
            $updateData['admin_notes'] = $data['admin_notes'];
        }
        if (isset($data['restocking_fee'])) {
            $updateData['restocking_fee'] = $data['restocking_fee'];
        }
        if (isset($data['final_refund_amount'])) {
            $updateData['final_refund_amount'] = $data['final_refund_amount'];
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Reject return request (alternative signature for controller)
     */
    public function reject(array $data = []): self
    {
        $updateData = [
            'status' => 'rejected',
            'processed_at' => now(),
        ];

        if (isset($data['processed_by'])) {
            $updateData['processed_by'] = $data['processed_by'];
        }
        if (isset($data['admin_notes'])) {
            $updateData['rejection_reason'] = $data['admin_notes'];
        }

        $this->update($updateData);

        return $this;
    }
}
