<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Nhật ký hoạt động';
    protected static ?string $pluralModelLabel = 'Nhật ký hoạt động';
    protected static ?string $navigationLabel = 'Nhật ký hoạt động';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Chi tiết nhật ký')
                    ->schema([
                        Forms\Components\Select::make('actor_id')
                            ->relationship('actor', 'name')
                            ->label('Người thực hiện')
                            ->disabled(),
                        Forms\Components\TextInput::make('action')
                            ->label('Hành động')
                            ->disabled(),
                        Forms\Components\TextInput::make('target_type')
                            ->label('Loại đối tượng')
                            ->disabled(),
                        Forms\Components\TextInput::make('target_id')
                            ->label('ID đối tượng')
                            ->disabled(),
                        Forms\Components\Textarea::make('payload')
                            ->label('Dữ liệu (JSON)')
                            ->rows(5)
                            ->columnSpanFull()
                            ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Thông tin hệ thống')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Thời gian')
                            ->displayFormat('d/m/Y H:i')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Thời gian'),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Người thực hiện')
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->label('Hành động')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'approve_property', 'approve_properties' => 'Duyệt BĐS',
                        'reject_property', 'reject_properties' => 'Từ chối BĐS',
                        'create_post' => 'Đăng tin mới',
                        'renew_post' => 'Gia hạn tin',
                        'hide_post' => 'Ẩn tin',
                        'delete_post' => 'Xóa tin',
                        'update_post_status' => 'Cập nhật trạng thái tin',
                        'create_phone_request' => 'Yêu cầu xem SĐT',
                        'approve_phone_request' => 'Duyệt xem SĐT',
                        'reject_phone_request' => 'Từ chối xem SĐT',
                        'lock_user' => 'Khóa tài khoản',
                        'unlock_user' => 'Mở khóa tài khoản',
                        'create_report' => 'Gửi báo cáo',
                        'resolve_report' => 'Xử lý báo cáo',
                        default => $state,
                    })
                    ->colors([
                        'success' => ['approve_property', 'approve_properties', 'approve_phone_request', 'unlock_user', 'renew_post', 'create_post'],
                        'danger' => ['reject_property', 'reject_properties', 'reject_phone_request', 'lock_user', 'delete_post', 'hide_post'],
                        'warning' => ['create_phone_request', 'create_report'],
                        'info' => ['update_post_status', 'resolve_report'],
                    ]),
                Tables\Columns\TextColumn::make('target_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'App\Models\Property' => 'Bất động sản',
                        'App\Models\Post' => 'Tin đăng',
                        'App\Models\User' => 'Người dùng',
                        'App\Models\OwnerPhoneRequest' => 'Yêu cầu SĐT',
                        'App\Models\Report' => 'Báo cáo',
                        default => class_basename($state),
                    }),
                Tables\Columns\TextColumn::make('target_id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('actor_id')
                    ->relationship('actor', 'name')
                    ->label('Người thực hiện')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Hành động')
                    ->options([
                        'approve_property'       => 'Duyệt BĐS',
                        'reject_property'        => 'Từ chối BĐS',
                        'approve_properties'     => 'Duyệt nhiều BĐS',
                        'reject_properties'      => 'Từ chối nhiều BĐS',
                        'create_post'            => 'Đăng tin mới',
                        'approve_post'           => 'Duyệt bài đăng',
                        'renew_post'             => 'Gia hạn tin',
                        'hide_post'              => 'Ẩn tin',
                        'delete_post'            => 'Xóa tin',
                        'update_post_status'     => 'Cập nhật trạng thái tin',
                        'create_phone_request'   => 'Yêu cầu xem SĐT',
                        'approve_phone_request'  => 'Duyệt xem SĐT',
                        'reject_phone_request'   => 'Từ chối xem SĐT',
                        'lock_user'              => 'Khóa tài khoản',
                        'unlock_user'            => 'Mở khóa tài khoản',
                        'create_report'          => 'Gửi báo cáo',
                        'resolve_report'         => 'Xử lý báo cáo',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->label('Thời gian')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Từ ngày')
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Đến ngày')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn(Builder $q, string $d) => $q->whereDate('created_at', '<=', $d));
                    })
                    ->columns(2),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()->slideOver(),
            ])
            ->bulkActions([
                // Immutable logs
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'create' => Pages\CreateAuditLog::route('/create'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
            'edit' => Pages\EditAuditLog::route('/{record}/edit'),
        ];
    }
}
