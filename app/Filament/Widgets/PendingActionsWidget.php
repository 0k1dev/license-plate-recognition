<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ApprovalStatus;
use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\Report;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PendingActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.pending-actions-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    private const CACHE_TTL = 86400; // 24h

    protected $listeners = ['refreshStats' => '$refresh'];

    public function getData(): array
    {
        $user = Auth::user();
        $isAdmin = $user?->hasRole('SUPER_ADMIN') || $user?->hasRole('OFFICE_ADMIN');

        if (!$isAdmin) {
            return [];
        }

        $version = Cache::get('dashboard_stats_version', 1);
        $cacheKey = "pending_actions_{$user?->id}_v{$version}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'pending_properties' => Property::query()
                    ->where('approval_status', ApprovalStatus::PENDING->value)
                    ->with('areaLocation:id,name')
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'title', 'area_id', 'created_at']),

                'property_count' => Property::where('approval_status', ApprovalStatus::PENDING->value)->count(),

                'pending_requests' => OwnerPhoneRequest::query()
                    ->where('status', RequestStatus::PENDING->value)
                    ->with(['requester:id,name', 'property:id,title'])
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'property_id', 'requester_id', 'reason', 'created_at']),

                'request_count' => OwnerPhoneRequest::where('status', RequestStatus::PENDING->value)->count(),

                'pending_reports' => Report::query()
                    ->where('status', ReportStatus::OPEN->value)
                    ->with('reporter:id,name')
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'type', 'content', 'reporter_id', 'created_at']),

                'report_count' => Report::where('status', ReportStatus::OPEN->value)->count(),
            ];
        });
    }
}
