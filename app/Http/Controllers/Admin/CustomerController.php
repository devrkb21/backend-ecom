<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Order::query()
            ->select(
                'shipping_phone as phone',
                DB::raw('MAX(shipping_name) as latest_name'),
                DB::raw('MAX(shipping_email) as latest_email'),
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(total) as total_spent'),
                DB::raw('MAX(created_at) as last_order_date')
            )
            ->whereNotNull('shipping_phone')
            ->where('shipping_phone', '!=', '')
            ->whereNotIn('status', ['cancelled', 'failed', 'returned'])
            ->groupBy('shipping_phone');

        if ($search) {
            $query->having('phone', 'like', "%{$search}%")
                  ->orHaving('latest_name', 'like', "%{$search}%")
                  ->orHaving('latest_email', 'like', "%{$search}%");
        }

        $customers = $query->orderBy('last_order_date', 'desc')->paginate(20);

        // Calculate their group on the fly for display
        $customers->getCollection()->transform(function ($customer) {
            $customer->group = CustomerGroup::getQualifyingGroup($customer->total_orders, $customer->total_spent);
            return $customer;
        });

        return view('admin.customers.index', compact('customers', 'search'));
    }
}
