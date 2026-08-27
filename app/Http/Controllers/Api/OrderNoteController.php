<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderNote;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderNoteController extends Controller
{
    public function __construct(protected LicenseService $licenseService) {}

    public function index(Request $request, int $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);

        // Non-admin users can only see customer-visible notes for their own orders
        if (! $request->user()->isAdmin()) {
            if ($order->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $notes = $order->notes()
                ->customerVisible()
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $notes = $order->notes()
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $this->successResponse($notes);
    }

    public function store(Request $request, int $orderId): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'string', 'in:internal,customer,system'],
            'is_customer_visible' => ['nullable', 'boolean'],
        ]);

        $order = Order::findOrFail($orderId);

        if ($this->licenseService->isOrderLocked($order)) {
            return $this->errorResponse(
                'This order was placed after your license expired. Renew your license to manage new orders.',
                403,
            );
        }

        $note = $order->notes()->create([
            'user_id' => $request->user()->id,
            'note' => $request->note,
            'type' => $request->type ?? 'internal',
            'is_customer_visible' => $request->is_customer_visible ?? false,
        ]);

        $note->load('user:id,name');

        return $this->createdResponse($note, 'Note added successfully');
    }

    public function destroy(Request $request, int $orderId, int $noteId): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $note = OrderNote::where('order_id', $orderId)->findOrFail($noteId);

        if ($this->licenseService->isOrderLocked(Order::findOrFail($orderId))) {
            return $this->errorResponse(
                'This order was placed after your license expired. Renew your license to manage new orders.',
                403,
            );
        }

        $note->delete();

        return $this->successResponse(null, 'Note deleted successfully');
    }
}
