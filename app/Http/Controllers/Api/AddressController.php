<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    /**
     * Get user's addresses
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Address::where('user_id', $request->user()->id);

        // Filter by type
        if ($type = $request->input('type')) {
            if ($type === 'shipping') {
                $query->shipping();
            } elseif ($type === 'billing') {
                $query->billing();
            }
        }

        $addresses = $query->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return AddressResource::collection($addresses);
    }

    /**
     * Store a new address
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'type' => ['sometimes', Rule::in(['shipping', 'billing', 'both'])],
            'is_default' => 'boolean',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'sometimes|string|max:100',
            'instructions' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['type'] = $validated['type'] ?? 'both';
        $validated['country'] = $validated['country'] ?? 'Bangladesh';

        // Check if this is the first address (make it default)
        $hasAddresses = Address::where('user_id', $request->user()->id)->exists();
        if (!$hasAddresses) {
            $validated['is_default'] = true;
        }

        $address = Address::create($validated);

        // If marked as default, update other addresses
        if ($address->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully',
            'data' => new AddressResource($address),
        ], 201);
    }

    /**
     * Get a specific address
     */
    public function show(Request $request, Address $address): JsonResponse
    {
        // Ensure user owns this address
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Update an address
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        // Ensure user owns this address
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'type' => ['sometimes', Rule::in(['shipping', 'billing', 'both'])],
            'is_default' => 'boolean',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'address_line_1' => 'sometimes|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'sometimes|string|max:100',
            'instructions' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $address->update($validated);

        // If marked as default, update other addresses
        if ($address->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => new AddressResource($address->fresh()),
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        // Ensure user owns this address
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, set another one as default
        if ($wasDefault) {
            $newDefault = Address::where('user_id', $request->user()->id)
                ->orderByDesc('updated_at')
                ->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Set address as default
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        // Ensure user owns this address
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $address->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Default address updated',
            'data' => new AddressResource($address->fresh()),
        ]);
    }

    /**
     * Get default shipping address
     */
    public function defaultShipping(Request $request): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)
            ->shipping()
            ->default()
            ->first();

        if (!$address) {
            // Fallback to any default address
            $address = Address::where('user_id', $request->user()->id)
                ->default()
                ->first();
        }

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'No default shipping address found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Get default billing address
     */
    public function defaultBilling(Request $request): JsonResponse
    {
        $address = Address::where('user_id', $request->user()->id)
            ->billing()
            ->default()
            ->first();

        if (!$address) {
            // Fallback to any default address
            $address = Address::where('user_id', $request->user()->id)
                ->default()
                ->first();
        }

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'No default billing address found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AddressResource($address),
        ]);
    }
}
