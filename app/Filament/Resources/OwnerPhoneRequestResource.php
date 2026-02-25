<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RequestStatus;
use App\Filament\Resources\OwnerPhoneRequestResource\Pages;
use App\Models\OwnerPhoneRequest;
use App\Services\OwnerPhoneRequestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OwnerPhoneRequestResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = OwnerPhoneRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $activeNavigationIcon = 'heroicon-s-phone';

    protected static ?string $modelLabel = 'Yêu cầu SĐT';
    protected static ?string $pluralModelLabel = 'Danh sách Yêu cầu SĐT';
    protected static ?string $navigationLabel = 'Yêu cầu SĐT';
    protected static ?string $navigationGroup = 'Kiểm duyệt';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', RequestStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['property', 'requester', 'reviewer']);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // FIELD_STAFF chi thay yeu cau do chinh minh tao
        if ($user->isFieldStaff()) {
            return $query->where('requester_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin yêu cầu')
                    ->icon('heroicon-m-phone')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->relationship('property', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Bất động sản')
                            ->prefixIcon('heroicon-m-home-modern')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => auth()->id())
                            ->disabled(fn() => auth()->user()?->isFieldStaff())
                            ->dehydrated()
                            ->label('Người yêu cầu')
                            ->prefixIcon('heroicon-m-user')
                            ->helperText(fn() => auth()->user()?->isFieldStaff() ? 'Tự động gán theo tài khoản đăng nhập' : null),

                        Forms\Components\Select::make('status')
                            ->options(RequestStatus::options())
                            ->required()
                            ->default(RequestStatus::PENDING->value)
                            ->label('Trạng thái')
                            ->prefixIcon('heroicon-m-flag'),

                        Forms\Components\Textarea::make('reason')
                            ->columnSpanFull()
                            ->rows(2)
                            ->label('Lý do yêu cầu')
                            ->placeholder('Nhập lý do cần xem SĐT chủ nhà...'),
                    ]),

                Forms\Components\Section::make('Thông tin duyệt')
                    ->icon('heroicon-m-shield-check')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn($record) => !$record || $record->status === RequestStatus::PENDING->value)
                    ->schema([
                        Forms\Components\Textarea::make('admin_note')
                            ->columnSpanFull()
                            ->rows(2)
                            ->label('Ghi chú Admin'),

                        Forms\Components\Select::make('reviewed_by')
                            ->relationship('reviewer', 'name')
                            ->disabled()
                            ->label('Người duyệt')
                            ->prefixIcon('heroicon-m-user-circle'),

                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->disabled()
                            ->label('Ngày duyệt')
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->visible(fn($record) => $record && $record->status !== RequestStatus::PENDING->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('property.title')
                    ->limit(40)
                    ->tooltip(fn(OwnerPhoneRequest $record): string => $record->property?->title ?? '')
                    ->searchable()
                    ->label('Bất động sản')
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-home-modern'),

                Tables\Columns\TextColumn::make('requester.name')
                    ->sortable()
                    ->label('Người yêu cầu')
                    ->icon('heroicon-m-user')
                    ->description(fn(OwnerPhoneRequest $record): string => $record->reason ?? ''),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Trạng thái')
                    ->formatStateUsing(fn(string $state): string => RequestStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => RequestStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn(string $state): string => RequestStatus::tryFrom($state)?->getIcon() ?? 'heroicon-m-question-mark-circle'),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->sortable()
                    ->label('Người duyệt')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày duyệt')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(RequestStatus::options())
                    ->label('Trạng thái'),

                Tables\Filters\SelectFilter::make('area')
                    ->relationship('property.areaLocation', 'name')
                    ->label('Khu vuc')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('requester')
                    ->relationship('requester', 'name')
                    ->label('Người yêu cầu')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('quick_approve')
                    ->label('Duyệt')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt yêu cầu')
                    ->action(function (OwnerPhoneRequest $record) {
                        app(OwnerPhoneRequestService::class)->approve($record, Auth::user());
                        Notification::make()
                            ->title('Đã duyệt yêu cầu')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(OwnerPhoneRequest $record) => $record->status === RequestStatus::PENDING->value),

                Tables\Actions\Action::make('quick_reject')
                    ->label('Từ chối')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Lý do từ chối')
                            ->placeholder('Nhập lý do...'),
                    ])
                    ->action(function (OwnerPhoneRequest $record, array $data) {
                        app(OwnerPhoneRequestService::class)->reject($record, Auth::user(), $data['reason']);
                        Notification::make()
                            ->title('Đã từ chối yêu cầu')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn(OwnerPhoneRequest $record) => $record->status === RequestStatus::PENDING->value),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->slideOver(),
                    Tables\Actions\EditAction::make()->slideOver(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Duyệt hàng loạt')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if ($record->status === RequestStatus::PENDING->value) {
                                    app(OwnerPhoneRequestService::class)->approve($record, Auth::user());
                                    $count++;
                                }
                            });
                            Notification::make()
                                ->title("Đã duyệt $count yêu cầu")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Không có yêu cầu nào')
            ->emptyStateDescription('Các yêu cầu xem SĐT chủ nhà sẽ được hiển thị tại đây')
            ->emptyStateIcon('heroicon-o-phone');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwnerPhoneRequests::route('/'),
            'create' => Pages\CreateOwnerPhoneRequest::route('/create'),
            'view' => Pages\ViewOwnerPhoneRequest::route('/{record}'),
            'edit' => Pages\EditOwnerPhoneRequest::route('/{record}/edit'),
        ];
    }
}
