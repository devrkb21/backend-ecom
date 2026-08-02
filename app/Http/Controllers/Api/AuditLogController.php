<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && !$request->user()->hasAdminPermission('audit.view')) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $query = AuditLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($request->has('action')) {
            $query->byAction($request->action);
        }

        if ($request->has('user_id')) {
            $query->byUser((int) $request->user_id);
        }

        if ($request->has('model_type')) {
            $query->byModel($request->model_type, $request->model_id ? (int) $request->model_id : null);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $logs = $query->paginate($this->perPage());

        return $this->successResponse($logs);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin() && !$request->user()->hasAdminPermission('audit.view')) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $log = AuditLog::with('user:id,name,email')->findOrFail($id);

        return $this->successResponse($log);
    }
}
