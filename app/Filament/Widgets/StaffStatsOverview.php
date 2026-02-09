<?php

declare(strict_types=1);
namespace App\Filament\Widgets;

use App\Enums\ApprovalStatus;
use App\Enums\PostStatus;
use App\Enums\RequestStatus;
use App\Models\OwnerPhoneRequest;
use App\Models\Post;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StaffStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2; // Below Welcome Header

    protected int | string | array $columnSpan = 'half';

    protected function getStats(): array
    {
        $user = Auth::user();

        // 1. My Properties
        $myProps = Property::where('created_by', $user->id)->count();
        $approvedProps = Property::where('created_by', $user->id)
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->count();
        $pendingProps = $myProps - $approvedProps;

        // 2. My Posts
        $visiblePosts = Post::where('created_by', $user->id)
            ->where('status', PostStatus::VISIBLE->value)
            ->count();

        $expiringSoon = Post::where('created_by', $user->id)
            ->where('status', PostStatus::VISIBLE->value)
            ->whereBetween('visible_until', [now(), now()->addDays(7)])
            ->count();

        // 3. Phone Requests
        $approvedReqs = OwnerPhoneRequest::where('requester_id', $user->id)
            ->where('status', RequestStatus::APPROVED->value)
            ->count();
        $pendingReqs = OwnerPhoneRequest::where('requester_id', $user->id)
            ->where('status', RequestStatus::PENDING->value)
            ->count();

        return [
            Stat::make('BĐS Của Tôi', number_format($myProps))
                ->description($pendingProps > 0 ? "{$pendingProps} đang chờ duyệt" : 'Tất cả đã được duyệt')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color($pendingProps > 0 ? 'warning' : 'success')
                ->chart([$myProps, $approvedProps]),

            Stat::make('Tin Đang Hiển Thị', number_format($visiblePosts))
                ->description($expiringSoon > 0 ? "{$expiringSoon} tin sắp hết hạn" : 'Hoạt động ổn định')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color($expiringSoon > 0 ? 'danger' : 'primary'),

            Stat::make('Yêu Cầu SĐT', number_format($approvedReqs))
                ->description($pendingReqs > 0 ? "{$pendingReqs} yêu cầu đang chờ" : 'Đã duyệt xong')
                ->descriptionIcon('heroicon-m-phone')
                ->color('info'),
        ];
    }
}
