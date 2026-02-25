<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportResolveRequest;
use App\Http\Requests\StoreReportRequest;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $service
    ) {}

    public function store(StoreReportRequest $request)
    {
        $this->authorize('create', \App\Models\Report::class);
        try {
            $report = $this->service->create($request->user(), $request->validated());
            return (new \App\Http\Resources\ReportResource($report->load(['reporter'])))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Report::class);

        $query = \App\Models\Report::query()
            ->with(['reporter', 'reportable', 'resolver']);

        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('reportable_type'), fn($q) => $q->where('reportable_type', $request->reportable_type))
            ->when($request->filled('reporter_id'), fn($q) => $q->where('reporter_id', $request->reporter_id))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('until'), fn($q) => $q->whereDate('created_at', '<=', $request->until));

        $reports = $query->latest()->paginate($request->input('limit', 10));

        return \App\Http\Resources\ReportResource::collection($reports);
    }

    public function resolve(ReportResolveRequest $request, \App\Models\Report $report)
    {
        $this->authorize('resolve', $report);

        DB::transaction(function () use ($request, $report): void {
            $this->service->resolve(
                $report,
                $request->user(),
                $request->validated()['action'],
                $request->validated()['note'] ?? null
            );
        });

        return new \App\Http\Resources\ReportResource($report->fresh(['reporter', 'resolver']));
    }
}
