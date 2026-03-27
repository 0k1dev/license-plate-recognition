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
use Illuminate\Support\HtmlString;

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
        return parent::getEloquentQuery()
            ->with(['reporter', 'resolver', 'post.property', 'post.creator', 'files'])
            ->withCount('files');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin báo cáo')
                    ->icon('heroicon-m-flag')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('post_id')
                            ->relationship('post', 'id', fn(Builder $query) => $query->with('property'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn(\App\Models\Post $record): string => '#'.$record->id.' - '.($record->property?->title ?? 'Bài đăng'))
                            ->label('Bài đăng bị báo cáo')
                            ->prefixIcon('heroicon-m-document-text'),

                        Forms\Components\Select::make('reporter_id')
                            ->relationship('reporter', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Người báo cáo')
                            ->prefixIcon('heroicon-m-user'),

                        Forms\Components\Placeholder::make('reported_user_name')
                            ->label('Người đăng bài')
                            ->content(function (?Report $record): HtmlString {
                                $name = $record?->post?->creator?->name ?? 'Chưa xác định';
                                $email = $record?->post?->creator?->email;

                                return new HtmlString(
                                    '<div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">'
                                    . '<div class="text-sm font-semibold text-gray-950 dark:text-white">'.e($name).'</div>'
                                    . ($email ? '<div class="mt-1 text-xs text-gray-600 dark:text-gray-400">'.e($email).'</div>' : '')
                                    . '</div>'
                                );
                            }),

                        Forms\Components\Placeholder::make('property_title')
                            ->label('Bất động sản liên quan')
                            ->content(function (?Report $record): HtmlString {
                                $title = $record?->post?->property?->title ?? 'Chưa xác định';
                                $address = $record?->post?->property?->address;

                                return new HtmlString(
                                    '<div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">'
                                    . '<div class="text-sm font-semibold text-gray-950 dark:text-white">'.e($title).'</div>'
                                    . ($address ? '<div class="mt-1 text-xs text-gray-600 dark:text-gray-400">'.e($address).'</div>' : '')
                                    . '</div>'
                                );
                            }),

                        Forms\Components\Select::make('type')
                            ->options(static::reportTypeOptions())
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

                        Forms\Components\Placeholder::make('evidence_files')
                            ->columnSpanFull()
                            ->label('Bằng chứng đính kèm')
                            ->content(function (?Report $record): HtmlString {
                                if (! $record) {
                                    return new HtmlString(
                                        static::emptyEvidenceHtml()
                                    );
                                }

                                $record->loadMissing('files');

                                if ($record->files->isEmpty()) {
                                    return new HtmlString(
                                        static::emptyEvidenceHtml()
                                    );
                                }

                                return new HtmlString(static::evidenceGalleryHtml($record));
                            }),
                    ]),

                Forms\Components\Section::make('Xử lý báo cáo')
                    ->icon('heroicon-m-shield-check')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn($record) => ! $record || $record->status === ReportStatus::OPEN->value)
                    ->schema([
                        Forms\Components\Select::make('action')
                            ->options(static::resolutionActionOptions())
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
                        'scam', 'FRAUD_SCAM' => 'danger',
                        'spam', 'fake_info', 'PROPERTY_INFO', 'SELLER_BEHAVIOR' => 'warning',
                        'inappropriate' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => static::reportTypeLabel($state)),

                Tables\Columns\TextColumn::make('post.property.title')
                    ->label('Bài đăng / BĐS')
                    ->placeholder('Bài đăng đã xóa')
                    ->description(fn(Report $record): ?string => $record->post_id ? 'Post #'.$record->post_id : null)
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->tooltip(fn(Report $record): string => $record->content)
                    ->label('Nội dung')
                    ->wrap(),

                Tables\Columns\TextColumn::make('post.creator.name')
                    ->label('Người bị báo cáo')
                    ->placeholder('Chưa xác định')
                    ->description(fn(Report $record): ?string => $record->post?->creator?->email)
                    ->icon('heroicon-m-user-circle')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reporter.name')
                    ->sortable()
                    ->searchable()
                    ->label('Người báo cáo')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('files_count')
                    ->label('Bằng chứng')
                    ->badge()
                    ->formatStateUsing(fn(int $state): string => $state.' tệp')
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray'),

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
                    ->formatStateUsing(fn(?string $state): string => static::resolutionActionLabel($state))
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
                    ->options(static::reportTypeOptions())
                    ->label('Loại vi phạm'),

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
                            ->when($data['from'] ?? null, fn(Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, string $date) => $q->whereDate('created_at', '<=', $date));
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
                    ->action(function (Report $record, array $data): void {
                        app(ReportService::class)->markInProgress($record, auth()->user(), $data['note'] ?? null);

                        Notification::make()
                            ->title('Đã tiếp nhận báo cáo')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Report $record): bool => $record->status === ReportStatus::OPEN->value),

                Tables\Actions\Action::make('resolve')
                    ->label('Xử lý')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('action')
                            ->options(static::resolutionActionOptions())
                            ->required()
                            ->label('Hành động'),
                        Forms\Components\Textarea::make('note')
                            ->label('Ghi chú')
                            ->placeholder('Nhập ghi chú xử lý...'),
                    ])
                    ->action(function (Report $record, array $data): void {
                        app(ReportService::class)->resolve($record, auth()->user(), $data['action'], $data['note'] ?? null);

                        Notification::make()
                            ->title('Đã xử lý báo cáo')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Report $record): bool => in_array($record->status, [ReportStatus::OPEN->value, ReportStatus::IN_PROGRESS->value], true)),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->slideOver(),
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

    protected static function reportTypeOptions(): array
    {
        return [
            'POST_CONTENT' => 'Bài đăng có vấn đề',
            'SELLER_BEHAVIOR' => 'Người bán / nhân viên',
            'PROPERTY_INFO' => 'Thông tin bất động sản sai lệch',
            'FRAUD_SCAM' => 'Gian lận / lừa đảo',
            'spam' => 'Spam',
            'fake_info' => 'Thông tin sai',
            'scam' => 'Lừa đảo',
            'duplicate' => 'Trùng lặp',
            'inappropriate' => 'Không phù hợp',
            'other' => 'Khác',
        ];
    }

    protected static function reportTypeLabel(?string $value): string
    {
        return static::reportTypeOptions()[$value ?? ''] ?? ($value ?? '-');
    }

    protected static function resolutionActionOptions(): array
    {
        return [
            'HIDE_POST' => 'Ẩn bài đăng',
            'LOCK_USER' => 'Khóa tài khoản người đăng',
            'WARN' => 'Cảnh cáo người đăng',
            'NO_ACTION' => 'Không xử lý',
        ];
    }

    protected static function resolutionActionLabel(?string $value): string
    {
        return static::resolutionActionOptions()[$value ?? ''] ?? ($value ?? '-');
    }

    protected static function emptyEvidenceHtml(): string
    {
        return '<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-white/15 dark:bg-white/5 dark:text-gray-400">Chưa có bằng chứng.</div>';
    }

    protected static function evidenceGalleryHtml(Report $record): string
    {
        $cards = $record->files
            ->map(function ($file): string {
                $inlineUrl = route('files.download', ['file' => $file->id, 'inline' => 1]);
                $downloadUrl = $file->visibility === 'PUBLIC'
                    ? ($file->url ?? $inlineUrl)
                    : route('files.download', ['file' => $file->id]);
                $previewUrl = $file->visibility === 'PUBLIC'
                    ? ($file->is_image ? ($file->thumbnail_url ?? $file->url ?? $inlineUrl) : ($file->url ?? $inlineUrl))
                    : $inlineUrl;

                $preview = $file->is_image
                    ? '<a href="' . e($previewUrl) . '" target="_blank" class="block overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">'
                        . '<img src="' . e($previewUrl) . '" alt="' . e($file->original_name) . '" class="h-40 w-full object-cover bg-gray-100 dark:bg-white/5">'
                        . '</a>'
                    : '<a href="' . e($previewUrl) . '" target="_blank" class="flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-100 text-sm font-medium text-gray-600 dark:border-white/15 dark:bg-white/5 dark:text-gray-400">'
                        . 'Mở tệp'
                        . '</a>';

                return '<div class="rounded-xl border border-gray-200 bg-gray-100 p-3 dark:border-white/10 dark:bg-white/5">'
                    . $preview
                    . '<div class="mt-3">'
                    . '<div class="truncate text-sm font-semibold text-gray-900 dark:text-white">' . e($file->original_name) . '</div>'
                    . '<div class="mt-1 text-xs text-gray-600 dark:text-gray-400">' . e($file->human_size) . '</div>'
                    . '<div class="mt-3 flex gap-2">'
                    . '<a href="' . e($previewUrl) . '" target="_blank" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white dark:bg-white dark:text-gray-900">Xem</a>'
                    . '<a href="' . e($downloadUrl) . '" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 dark:border-white/15 dark:text-gray-200">Tải xuống</a>'
                    . '</div>'
                    . '</div>'
                    . '</div>';
            })
            ->implode('');

        return '<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">'
            . '<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">' . $cards . '</div>'
            . '</div>';
    }
}
