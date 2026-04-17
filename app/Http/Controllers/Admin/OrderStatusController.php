<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function index(): View
    {
        $statuses = OrderStatus::query()
            ->withCount('orders')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.settings.order-statuses', compact('statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:order_statuses,key'],
            'label' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxSort = (int) OrderStatus::query()->max('sort_order');

        OrderStatus::create([
            'key' => strtolower(trim($validated['key'])),
            'label' => trim($validated['label']),
            'color' => $this->normalizeColor($validated['color'] ?? null),
            'sort_order' => $maxSort + 1,
            'is_active' => $request->boolean('is_active', true),
            'is_system' => false,
        ]);

        return back()->with('success', 'Order status added successfully.');
    }

    public function update(Request $request, OrderStatus $orderStatus): RedirectResponse
    {
        $rules = [
            'label' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];

        if ($orderStatus->is_system) {
            $rules['key'] = ['nullable', 'string'];
        } else {
            $rules['key'] = [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('order_statuses', 'key')->ignore($orderStatus->id),
            ];
        }

        $validated = $request->validate($rules);

        $isActive = $request->boolean('is_active', $orderStatus->is_active);

        if (!$isActive) {
            $otherActiveCount = OrderStatus::query()
                ->where('is_active', true)
                ->where('id', '!=', $orderStatus->id)
                ->count();

            if ($otherActiveCount === 0) {
                return back()->with('error', 'At least one active order status is required.');
            }
        }

        $orderStatus->update([
            'key' => $orderStatus->is_system
                ? $orderStatus->key
                : strtolower(trim((string) ($validated['key'] ?? $orderStatus->key))),
            'label' => trim($validated['label']),
            'color' => $this->normalizeColor($validated['color'] ?? null),
            'is_active' => $isActive,
            'sort_order' => isset($validated['sort_order'])
                ? (int) $validated['sort_order']
                : $orderStatus->sort_order,
        ]);

        return back()->with('success', 'Order status updated successfully.');
    }

    public function destroy(OrderStatus $orderStatus): RedirectResponse
    {
        if ($orderStatus->is_system) {
            return back()->with('error', 'System order statuses cannot be deleted.');
        }

        if (OrderStatus::query()->count() <= 1) {
            return back()->with('error', 'At least one order status must remain.');
        }

        if ($orderStatus->orders()->exists()) {
            return back()->with('error', 'This status is used by existing orders and cannot be deleted.');
        }

        $orderStatus->delete();

        return back()->with('success', 'Order status deleted successfully.');
    }

    private function normalizeColor(?string $color): string
    {
        $value = strtoupper(trim((string) $color));

        if (preg_match('/^#[0-9A-F]{6}$/', $value)) {
            return $value;
        }

        return '#6C757D';
    }
}
