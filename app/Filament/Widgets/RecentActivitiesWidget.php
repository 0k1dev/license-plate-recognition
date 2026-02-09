<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentActivitiesWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Hoạt động gần đây';

    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => $this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('Hành động')
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_contains($state, 'approve') => 'success',
                        str_contains($state, 'reject') => 'danger',
                        str_contains($state, 'lock') => 'danger',
                        str_contains($state, 'unlock') => 'success',
                        str_contains($state, 'create') => 'info',
                        str_contains($state, 'update') => 'warning',
                        str_contains($state, 'delete') => 'danger',
                        str_contains($state, 'hide') => 'gray',
                        str_contains($state, 'renew') => 'success',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match (true) {
                        str_contains($state, 'approve') => 'heroicon-m-check-circle',
                        str_contains($state, 'reject') => 'heroicon-m-x-circle',
                        str_contains($state, 'lock') => 'heroicon-m-lock-closed',
                        str_contains($state, 'unlock') => 'heroicon-m-lock-open',
                        str_contains($state, 'create') => 'heroicon-m-plus-circle',
                        str_contains($state, 'update') => 'heroicon-m-pencil-square',
                        str_contains($state, 'delete') => 'heroicon-m-trash',
                        str_contains($state, 'hide') => 'heroicon-m-eye-slash',
                        str_contains($state, 'renew') => 'heroicon-m-arrow-path',
                        default => 'heroicon-m-bolt',
                    })
                    ->formatStateUsing(fn(string $state): string => $this->formatAction($state)),

                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Người thực hiện')
                    ->icon('heroicon-m-user')
                    ->placeholder('Hệ thống')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : '-')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Chi tiết')
                    ->limit(60)
                    ->tooltip(fn(AuditLog $record): ?string => $record->description)
                    ->wrap()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->since()
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-m-clock'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'approve_property' => 'Duyệt BĐS',
                        'reject_property' => 'Từ chối BĐS',
                        'approve_phone_request' => 'Duyệt SĐT',
                        'reject_phone_request' => 'Từ chối SĐT',
                        'resolve_report' => 'Xử lý báo cáo',
                        'lock_user' => 'Khóa user',
                        'unlock_user' => 'Mở khóa user',
                    ])
                    ->label('Lọc hành động'),
            ])
            ->emptyStateHeading('Chưa có hoạt động nào')
            ->emptyStateDescription('Các hoạt động sẽ được ghi nhận tại đây')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function getTableQuery(): Builder
    {
        $query = AuditLog::query()->with('actor:id,name');

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Field staff chỉ thấy hoạt động của mình
        if ($user?->hasRole('FIELD_STAFF')) {
            $query->where('actor_id', $user->id);
        }

        return $query;
    }

    private function formatAction(string $action): string
    {
        return match ($action) {
            'approve_property' => 'Duyệt BĐS',
            'reject_property' => 'Từ chối BĐS',
            'create_property' => 'Tạo BĐS',
            'update_property' => 'Cập nhật BĐS',
            'delete_property' => 'Xóa BĐS',
            'create_post' => 'Tạo tin đăng',
            'renew_post' => 'Gia hạn tin',
            'hide_post' => 'Ẩn tin',
            'delete_post' => 'Xóa tin',
            'create_phone_request' => 'Yêu cầu SĐT',
            'approve_phone_request' => 'Duyệt SĐT',
            'reject_phone_request' => 'Từ chối SĐT',
            'create_report' => 'Tạo báo cáo',
            'resolve_report' => 'Xử lý báo cáo',
            'lock_user' => 'Khóa tài khoản',
            'unlock_user' => 'Mở khóa TK',
            default => $action,
        };
    }
}
