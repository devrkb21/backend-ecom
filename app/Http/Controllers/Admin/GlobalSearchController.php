<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query || mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        $user = $request->user();

        // Search Orders
        if ($user->hasAdminPermission('orders.manage')) {
            $orders = Order::with('user')
                ->where('order_number', 'like', "%{$query}%")
                ->orWhereHas('user', function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->take(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'title' => 'Order #' . $order->order_number,
                        'subtitle' => $order->user ? $order->user->name : 'Guest',
                        'url' => route('admin.orders.show', $order->id),
                        'type' => 'Order',
                        'icon' => 'bi-cart'
                    ];
                });
            foreach ($orders as $item) $results[] = $item;
        }

        // Search Products, Categories, Attributes
        if ($user->hasAdminPermission('catalog.manage')) {
            $products = Product::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhereHas('variants.attributeValues', function ($q) use ($query) {
                    $q->where('value', 'like', "%{$query}%");
                })
                ->take(8)
                ->get()
                ->map(function ($product) {
                    return [
                        'title' => $product->name,
                        'subtitle' => 'SKU: ' . $product->sku,
                        'url' => route('admin.products.edit', $product->id),
                        'type' => 'Product',
                        'icon' => 'bi-box-seam'
                    ];
                });
            foreach ($products as $item) $results[] = $item;

            $categories = Category::where('name', 'like', "%{$query}%")
                ->take(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'title' => $category->name,
                        'subtitle' => 'Category',
                        'url' => route('admin.categories.edit', $category->id),
                        'type' => 'Category',
                        'icon' => 'bi-tags'
                    ];
                });
            foreach ($categories as $item) $results[] = $item;

            $attributes = ProductAttribute::where('name', 'like', "%{$query}%")
                ->take(3)
                ->get()
                ->map(function ($attr) {
                    return [
                        'title' => $attr->name,
                        'subtitle' => 'Attribute',
                        'url' => route('admin.attributes.edit', $attr->id),
                        'type' => 'Attribute',
                        'icon' => 'bi-sliders'
                    ];
                });
            foreach ($attributes as $item) $results[] = $item;
        }

        return response()->json(['results' => $results]);
    }
}
