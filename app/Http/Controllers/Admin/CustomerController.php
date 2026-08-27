<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\Order;
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

        $limit = $request->input('per_page', 20);
        $customers = $query->orderBy('last_order_date', 'desc')->paginate($limit)->withQueryString();

        // Calculate their group on the fly for display
        $customers->getCollection()->transform(function ($customer) {
            $customer->group = CustomerGroup::getQualifyingGroup($customer->total_orders, $customer->total_spent, $customer->phone);

            return $customer;
        });

        $groups = CustomerGroup::where('is_active', true)->orderBy('sort_order', 'asc')->get();

        $kpis = [
            'total_customers' => Order::whereNotNull('shipping_phone')->where('shipping_phone', '!=', '')->distinct('shipping_phone')->count('shipping_phone'),
            'total_revenue' => Order::whereNotIn('status', ['cancelled', 'failed', 'returned'])->sum('total'),
            'total_orders' => Order::whereNotIn('status', ['cancelled', 'failed', 'returned'])->count(),
        ];

        return view('admin.customers.index', compact('customers', 'search', 'groups', 'kpis'));
    }

    public function show($phone)
    {
        $orders = Order::where('shipping_phone', $phone)->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty()) {
            return redirect()->route('admin.customers.index')->with('error', 'Customer not found.');
        }

        // Aggregate data
        $totalOrders = $orders->whereNotIn('status', ['cancelled', 'failed', 'returned'])->count();
        $totalSpent = $orders->whereNotIn('status', ['cancelled', 'failed', 'returned'])->sum('total');
        $latestName = $orders->first()->shipping_name;
        $latestEmail = $orders->first()->shipping_email;
        $group = CustomerGroup::getQualifyingGroup($totalOrders, $totalSpent, $phone);

        // Extract unique addresses
        $addresses = [];
        foreach ($orders as $order) {
            $addrKey = md5(strtolower($order->shipping_address.$order->shipping_city));
            if (! isset($addresses[$addrKey]) && $order->shipping_address) {
                $addresses[$addrKey] = [
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'last_used' => $order->created_at,
                    'times_used' => 1,
                ];
            } elseif (isset($addresses[$addrKey])) {
                $addresses[$addrKey]['times_used']++;
            }
        }

        $customer = (object) [
            'phone' => $phone,
            'name' => $latestName,
            'email' => $latestEmail,
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'group' => $group,
            'first_order_date' => $orders->last()->created_at,
            'last_order_date' => $orders->first()->created_at,
            'addresses' => array_values($addresses),
        ];

        $groups = CustomerGroup::where('is_active', true)->orderBy('sort_order', 'asc')->get();

        return view('admin.customers.show', compact('customer', 'orders', 'groups'));
    }

    public function update(Request $request, $phone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $newPhone = $request->input('phone');

        // Update all orders with this phone
        Order::where('shipping_phone', $phone)->update([
            'shipping_name' => $request->input('name'),
            'shipping_email' => $request->input('email'),
            'shipping_phone' => $newPhone,
        ]);

        return back()->with('success', 'Customer details updated successfully.');
    }

    public function destroy($phone)
    {
        Order::where('shipping_phone', $phone)->delete();

        return back()->with('success', 'Customer orders deleted successfully.');
    }

    public function assignGroup(Request $request, $phone)
    {
        $request->validate([
            'group_id' => 'nullable|exists:customer_groups,id',
        ]);

        $groupId = $request->input('group_id');
        $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);

        // Remove from all manual lists first
        $allGroups = CustomerGroup::all();
        foreach ($allGroups as $g) {
            if ($g->manual_numbers) {
                $numbers = array_map('trim', explode(',', $g->manual_numbers));
                $numbers = array_map(fn ($num) => preg_replace('/[^0-9]/', '', $num), $numbers);

                if (in_array($cleanedPhone, $numbers)) {
                    $numbers = array_filter($numbers, fn ($n) => $n !== $cleanedPhone);
                    $g->manual_numbers = empty($numbers) ? null : implode(', ', $numbers);
                    $g->save();
                }
            }
        }

        // Add to selected group
        if ($groupId) {
            $group = CustomerGroup::find($groupId);
            $numbers = $group->manual_numbers ? array_map('trim', explode(',', $group->manual_numbers)) : [];
            $numbers[] = $cleanedPhone;
            $group->manual_numbers = implode(', ', array_unique($numbers));
            $group->save();

            return back()->with('success', 'Customer assigned to group manually.');
        }

        return back()->with('success', 'Customer manual group assignment removed. They will now use auto-assign rules.');
    }
}
