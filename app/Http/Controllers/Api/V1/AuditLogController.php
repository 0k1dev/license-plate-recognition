<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /admin/audit-logs – Tra cứu audit logs với filter.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('actor');

        $query->when($request->filled('action'), fn($q) => $q->where('action', $request->action))
            ->when($request->filled('actor_id'), fn($q) => $q->where('actor_id', $request->actor_id))
            ->when($request->filled('target_type'), fn($q) => $q->where('target_type', $request->target_type))
            ->when($request->filled('target_id'), fn($q) => $q->where('target_id', $request->target_id))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('until'), fn($q) => $q->whereDate('created_at', '<=', $request->until));

        $logs = $query->latest()->paginate($request->input('limit', 20));

        return \App\Http\Resources\AuditLogApiResource::collection($logs);
    }
}
