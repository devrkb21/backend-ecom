<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\BdDistrict;
use App\Models\BdDivision;
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
        $query = Address::with(['division', 'district', 'upazila', 'union'])
            ->where('user_id', $request->user()->id);

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
            'division_id' => ['required', 'integer', Rule::exists('bd_divisions', 'id')],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('bd_districts', 'id')->where(function ($query) use ($request) {
                    $query->where('division_id', (int) $request->input('division_id'));
                }),
            ],
            'upazila_id' => [
                'required',
                'integer',
                Rule::exists('bd_upazilas', 'id')->where(function ($query) use ($request) {
                    $query->where('district_id', (int) $request->input('district_id'));
                }),
            ],
            'union_id' => [
                'nullable',
                'integer',
                Rule::exists('bd_unions', 'id')->where(function ($query) use ($request) {
                    $query->where('upazila_id', (int) $request->input('upazila_id'));
                }),
            ],
            'area' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => ['sometimes', 'string', 'max:100', Rule::in(['Bangladesh', 'BD'])],
            'instructions' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $validated = $this->applyBangladeshAddressMetadata($validated);

        $validated['user_id'] = $request->user()->id;
        $validated['type'] = $validated['type'] ?? 'both';

        // Check if this is the first address (make it default)
        $hasAddresses = Address::where('user_id', $request->user()->id)->exists();
        if (!$hasAddresses) {
            $validated['is_default'] = true;
        }

        $address = Address::create($validated);
        $address->load(['division', 'district', 'upazila', 'union']);

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

        $address->loadMissing(['division', 'district', 'upazila', 'union']);

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

        $divisionId = (int) $request->input('division_id', $address->division_id);
        $districtId = (int) $request->input('district_id', $address->district_id);
        $upazilaId = (int) $request->input('upazila_id', $address->upazila_id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'type' => ['sometimes', Rule::in(['shipping', 'billing', 'both'])],
            'is_default' => 'boolean',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'address_line_1' => 'sometimes|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'division_id' => ['sometimes', 'integer', Rule::exists('bd_divisions', 'id')],
            'district_id' => [
                'nullable',
                'required_with:division_id',
                'integer',
                Rule::exists('bd_districts', 'id')->where(function ($query) use ($divisionId) {
                    $query->where('division_id', $divisionId);
                }),
            ],
            'upazila_id' => [
                'nullable',
                'required_with:district_id',
                'integer',
                Rule::exists('bd_upazilas', 'id')->where(function ($query) use ($districtId) {
                    $query->where('district_id', $districtId);
                }),
            ],
            'union_id' => [
                'nullable',
                'integer',
                Rule::exists('bd_unions', 'id')->where(function ($query) use ($upazilaId) {
                    $query->where('upazila_id', $upazilaId);
                }),
            ],
            'area' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => ['sometimes', 'string', 'max:100', Rule::in(['Bangladesh', 'BD'])],
            'instructions' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $normalized = $this->applyBangladeshAddressMetadata([
            'division_id' => $validated['division_id'] ?? $address->division_id,
            'district_id' => $validated['district_id'] ?? $address->district_id,
            'city' => $validated['city'] ?? $address->city,
            'state' => $validated['state'] ?? $address->state,
        ]);

        $validated['city'] = $normalized['city'] ?? $validated['city'] ?? $address->city;
        $validated['state'] = $normalized['state'] ?? $validated['state'] ?? $address->state;
        $validated['country'] = 'Bangladesh';

        $address->update($validated);

        // If marked as default, update other addresses
        if ($address->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => new AddressResource($address->fresh()->load(['division', 'district', 'upazila', 'union'])),
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
            'data' => new AddressResource($address->fresh()->load(['division', 'district', 'upazila', 'union'])),
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
            'data' => new AddressResource($address->loadMissing(['division', 'district', 'upazila', 'union'])),
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
            'data' => new AddressResource($address->loadMissing(['division', 'district', 'upazila', 'union'])),
        ]);
    }

    protected function applyBangladeshAddressMetadata(array $data): array
    {
        $division = !empty($data['division_id']) ? BdDivision::find((int) $data['division_id']) : null;
        $district = !empty($data['district_id']) ? BdDistrict::find((int) $data['district_id']) : null;

        $data['city'] = $district?->name ?? ($data['city'] ?? null);
        $data['state'] = $division?->name ?? ($data['state'] ?? null);
        $data['country'] = 'Bangladesh';

        return $data;
    }
}
