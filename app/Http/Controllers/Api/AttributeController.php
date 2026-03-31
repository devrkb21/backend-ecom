<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductAttributeResource;
use App\Models\ProductAttribute;
use Illuminate\Http\JsonResponse;

class AttributeController extends Controller
{
    /**
     * Get all product attributes with their values
     */
    public function index(): JsonResponse
    {
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();
        
        return $this->successResponse(ProductAttributeResource::collection($attributes));
    }

    /**
     * Get a single attribute with its values
     */
    public function show(int $id): JsonResponse
    {
        $attribute = ProductAttribute::with('values')->findOrFail($id);
        
        return $this->successResponse(new ProductAttributeResource($attribute));
    }
}
