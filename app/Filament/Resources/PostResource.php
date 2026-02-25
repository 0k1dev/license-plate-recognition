<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PostStatus;
use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Services\PostService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $activeNavigationIcon = 'heroicon-s-rectangle-stack';

    protected static ?string $modelLabel = 'Tin đăng';
    protected static ?string $pluralModelLabel = 'Danh sách tin đăng';
    protected static ?string $navigationGroup = 'Quản lý BĐS';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', PostStatus::PENDING->value)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['property', 'creator']);

        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // Posts created by me
            $q->where('created_by', $user->id)
                // Or Posts belonging to Properties I can see
                ->orWhereHas('property', function ($pq) use ($user) {
                    $pq->withinUserAreas($user);
                });
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin bài đăng')
                    ->icon('heroicon-m-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->relationship(
                                'property',
                                'title',
                                fn($query) =>
                                $query->where('approval_status', 'APPROVED')
                            )
                            ->required()
                            ->searchable()
                            ->label('Bất động sản')
                            ->prefixIcon('heroicon-m-home-modern')
                            ->helperText('Chỉ hiển thị BĐS đã được duyệt')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options(PostStatus::options())
                            ->required()
                            ->default(PostStatus::PENDING->value)
                            ->label('Trạng thái')
                            ->prefixIcon('heroicon-m-flag')
                            ->live(),

                        Forms\Components\DateTimePicker::make('visible_until')
                            ->label('Hiển thị đến')
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()->addDays(30))
                            ->prefixIcon('heroicon-m-calendar')
                            ->helperText('Tin sẽ tự động ẩn sau ngày này'),

                        Forms\Components\Select::make('created_by')
                            ->relationship('creator', 'name')
                            ->default(auth()->id())
                            ->disabled()
                            ->required()
                            ->label('Người tạo')
                            ->prefixIcon('heroicon-m-user'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('property.title')
                    ->limit(50)
                    ->tooltip(fn(Post $record): string => $record->property?->title ?? '')
                    ->searchable()
                    ->label('Bất động sản')
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-home-modern'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Trạng thái')
                    ->formatStateUsing(fn(string $state): string => PostStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => PostStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn(string $state): string => PostStatus::tryFrom($state)?->getIcon() ?? 'heroicon-m-question-mark-circle'),

                Tables\Columns\TextColumn::make('visible_until')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Hết hạn')
                    ->color(fn($state) => $state && $state->isPast() ? 'danger' : 'success')
                    ->icon(fn($state) => $state && $state->isPast() ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-calendar')
                    ->description(function ($state) {
                        if (!$state) return null;
                        $days = now()->diffInDays($state, false);
                        if ($days < 0) return 'Đã hết hạn ' . abs($days) . ' ngày';
                        if ($days === 0) return 'Hết hạn hôm nay!';
                        return 'Còn ' . $days . ' ngày';
                    }),

                Tables\Columns\TextColumn::make('renew_count')
                    ->label('Gia hạn')
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state >= config('bds.max_post_renew', 3) => 'danger',
                        $state > 0 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(int $state): string => $state . '/' . config('bds.max_post_renew', 3))
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Người tạo')
                    ->icon('heroicon-m-user')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PostStatus::options())
                    ->label('Trạng thái'),

                Tables\Filters\SelectFilter::make('area')
                    ->relationship('property.areaLocation', 'name')
                    ->label('Khu vực')
                    ->searchable(),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Sắp hết hạn (7 ngày)')
                    ->query(fn(Builder $query) => $query->where('status', PostStatus::VISIBLE->value)
                        ->whereBetween('visible_until', [now(), now()->addDays(7)])),

                Tables\Filters\Filter::make('expired')
                    ->label('Đã hết hạn')
                    ->query(fn(Builder $query) => $query->where('visible_until', '<', now())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver(),

                    Tables\Actions\Action::make('approve_post')
                        ->label('Duyệt bài')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Duyệt bài đăng')
                        ->modalDescription('Bài đăng sẽ được chuyển sang trạng thái HIỂN THỊ và có hiệu lực 30 ngày.')
                        ->action(function (Post $record) {
                            app(PostService::class)->approve($record);
                            Notification::make()
                                ->title('Đã duyệt bài đăng')
                                ->body('Bài đăng đã được duyệt và hiển thị.')
                                ->success()
                                ->send();
                        })
                        ->visible(
                            fn(Post $record) =>
                            $record->status === PostStatus::PENDING->value
                                && (auth()->user()->isSuperAdmin() || auth()->user()->isOfficeAdmin())
                        ),

                    Tables\Actions\EditAction::make()
                        ->slideOver(),


                    Tables\Actions\Action::make('renew')
                        ->label('Gia hạn 30 ngày')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Gia hạn tin đăng')
                        ->modalDescription('Tin sẽ được gia hạn thêm 30 ngày kể từ bây giờ')
                        ->action(function (Post $record) {
                            app(PostService::class)->renew($record, 30);
                            Notification::make()
                                ->title('Đã gia hạn thành công')
                                ->body('Tin đăng sẽ hiển thị đến ' . now()->addDays(30)->format('d/m/Y'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Post $record) => in_array($record->status, [PostStatus::HIDDEN->value, PostStatus::EXPIRED->value]) && $record->renew_count < config('bds.max_post_renew', 3) && auth()->user()->can('update', $record)),

                    Tables\Actions\Action::make('renew_custom')
                        ->label('Gia hạn tùy chọn')
                        ->icon('heroicon-m-calendar-days')
                        ->color('info')
                        ->form([
                            Forms\Components\DateTimePicker::make('visible_until')
                                ->label('Hiển thị đến')
                                ->required()
                                ->minDate(now())
                                ->default(now()->addDays(30)),
                        ])
                        ->action(function (Post $record, array $data) {
                            app(PostService::class)->setVisible($record, $data['visible_until']);
                            Notification::make()
                                ->title('Đã gia hạn thành công')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Post $record) => in_array($record->status, [PostStatus::HIDDEN->value, PostStatus::EXPIRED->value]) && $record->renew_count < config('bds.max_post_renew', 3) && auth()->user()->can('update', $record)),


                    Tables\Actions\Action::make('hide')
                        ->label('Ẩn tin')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Post $record) {
                            app(PostService::class)->hide($record);
                            Notification::make()
                                ->title('Đã ẩn tin')
                                ->warning()
                                ->send();

                            /** @var \App\Models\User $user */
                            $user = auth()->user();
                            if ($record->creator && $record->creator->id !== $user->id) {
                                Notification::make()
                                    ->title('Tin đăng đã bị ẩn')
                                    ->body("Tin đăng cho BĐS \"{$record->property->title}\" đã bị ẩn bởi Admin.")
                                    ->warning()
                                    ->sendToDatabase($record->creator);
                            }
                        })
                        ->visible(fn(Post $record) => $record->status === PostStatus::VISIBLE->value && auth()->user()->can('update', $record)),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Xóa tin đăng')
                        ->modalDescription('Bạn có chắc chắn muốn xóa tin này? Hành động này không thể hoàn tác.'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Thao tác'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkRenew')
                        ->label('Gia hạn hàng loạt')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if (in_array($record->status, [PostStatus::HIDDEN->value, PostStatus::EXPIRED->value])) {
                                    app(PostService::class)->renew($record, 30);
                                    $count++;
                                }
                            });
                            Notification::make()
                                ->title("Đã gia hạn $count tin đăng")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulkHide')
                        ->label('Ẩn hàng loạt')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            /** @var \App\Models\User $user */
                            $user = auth()->user();
                            $hiddenCount = 0;

                            $records->each(function ($record) use ($user, &$hiddenCount) {
                                if ($record->status === PostStatus::VISIBLE->value) {
                                    app(PostService::class)->hide($record);
                                    $hiddenCount++;

                                    if ($record->creator && $record->creator->id !== $user->id) {
                                        Notification::make()
                                            ->title('Tin đăng đã bị ẩn')
                                            ->body("Tin đăng cho BĐS \"{$record->property->title}\" đã bị ẩn bởi Admin.")
                                            ->warning()
                                            ->sendToDatabase($record->creator);
                                    }
                                }
                            });
                            Notification::make()
                                ->title('Đã ẩn ' . $hiddenCount . ' tin')
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Chưa có tin đăng nào')
            ->emptyStateDescription('Tạo tin đăng mới từ trang Bất động sản')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
