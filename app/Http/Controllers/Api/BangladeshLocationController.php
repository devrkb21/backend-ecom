<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BangladeshLocationController extends Controller
{
    public function divisions(): JsonResponse
    {
        $data = BdDivision::query()
            ->orderBy('name')
            ->get(['id', 'name', 'bn_name']);

        return $this->successResponse($data);
    }

    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'division_id' => ['nullable', 'integer', 'exists:bd_divisions,id'],
        ]);

        $query = BdDistrict::query()->orderBy('name');

        if ($request->filled('division_id')) {
            $query->where('division_id', (int) $request->input('division_id'));
        }

        $data = $query->get(['id', 'division_id', 'name', 'bn_name']);

        return $this->successResponse($data);
    }

    public function upazilas(Request $request): JsonResponse
    {
        $request->validate([
            'district_id' => ['nullable', 'integer', 'exists:bd_districts,id'],
        ]);

        $query = BdUpazila::query()->orderBy('name');

        if ($request->filled('district_id')) {
            $query->where('district_id', (int) $request->input('district_id'));
        }

        $data = $query->get(['id', 'district_id', 'name', 'bn_name']);

        return $this->successResponse($data);
    }

    public function unions(Request $request): JsonResponse
    {
        $request->validate([
            'upazila_id' => ['nullable', 'integer', 'exists:bd_upazilas,id'],
        ]);

        $query = BdUnion::query()->orderBy('name');

        if ($request->filled('upazila_id')) {
            $query->where('upazila_id', (int) $request->input('upazila_id'));
        }

        $data = $query->get(['id', 'upazila_id', 'name', 'bn_name']);

        return $this->successResponse($data);
    }
}
