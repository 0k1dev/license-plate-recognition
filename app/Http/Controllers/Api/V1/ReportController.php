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
            $this->service->create(
                $request->user(),
                $request->validated(),
                $request->file('files', [])
            );

            return response()->json([
                'message' => 'Gửi báo cáo thành công.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gửi báo cáo thất bại. Vui lòng thử lại sau.',
            ], 400);
        }
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Report::class);

        $query = \App\Models\Report::query()
            ->with(['reporter', 'resolver', 'files', 'post.property', 'post.creator']);

        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('post_id'), fn($q) => $q->where('post_id', $request->post_id))
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

        return new \App\Http\Resources\ReportResource($report->fresh([
            'reporter',
            'resolver',
            'files',
            'post.property',
            'post.creator',
        ]));
    }
}
