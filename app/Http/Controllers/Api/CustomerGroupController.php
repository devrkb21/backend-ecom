<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerGroupController extends Controller
{
    /**
     * Check if a phone number qualifies for any customer loyalty group discount.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:20',
        ]);

        $phone = $request->input('phone');

        // Aggregate orders for this phone number
        $orderStats = Order::where('shipping_phone', $phone)
            ->whereNotIn('status', ['cancelled', 'failed', 'returned'])
            ->selectRaw('COUNT(id) as total_orders, SUM(total) as total_spent')
            ->first();

        $totalOrders = $orderStats->total_orders ?? 0;
        $totalSpent = $orderStats->total_spent ?? 0.0;

        // Find qualifying group
        $qualifyingGroup = CustomerGroup::getQualifyingGroup($totalOrders, $totalSpent, $phone);

        if (!$qualifyingGroup) {
            return response()->json([
                'success' => true,
                'has_group' => false,
                'message' => 'No active loyalty group for this number.',
                'data' => [
                    'total_orders' => $totalOrders,
                    'total_spent' => $totalSpent,
                ],
            ]);
        }

        $customerName = 'Customer';
        $latestOrder = Order::where('shipping_phone', $phone)->latest('id')->first();
        if ($latestOrder) {
            $customerName = trim($latestOrder->shipping_name) ?: 'Customer';
            if ($latestOrder->user) {
                $customerName = trim($latestOrder->user->name) ?: $customerName;
            }
        }

        $message = $qualifyingGroup->custom_message ?? 'Congratulations! You qualify for a loyalty discount.';
        $message = str_ireplace('{name}', $customerName, $message);

        return response()->json([
            'success' => true,
            'has_group' => true,
            'message' => $message,
            'data' => [
                'group_name' => $qualifyingGroup->name,
                'discount_percentage' => $qualifyingGroup->discount_percentage,
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
            ],
        ]);
    }
}
