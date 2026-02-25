<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ReportStatus;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Services\ReportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $activeNavigationIcon = 'heroicon-s-flag';

    protected static ?string $modelLabel = 'Báo cáo vi phạm';
    protected static ?string $pluralModelLabel = 'Danh sách Báo cáo';
    protected static ?string $navigationLabel = 'Báo cáo';
    protected static ?string $navigationGroup = 'Kiểm duyệt';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', ReportStatus::OPEN->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reporter', 'resolver']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin báo cáo')
                    ->icon('heroicon-m-flag')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('reportable_type')
                            ->required()
                            ->maxLength(255)
                            ->label('Loại đối tượng')
                            ->prefixIcon('heroicon-m-cube'),

                        Forms\Components\TextInput::make('reportable_id')
                            ->required()
                            ->numeric()
                            ->label('ID đối tượng')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Forms\Components\Select::make('reporter_id')
                            ->relationship('reporter', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Người báo cáo')
                            ->prefixIcon('heroicon-m-user'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'spam' => '🚫 Spam',
                                'fake_info' => '⚠️ Thông tin sai lệch',
                                'scam' => '🔴 Lừa đảo',
                                'duplicate' => '📋 Trùng lặp',
                                'inappropriate' => '❌ Nội dung không phù hợp',
                                'other' => '📝 Khác',
                            ])
                            ->required()
                            ->label('Loại vi phạm')
                            ->prefixIcon('heroicon-m-exclamation-triangle'),

                        Forms\Components\Select::make('status')
                            ->options(ReportStatus::options())
                            ->required()
                            ->default(ReportStatus::OPEN->value)
                            ->label('Trạng thái')
                            ->prefixIcon('heroicon-m-flag'),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Nội dung báo cáo')
                            ->placeholder('Mô tả chi tiết về vi phạm...'),
                    ]),

                Forms\Components\Section::make('Xử lý báo cáo')
                    ->icon('heroicon-m-shield-check')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn($record) => !$record || $record->status === ReportStatus::OPEN->value)
                    ->schema([
                        Forms\Components\Select::make('action')
                            ->options([
                                'HIDE_POST' => '👁️ Ẩn bài đăng',
                                'LOCK_USER' => '🔒 Khóa tài khoản',
                                'WARN' => '⚠️ Cảnh cáo',
                                'NO_ACTION' => '✅ Không xử lý',
                            ])
                            ->label('Hành động xử lý')
                            ->prefixIcon('heroicon-m-bolt'),

                        Forms\Components\Select::make('resolved_by')
                            ->relationship('resolver', 'name')
                            ->disabled()
                            ->label('Người xử lý')
                            ->prefixIcon('heroicon-m-user-circle'),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->disabled()
                            ->label('Ngày xử lý')
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\Textarea::make('admin_note')
                            ->columnSpanFull()
                            ->rows(2)
                            ->label('Ghi chú Admin'),
                    ])
                    ->visible(fn($record) => $record && $record->status !== ReportStatus::OPEN->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Loại vi phạm')
                    ->color(fn(string $state): string => match ($state) {
                        'scam' => 'danger',
                        'spam', 'fake_info' => 'warning',
                        'inappropriate' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'spam' => 'Spam',
                        'fake_info' => 'Thông tin sai',
                        'scam' => 'Lừa đảo',
                        'duplicate' => 'Trùng lặp',
                        'inappropriate' => 'Không phù hợp',
                        'other' => 'Khác',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->tooltip(fn(Report $record): string => $record->content)
                    ->label('Nội dung')
                    ->weight(FontWeight::SemiBold)
                    ->wrap(),

                Tables\Columns\TextColumn::make('reporter.name')
                    ->sortable()
                    ->label('Người báo cáo')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn(string $state): string => class_basename($state))
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Trạng thái')
                    ->formatStateUsing(fn(string $state): string => ReportStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => ReportStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn(string $state): string => ReportStatus::tryFrom($state)?->getIcon() ?? 'heroicon-m-question-mark-circle'),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->label('Hành động')
                    ->placeholder('-')
                    ->color(fn(?string $state): string => match ($state) {
                        'LOCK_USER' => 'danger',
                        'HIDE_POST' => 'warning',
                        'WARN' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ReportStatus::options())
                    ->label('Trạng thái'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'spam' => 'Spam',
                        'fake_info' => 'Thông tin sai',
                        'scam' => 'Lừa đảo',
                        'duplicate' => 'Trùng lặp',
                        'inappropriate' => 'Không phù hợp',
                        'other' => 'Khác',
                    ])
                    ->label('Loại vi phạm'),

                Tables\Filters\SelectFilter::make('reportable_type')
                    ->label('Loại đối tượng')
                    ->options([
                        'App\Models\Post'     => 'Tin đăng',
                        'App\Models\User'     => 'Người dùng',
                        'App\Models\Property' => 'Bất động sản',
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
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\Action::make('mark_in_progress')
                    ->label('Tiếp nhận')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Tiếp nhận báo cáo')
                    ->modalDescription('Báo cáo sẽ chuyển sang trạng thái Đang xử lý.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Ghi chú')
                            ->placeholder('Ghi chú tiếp nhận...'),
                    ])
                    ->action(function (Report $record, array $data) {
                        app(ReportService::class)->markInProgress($record, auth()->user(), $data['note'] ?? null);
                        Notification::make()
                            ->title('Đã tiếp nhận báo cáo')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Report $record) => $record->status === ReportStatus::OPEN->value),

                // Quick resolve dropdown
                Tables\Actions\Action::make('resolve')
                    ->label('Xử lý')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('action')
                            ->options([
                                'HIDE_POST' => '👁️ Ẩn bài đăng',
                                'LOCK_USER' => '🔒 Khóa tài khoản',
                                'WARN' => '⚠️ Cảnh cáo',
                                'NO_ACTION' => '✅ Không xử lý',
                            ])
                            ->required()
                            ->label('Hành động'),
                        Forms\Components\Textarea::make('note')
                            ->label('Ghi chú')
                            ->placeholder('Nhập ghi chú xử lý...'),
                    ])
                    ->action(function (Report $record, array $data) {
                        app(ReportService::class)->resolve($record, auth()->user(), $data['action'], $data['note'] ?? null);
                        Notification::make()
                            ->title('Đã xử lý báo cáo')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Report $record) => in_array($record->status, [ReportStatus::OPEN->value, ReportStatus::IN_PROGRESS->value])),

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
                ]),
            ])
            ->emptyStateHeading('Không có báo cáo nào')
            ->emptyStateDescription('Các báo cáo vi phạm sẽ được hiển thị tại đây')
            ->emptyStateIcon('heroicon-o-flag');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'view' => Pages\ViewReport::route('/{record}'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
