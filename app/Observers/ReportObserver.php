<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Report;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class ReportObserver
{
    /**
     * Handle the Report "created" event.
     */
    public function created(Report $report): void
    {
        // Gửi notification cho tất cả Admin
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['SUPER_ADMIN', 'OFFICE_ADMIN']);
        })->get();

        $typeLabel = match ($report->type) {
            'spam' => 'Spam',
            'fake_info' => 'Thông tin sai',
            'scam' => 'Lừa đảo',
            'duplicate' => 'Trùng lặp',
            'inappropriate' => 'Không phù hợp',
            'POST_CONTENT' => 'Bài đăng có vấn đề',
            'SELLER_BEHAVIOR' => 'Người bán / nhân viên',
            'PROPERTY_INFO' => 'Thông tin bất động sản sai lệch',
            'FRAUD_SCAM' => 'Gian lận / lừa đảo',
            default => $report->type,
        };

        $iconColor = match ($report->type) {
            'scam', 'inappropriate', 'FRAUD_SCAM' => 'danger',
            'spam', 'fake_info', 'PROPERTY_INFO', 'SELLER_BEHAVIOR' => 'warning',
            default => 'gray',
        };

        foreach ($admins as $admin) {
            Notification::make()
                ->title('🚨 Báo cáo vi phạm mới')
                ->icon('heroicon-o-flag')
                ->iconColor($iconColor)
                ->body("Loại: {$typeLabel}\nNội dung: " . \Illuminate\Support\Str::limit($report->content, 100))
                ->actions([
                    Action::make('view')
                        ->label('Xem & Xử lý')
                        ->url(route('filament.admin.resources.reports.index'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($admin);
        }
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        // Nếu báo cáo được xử lý, thông báo cho người báo cáo
        if ($report->wasChanged('status')) {
            if ($report->status === 'RESOLVED') {
                $actionLabel = match ($report->action) {
                    'HIDE_POST' => 'ẩn bài đăng',
                    'LOCK_USER' => 'khóa tài khoản người dùng',
                    'WARN' => 'cảnh cáo',
                    'NO_ACTION' => 'không thực hiện hành động nào',
                    default => 'xử lý',
                };

                Notification::make()
                    ->title('✅ Báo cáo của bạn đã được xử lý')
                    ->icon('heroicon-o-check-badge')
                    ->iconColor('success')
                    ->body("Chúng tôi đã {$actionLabel} dựa trên báo cáo của bạn. Cảm ơn bạn đã giúp cộng đồng!")
                    ->sendToDatabase($report->reporter);
            } elseif ($report->status === 'REJECTED') {
                Notification::make()
                    ->title('❌ Báo cáo bị từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->body("Báo cáo của bạn đã bị từ chối. Lý do: " . ($report->admin_note ?? 'Không có lý do cụ thể'))
                    ->sendToDatabase($report->reporter);
            }
        }
    }
}
