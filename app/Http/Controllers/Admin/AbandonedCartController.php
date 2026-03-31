<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbandonedCartController extends Controller
{
    /**
     * Display listing of abandoned carts
     */
    public function index(Request $request): View
    {
        $query = AbandonedCart::with(['user', 'recoveredOrder', 'followedUpBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by checkout step
        if ($request->filled('checkout_step')) {
            $query->where('checkout_step', $request->checkout_step);
        }

        // Filter by contact info presence
        if ($request->filled('has_contact')) {
            if ($request->has_contact === 'yes') {
                $query->withContactInfo();
            } else {
                $query->whereNull('email')->whereNull('phone');
            }
        }

        // Search by email, phone, or name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Minimum value filter
        if ($request->filled('min_value')) {
            $query->where('total', '>=', $request->min_value);
        }

        $abandonedCarts = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => AbandonedCart::count(),
            'pending' => AbandonedCart::pending()->count(),
            'follow_up' => AbandonedCart::followUp()->count(),
            'recovered' => AbandonedCart::recovered()->count(),
            'cancelled' => AbandonedCart::cancelled()->count(),
            'potential_revenue' => AbandonedCart::getPotentialRevenue(),
            'recovery_rate' => AbandonedCart::getRecoveryRate(),
            'with_contact' => AbandonedCart::withContactInfo()->whereIn('status', ['pending', 'follow_up'])->count(),
        ];

        return view('admin.abandoned-carts.index', compact('abandonedCarts', 'stats'));
    }

    /**
     * Show abandoned cart details
     */
    public function show(AbandonedCart $abandonedCart): View
    {
        $abandonedCart->load(['user', 'recoveredOrder', 'followedUpBy', 'cart.items.product']);

        return view('admin.abandoned-carts.show', compact('abandonedCart'));
    }

    /**
     * Mark as follow up
     */
    public function markFollowUp(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $abandonedCart->markAsFollowUp(
            auth()->id(),
            $validated['admin_notes'] ?? null
        );

        return redirect()
            ->back()
            ->with('success', 'Marked as follow up successfully.');
    }

    /**
     * Mark as recovered
     */
    public function markRecovered(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $abandonedCart->markAsRecovered($validated['order_id'] ?? null);

        return redirect()
            ->back()
            ->with('success', 'Marked as recovered successfully.');
    }

    /**
     * Mark as cancelled
     */
    public function markCancelled(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $abandonedCart->markAsCancelled($validated['admin_notes'] ?? null);

        return redirect()
            ->back()
            ->with('success', 'Marked as cancelled.');
    }

    /**
     * Update admin notes
     */
    public function updateNotes(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $abandonedCart->update([
            'admin_notes' => $validated['admin_notes'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Notes updated successfully.');
    }

    /**
     * Bulk action
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:follow_up,recovered,cancelled,delete',
            'ids' => 'required|array',
            'ids.*' => 'exists:abandoned_carts,id',
        ]);

        $carts = AbandonedCart::whereIn('id', $validated['ids'])->get();

        foreach ($carts as $cart) {
            switch ($validated['action']) {
                case 'follow_up':
                    $cart->markAsFollowUp(auth()->id());
                    break;
                case 'recovered':
                    $cart->markAsRecovered();
                    break;
                case 'cancelled':
                    $cart->markAsCancelled();
                    break;
                case 'delete':
                    $cart->delete();
                    break;
            }
        }

        $actionLabel = match ($validated['action']) {
            'follow_up' => 'marked as follow up',
            'recovered' => 'marked as recovered',
            'cancelled' => 'cancelled',
            'delete' => 'deleted',
        };

        return redirect()
            ->back()
            ->with('success', count($validated['ids']) . " abandoned carts {$actionLabel}.");
    }

    /**
     * Delete abandoned cart
     */
    public function destroy(AbandonedCart $abandonedCart): RedirectResponse
    {
        $abandonedCart->delete();

        return redirect()
            ->route('admin.abandoned-carts.index')
            ->with('success', 'Abandoned cart deleted.');
    }

    /**
     * Export abandoned carts
     */
    public function export(Request $request)
    {
        $query = AbandonedCart::with(['user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $carts = $query->get();

        $filename = 'abandoned_carts_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($carts) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID',
                'Date',
                'Status',
                'Name',
                'Email',
                'Phone',
                'Items',
                'Total',
                'Checkout Step',
                'Last Activity',
            ]);

            foreach ($carts as $cart) {
                fputcsv($file, [
                    $cart->id,
                    $cart->created_at->format('Y-m-d H:i'),
                    $cart->status_label,
                    $cart->name,
                    $cart->email,
                    $cart->phone,
                    $cart->item_count,
                    $cart->total,
                    $cart->checkout_step_label,
                    $cart->last_activity_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
