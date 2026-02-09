<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OwnerPhoneRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class OwnerPhoneRequestObserver
{
    /**
     * Handle the OwnerPhoneRequest "created" event.
     */
    public function created(OwnerPhoneRequest $request): void
    {
        // Gửi notification cho tất cả Admin
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['SUPER_ADMIN', 'OFFICE_ADMIN']);
        })->get();

        foreach ($admins as $admin) {
            Notification::make()
                ->title('Yêu cầu xem SĐT mới')
                ->icon('heroicon-o-phone')
                ->iconColor('info')
                ->body("Nhân viên {$request->requester->name} yêu cầu xem SĐT BĐS: {$request->property->title}")
                ->actions([
                    Action::make('view')
                        ->label('Xem chi tiết')
                        ->url(route('filament.admin.resources.owner-phone-requests.index'))
                        ->markAsRead(),
                    Action::make('approve')
                        ->label('Duyệt')
                        ->color('success')
                        ->dispatch('approveRequest', ['id' => $request->id])
                        ->close(),
                ])
                ->sendToDatabase($admin);
        }
    }

    /**
     * Handle the OwnerPhoneRequest "updated" event.
     */
    public function updated(OwnerPhoneRequest $request): void
    {
        // Nếu trạng thái thay đổi, thông báo cho người yêu cầu
        if ($request->wasChanged('status')) {
            $status = $request->status;
            $title = match ($status) {
                'APPROVED' => '✅ Yêu cầu SĐT được duyệt',
                'REJECTED' => '❌ Yêu cầu SĐT bị từ chối',
                default => 'Cập nhật yêu cầu SĐT',
            };

            $body = match ($status) {
                'APPROVED' => "Bạn đã được phép xem SĐT chủ nhà BĐS: {$request->property->title}",
                'REJECTED' => "Yêu cầu xem SĐT BĐS: {$request->property->title} đã bị từ chối. Lý do: {$request->admin_note}",
                default => "Trạng thái yêu cầu đã được cập nhật",
            };

            Notification::make()
                ->title($title)
                ->icon($status === 'APPROVED' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->iconColor($status === 'APPROVED' ? 'success' : 'danger')
                ->body($body)
                ->actions([
                    Action::make('view')
                        ->label('Xem BĐS')
                        ->url(route('filament.admin.resources.properties.view', $request->property_id))
                        ->markAsRead(),
                ])
                ->sendToDatabase($request->requester);
        }
    }
}
