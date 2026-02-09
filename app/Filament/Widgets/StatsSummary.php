<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ApprovalStatus;
use App\Enums\PostStatus;
use App\Enums\ReportStatus;
use App\Enums\RequestStatus;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use App\Models\OwnerPhoneRequest;
use App\Models\Report;
use App\Traits\HasAreaScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsSummary extends BaseWidget
{
    use HasAreaScope;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null; // Tắt auto poll để dùng cache hoàn toàn

    /**
     * Cache time (24h)
     */
    private const CACHE_TTL = 86400;

    protected $listeners = ['refreshStats' => '$refresh'];

    protected function getStats(): array
    {
        $user = Auth::user();
        $version = Cache::get('dashboard_stats_version', 1);
        $cacheKey = 'dashboard_stats_' . ($user ? $user->id : 'guest') . '_v' . $version;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $stats = [];

            // 1. Tổng BĐS
            $stats[] = $this->buildPropertyStat();

            // 2. Bài đăng hoạt động
            $stats[] = $this->buildPostStat();

            // 3. Admin view
            $stats[] = $this->buildAdminUserStat();
            $stats[] = $this->buildPendingWorkStat();

            return $stats;
        });
    }

    private function buildPropertyStat(): Stat
    {
        $query = Property::query();
        $totalProps = $query->count();
        $pendingProps = (clone $query)->where('approval_status', ApprovalStatus::PENDING->value)->count();
        $trend = $this->getPropertyTrend();

        return Stat::make('Tổng Bất Động Sản', number_format($totalProps))
            ->description($pendingProps > 0 ? "{$pendingProps} chờ duyệt" : 'Đã duyệt hết')
            ->descriptionIcon($pendingProps > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
            ->chart($trend)
            ->color($pendingProps > 0 ? 'warning' : 'success')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:scale-[1.02] transition-transform',
            ]);
    }

    private function buildPostStat(): Stat
    {
        $query = Post::query();
        $visiblePosts = (clone $query)->where('status', PostStatus::VISIBLE->value)->count();
        $expiringPosts = (clone $query)
            ->where('status', PostStatus::VISIBLE->value)
            ->whereBetween('visible_until', [now(), now()->addDays(7)])
            ->count();
        $trend = $this->getPostTrend();

        $description = $expiringPosts > 0
            ? "{$expiringPosts} tin sắp hết hạn"
            : 'Đang hoạt động tốt';

        return Stat::make('Tin Đang Hiển Thị', number_format($visiblePosts))
            ->description($description)
            ->descriptionIcon($expiringPosts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-arrow-trending-up')
            ->chart($trend)
            ->color($expiringPosts > 0 ? 'warning' : 'primary');
    }

    private function buildAdminUserStat(): Stat
    {
        $activeUsers = User::where('is_locked', false)->count();
        $lockedUsers = User::where('is_locked', true)->count();

        // Simple 30-day user count trend
        $trend = $this->getUserTrend();

        return Stat::make('Người Dùng', number_format($activeUsers))
            ->description($lockedUsers > 0 ? "{$lockedUsers} đã khóa" : 'Tất cả hoạt động')
            ->descriptionIcon('heroicon-m-users')
            ->chart($trend)
            ->color('info');
    }

    private function buildPendingWorkStat(): Stat
    {
        $pendingRequests = OwnerPhoneRequest::where('status', RequestStatus::PENDING->value)->count();
        $pendingReports = Report::where('status', ReportStatus::NEW->value)->count();
        $pendingProperties = Property::where('approval_status', ApprovalStatus::PENDING->value)->count();

        $total = $pendingRequests + $pendingReports + $pendingProperties;

        $details = [];
        if ($pendingProperties > 0) $details[] = "{$pendingProperties} BĐS";
        if ($pendingRequests > 0) $details[] = "{$pendingRequests} SĐT";
        if ($pendingReports > 0) $details[] = "{$pendingReports} báo cáo";

        // Simple 30-day pending work trend
        $trend = $this->getPendingWorkTrend();

        return Stat::make('Công Việc Chờ', number_format($total))
            ->description($total > 0 ? implode(' • ', $details) : '🎉 Không có tồn đọng')
            ->descriptionIcon($total > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-badge')
            ->chart($trend)
            ->color($total > 0 ? 'danger' : 'success');
    }

    private function buildMyPropertyStat(?User $user): Stat
    {
        $myProps = Property::where('created_by', $user?->id)->count();
        $approvedProps = Property::where('created_by', $user?->id)
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->count();

        return Stat::make('BĐS Của Tôi', number_format($myProps))
            ->description("{$approvedProps} đã duyệt")
            ->descriptionIcon('heroicon-m-home-modern')
            ->color('info');
    }

    private function buildMyRequestsStat(?User $user): Stat
    {
        $approvedReqs = OwnerPhoneRequest::where('requester_id', $user?->id)
            ->where('status', RequestStatus::APPROVED->value)
            ->count();

        $pendingReqs = OwnerPhoneRequest::where('requester_id', $user?->id)
            ->where('status', RequestStatus::PENDING->value)
            ->count();

        return Stat::make('Yêu Cầu SĐT', number_format($approvedReqs))
            ->description($pendingReqs > 0 ? "{$pendingReqs} chờ duyệt" : 'Đã được duyệt')
            ->descriptionIcon('heroicon-m-phone')
            ->color('success');
    }

    private function getPropertyTrend(?array $areaIds = null): array
    {
        $query = Property::query();
        if ($areaIds) {
            $query->whereIn('area_id', $areaIds);
        }

        return Trend::query($query)
            ->between(start: now()->subDays(29), end: now())
            ->perDay()
            ->count()
            ->map(fn(TrendValue $value) => $value->aggregate)
            ->toArray();
    }

    private function getPostTrend(?array $areaIds = null): array
    {
        $query = Post::query()->where('status', PostStatus::VISIBLE->value);
        if ($areaIds) {
            $query->whereHas('property', fn($q) => $q->whereIn('area_id', $areaIds));
        }

        return Trend::query($query)
            ->between(start: now()->subDays(29), end: now())
            ->perDay()
            ->count()
            ->map(fn(TrendValue $value) => $value->aggregate)
            ->toArray();
    }

    private function getUserTrend(): array
    {
        return Trend::query(User::query())
            ->between(start: now()->subDays(29), end: now())
            ->perDay()
            ->count()
            ->map(fn(TrendValue $value) => $value->aggregate)
            ->toArray();
    }

    private function getPendingWorkTrend(): array
    {
        // Combine pending items from all sources
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $properties = Property::where('approval_status', ApprovalStatus::PENDING->value)
                ->whereDate('created_at', '<=', $date)
                ->count();

            $requests = OwnerPhoneRequest::where('status', RequestStatus::PENDING->value)
                ->whereDate('created_at', '<=', $date)
                ->count();

            $reports = Report::where('status', ReportStatus::NEW->value)
                ->whereDate('created_at', '<=', $date)
                ->count();

            $data[] = $properties + $requests + $reports;
        }

        return $data;
    }
}
