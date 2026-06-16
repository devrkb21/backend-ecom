<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbandonedCartController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display listing of abandoned carts
     */
    public function index(Request $request): View
    {
        $query = AbandonedCart::with(['user', 'recoveredOrder', 'followedUpBy'])
            ->latest('created_at');

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

        // Filter by landing page slug
        if ($request->filled('landing_page_slug')) {
            $query->where('landing_page_slug', $request->landing_page_slug);
        }

        // Filter by contact info presence
        if ($request->filled('has_contact')) {
            if ($request->has_contact === 'yes') {
                $query->withContactInfo();
            } else {
                $query->whereNull('email')->whereNull('phone');
            }
        }

        // Search by email, phone, name, or landing page slug
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('landing_page_slug', 'like', "%{$search}%");
            });
        }

        // Minimum value filter
        if ($request->filled('min_value')) {
            $query->where('total', '>=', $request->min_value);
        }

        // Priority filters for quick triage
        if ($request->filled('priority')) {
            $priority = (string) $request->priority;

            if ($priority === 'high_value') {
                $query->open()->highValue(5000);
            } elseif ($priority === 'overdue_follow_up') {
                $query->open()->overdueFollowUp(24);
            } elseif ($priority === 'reminder_due') {
                $query->reminderDue(24, 3);
            } elseif ($priority === 'actionable') {
                $query->open()->withContactInfo();
            }
        }

        // Sorting options
        if ($request->filled('sort_by')) {
            $sortBy = (string) $request->sort_by;

            if ($sortBy === 'oldest') {
                $query->reorder()->orderBy('created_at');
            } elseif ($sortBy === 'highest_value') {
                $query->reorder()->orderByDesc('total');
            } elseif ($sortBy === 'oldest_activity') {
                $query->reorder()->orderBy('last_activity_at');
            } elseif ($sortBy === 'latest_activity') {
                $query->reorder()->orderByDesc('last_activity_at');
            }
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $abandonedCarts = $query->paginate($perPage)->withQueryString();

        $stats = AbandonedCart::getSummary();
        $landingPages = AbandonedCart::select('landing_page_slug')
            ->distinct()
            ->whereNotNull('landing_page_slug')
            ->pluck('landing_page_slug');

        return view('admin.abandoned-carts.index', compact('abandonedCarts', 'stats', 'landingPages'));
    }

    /**
     * Show abandoned cart details
     */
    public function show(AbandonedCart $abandonedCart): View
    {
        $abandonedCart->load(['user', 'recoveredOrder', 'followedUpBy', 'cart.items.product']);

        return view('admin.abandoned-carts.show', compact('abandonedCart'));
    }

    public function markFollowUp(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
        ]);

        $abandonedCart->markAsFollowUp(
            auth()->id(),
            $validated['admin_notes'] ?? null,
            $validated['follow_up_date'] ?? null
        );

        return redirect()
            ->back()
            ->with('success', 'Marked as follow up successfully.');
    }

    /**
     * Mark as pending/incomplete
     */
    public function markPending(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $abandonedCart->markAsPending($validated['admin_notes'] ?? null);

        return redirect()
            ->back()
            ->with('success', 'Marked as incomplete (pending).');
    }

    /**
     * Mark as recovered
     */
    public function markRecovered(Request $request, AbandonedCart $abandonedCart): RedirectResponse
    {
        if (!in_array($abandonedCart->status, ['pending', 'follow_up'], true)) {
            return redirect()
                ->back()
                ->with('error', 'Only incomplete checkouts can be marked as recovered.');
        }

        try {
            $order = $this->orderService->createRecoveredOrderFromAbandonedCart($abandonedCart);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('error', 'Recovery failed: ' . $exception->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', "Recovered successfully. New order created: {$order->order_number}");
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
            'action' => 'required|in:pending,follow_up,recovered,cancelled,delete',
            'ids' => 'required|array',
            'ids.*' => 'exists:abandoned_carts,id',
        ]);

        $carts = AbandonedCart::query()->whereIn('id', $validated['ids'])->get();
        $processedCount = 0;
        $failedCount = 0;

        foreach ($carts as $cart) {
            /** @var AbandonedCart $cart */
            try {
                switch ($validated['action']) {
                    case 'pending':
                        $cart->markAsPending();
                        break;
                    case 'follow_up':
                        $cart->markAsFollowUp(auth()->id());
                        break;
                    case 'recovered':
                        $this->orderService->createRecoveredOrderFromAbandonedCart($cart);
                        break;
                    case 'cancelled':
                        $cart->markAsCancelled();
                        break;
                    case 'delete':
                        $cart->delete();
                        break;
                }

                $processedCount++;
            } catch (\Throwable $exception) {
                $failedCount++;
            }
        }

        $actionLabel = match ($validated['action']) {
            'pending' => 'marked as incomplete',
            'follow_up' => 'marked as follow up',
            'recovered' => 'recovered with new orders',
            'cancelled' => 'cancelled',
            'delete' => 'deleted',
        };

        $response = redirect()
            ->back()
            ->with('success', "{$processedCount} abandoned carts {$actionLabel}.");

        if ($failedCount > 0) {
            $response = $response->with('error', "{$failedCount} abandoned carts failed to process.");
        }

        return $response;
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
                'Landing Page',
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
                    $cart->landing_page_slug,
                    $cart->last_activity_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
