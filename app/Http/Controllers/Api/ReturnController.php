<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReturnController extends Controller
{
    /**
     * Get list of user's return requests
     */
    public function index(Request $request)
    {
        $returns = ReturnRequest::where('user_id', auth()->id())
            ->with(['order:id,order_number,total', 'items.product:id,name,slug'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $returns,
        ]);
    }

    /**
     * Get details of a specific return request
     */
    public function show(ReturnRequest $return)
    {
        // Ensure user owns this return
        if ($return->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Return request not found.',
            ], 404);
        }

        $return->load([
            'order:id,order_number,total,payment_method,created_at',
            'order.items.product:id,name,slug,thumbnail',
            'items.product:id,name,slug,thumbnail',
            'items.orderItem',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'return_number' => $return->return_number,
                'order_number' => $return->order->order_number,
                'type' => $return->type,
                'status' => $return->status,
                'status_label' => $return->status_label,
                'reason' => $return->reason,
                'reason_label' => $return->reason_label,
                'description' => $return->description,
                'total_amount' => $return->total_amount,
                'refund_amount' => $return->final_refund_amount,
                'restocking_fee' => $return->restocking_fee,
                'refund_method' => $return->refund_method,
                'refund_status' => $return->refund_status,
                'return_shipping_label' => $return->return_shipping_label,
                'return_tracking_number' => $return->return_tracking_number,
                'images' => $return->images,
                'items' => $return->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'N/A',
                        'product_thumbnail' => $item->product->thumbnail ?? null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->total_price,
                        'reason' => $item->notes,
                        'condition' => $item->condition,
                    ];
                }),
                'timeline' => $return->getTimeline(),
                'can_cancel' => $return->isPending(),
                'created_at' => $return->created_at->toIso8601String(),
                'updated_at' => $return->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new return/refund request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => [
                'required',
                'exists:orders,id',
                Rule::exists('orders', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->whereIn('status', ['delivered', 'completed']);
                }),
            ],
            'type' => 'required|in:return,refund',
            'reason' => 'required|in:defective,wrong_item,not_as_described,changed_mind,damaged_shipping,missing_parts,damaged,size_issue,quality_issue,late_delivery,other',
            'description' => 'required|string|max:2000',
            'refund_method' => 'nullable|in:original,store_credit,bank_transfer',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:500',
        ]);

        $order = Order::with('items')->findOrFail($validated['order_id']);

        $normalizedReason = match ($validated['reason']) {
            'damaged_shipping' => 'damaged',
            'missing_parts' => 'quality_issue',
            default => $validated['reason'],
        };

        // Check if order is within return period (e.g., 30 days)
        $returnPeriodDays = config('shop.return_period_days', 30);
        if ($order->delivered_at && $order->delivered_at->diffInDays(now()) > $returnPeriodDays) {
            return response()->json([
                'success' => false,
                'message' => "Return period has expired. Returns are only accepted within {$returnPeriodDays} days of delivery.",
            ], 422);
        }

        // Check if there's already a pending return for this order
        $existingReturn = ReturnRequest::where('order_id', $order->id)
            ->whereNotIn('status', ['rejected', 'cancelled', 'completed'])
            ->first();

        if ($existingReturn) {
            return response()->json([
                'success' => false,
                'message' => 'A return request already exists for this order.',
                'return_number' => $existingReturn->return_number,
            ], 422);
        }

        // Validate items belong to the order and quantities are valid
        $orderItemIds = $order->items->pluck('id')->toArray();
        $totalAmount = 0;
        $itemsData = [];

        foreach ($validated['items'] as $item) {
            if (! in_array($item['order_item_id'], $orderItemIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more items do not belong to this order.',
                ], 422);
            }

            $orderItem = $order->items->firstWhere('id', $item['order_item_id']);

            // Check quantity doesn't exceed ordered quantity
            if ($item['quantity'] > $orderItem->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Return quantity for {$orderItem->product->name} exceeds ordered quantity.",
                ], 422);
            }

            // Check item hasn't already been returned
            $alreadyReturned = ReturnItem::whereHas('return', function ($q) use ($order) {
                $q->where('order_id', $order->id)
                    ->whereNotIn('status', ['rejected', 'cancelled']);
            })
                ->where('order_item_id', $orderItem->id)
                ->sum('quantity');

            $remainingReturnable = $orderItem->quantity - $alreadyReturned;

            if ($item['quantity'] > $remainingReturnable) {
                return response()->json([
                    'success' => false,
                    'message' => "You can only return {$remainingReturnable} unit(s) of {$orderItem->product->name}.",
                ], 422);
            }

            $itemTotal = $orderItem->price * $item['quantity'];
            $totalAmount += $itemTotal;

            $itemsData[] = [
                'order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $item['quantity'],
                'unit_price' => $orderItem->price,
                'total_price' => $itemTotal,
                'notes' => $item['reason'] ?? null,
            ];
        }

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('returns/'.date('Y/m'), 'public');
                $imagePaths[] = $path;
            }
        }

        DB::beginTransaction();

        try {
            // Create return request
            $return = ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'reason' => $normalizedReason,
                'description' => $validated['description'],
                'total_amount' => $totalAmount,
                'refund_method' => $validated['refund_method'] ?? 'original',
                'images' => $imagePaths,
            ]);

            // Create return items
            foreach ($itemsData as $itemData) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    ...$itemData,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return request submitted successfully. We will review it within 24-48 hours.',
                'data' => [
                    'return_number' => $return->return_number,
                    'status' => $return->status,
                    'estimated_review_time' => '24-48 hours',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded images on failure
            foreach ($imagePaths as $path) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit return request. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel a pending return request
     */
    public function cancel(ReturnRequest $return)
    {
        // Ensure user owns this return
        if ($return->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Return request not found.',
            ], 404);
        }

        if (! $return->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending return requests can be cancelled.',
            ], 422);
        }

        $return->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Return request cancelled successfully.',
        ]);
    }

    /**
     * Get return eligibility for an order
     */
    public function checkEligibility(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with('items.product')->findOrFail($validated['order_id']);

        // Check if user owns this order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Check order status
        if (! in_array($order->status, ['delivered', 'completed'])) {
            return response()->json([
                'success' => true,
                'eligible' => false,
                'reason' => 'Order must be delivered before you can request a return.',
            ]);
        }

        // Check return period
        $returnPeriodDays = config('shop.return_period_days', 30);
        $daysRemaining = $returnPeriodDays - ($order->delivered_at ? $order->delivered_at->diffInDays(now()) : 0);

        if ($daysRemaining < 0) {
            return response()->json([
                'success' => true,
                'eligible' => false,
                'reason' => 'Return period has expired.',
            ]);
        }

        // Check for existing return
        $existingReturn = ReturnRequest::where('order_id', $order->id)
            ->whereNotIn('status', ['rejected', 'cancelled', 'completed'])
            ->first();

        if ($existingReturn) {
            return response()->json([
                'success' => true,
                'eligible' => false,
                'reason' => 'A return request already exists for this order.',
                'existing_return_number' => $existingReturn->return_number,
            ]);
        }

        // Calculate returnable items
        $returnableItems = $order->items->map(function ($item) use ($order) {
            $alreadyReturned = ReturnItem::whereHas('return', function ($q) use ($order) {
                $q->where('order_id', $order->id)
                    ->whereNotIn('status', ['rejected', 'cancelled']);
            })
                ->where('order_item_id', $item->id)
                ->sum('quantity');

            return [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'N/A',
                'product_thumbnail' => $item->product->thumbnail ?? null,
                'unit_price' => $item->price,
                'ordered_quantity' => $item->quantity,
                'returnable_quantity' => max(0, $item->quantity - $alreadyReturned),
            ];
        })->filter(fn ($item) => $item['returnable_quantity'] > 0)->values();

        return response()->json([
            'success' => true,
            'eligible' => $returnableItems->isNotEmpty(),
            'days_remaining' => max(0, $daysRemaining),
            'returnable_items' => $returnableItems,
            'return_reasons' => [
                'defective' => 'Product is defective or damaged',
                'wrong_item' => 'Received wrong item',
                'not_as_described' => 'Product not as described',
                'changed_mind' => 'Changed my mind',
                'damaged_shipping' => 'Damaged during shipping',
                'missing_parts' => 'Missing parts or accessories',
                'damaged' => 'Product was damaged',
                'size_issue' => 'Size/fit issue',
                'quality_issue' => 'Quality issue',
                'late_delivery' => 'Late delivery',
                'other' => 'Other reason',
            ],
        ]);
    }

    /**
     * Upload additional images for a return request
     */
    public function uploadImages(Request $request, ReturnRequest $return)
    {
        // Ensure user owns this return
        if ($return->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Return request not found.',
            ], 404);
        }

        if (! $return->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot upload images to a processed return request.',
            ], 422);
        }

        $validated = $request->validate([
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $existingImages = $return->images ?? [];

        if (count($existingImages) + count($request->file('images')) > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum 5 images allowed per return request.',
            ], 422);
        }

        $newImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('returns/'.date('Y/m'), 'public');
            $newImages[] = $path;
        }

        $return->update([
            'images' => array_merge($existingImages, $newImages),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'images' => $return->fresh()->images,
        ]);
    }
}
