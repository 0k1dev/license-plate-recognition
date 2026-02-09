<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\PendingActionsWidget;
use App\Filament\Widgets\RecentActivitiesWidget;
use App\Filament\Widgets\StaffStatsOverview;
use App\Filament\Widgets\StatsSummary;
use App\Filament\Widgets\WelcomeHeaderWidget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int | string | array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('FIELD_STAFF')) {
            return 2; // Simpler layout for staff
        }

        return 3; // Dense layout for admins
    }

    public function getWidgets(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('FIELD_STAFF')) {
            return [
                WelcomeHeaderWidget::class,
                StaffStatsOverview::class,
                // Add LatestPropertiesTable here later if needed
            ];
        }

        // Default Admin Dashboard
        return [
            StatsSummary::class,
            PendingActionsWidget::class,
            RecentActivitiesWidget::class,
        ];
    }
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Làm mới')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Invalidate all dashboard stats caches by incrementing version
                    Cache::increment('dashboard_stats_version');

                    Notification::make()
                        ->title('Đã làm mới dữ liệu')
                        ->success()
                        ->send();

                    return redirect(request()->header('Referer'));
                })
        ];
    }
}
