<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $activeNavigationIcon = 'heroicon-s-users';

    protected static ?string $modelLabel = 'Người dùng';
    protected static ?string $pluralModelLabel = 'Danh sách Người dùng';
    protected static ?string $navigationLabel = 'Người dùng';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin tài khoản')
                    ->icon('heroicon-m-user-circle')
                    ->description('Thông tin đăng nhập và xác thực')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Họ tên')
                            ->prefixIcon('heroicon-m-user')
                            ->placeholder('Nguyễn Văn A'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Email')
                            ->prefixIcon('heroicon-m-envelope')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn($livewire) => $livewire instanceof Pages\CreateUser)
                            ->maxLength(255)
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->label('Mật khẩu')
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->helperText(fn($livewire) => $livewire instanceof Pages\EditUser ? 'Để trống nếu không muốn đổi mật khẩu' : null),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Ngày xác thực email')
                            ->displayFormat('d/m/Y H:i')
                            ->prefixIcon('heroicon-m-check-badge'),
                    ]),

                Forms\Components\Section::make('Phân quyền & Khu vực')
                    ->icon('heroicon-m-shield-check')
                    ->description('Thiết lập quyền hạn và phạm vi hoạt động')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Vai trò')
                            ->prefixIcon('heroicon-m-key')
                            ->helperText('SUPER_ADMIN: Full quyền, OFFICE_ADMIN: Quản lý văn phòng, FIELD_STAFF: Nhân viên thực địa'),

                        Forms\Components\Select::make('area_ids')
                            ->multiple()
                            ->options(\App\Models\Area::all()->pluck('name', 'id'))
                            ->label('Khu vực phụ trách')
                            ->prefixIcon('heroicon-m-map-pin')
                            ->helperText('Chỉ áp dụng cho FIELD_STAFF'),
                    ]),

                Forms\Components\Section::make('Bảo mật & Khóa tài khoản')
                    ->icon('heroicon-m-shield-exclamation')
                    ->description('Quản lý trạng thái tài khoản')
                    ->collapsible()
                    ->collapsed(fn($record) => !$record?->is_locked)
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_locked')
                                ->label('Khóa tài khoản')
                                ->helperText('Người dùng sẽ không thể đăng nhập')
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, bool $state) {
                                    if ($state) {
                                        $set('locked_at', now());
                                    } else {
                                        $set('locked_at', null);
                                        $set('lock_reason', null);
                                    }
                                }),

                            Forms\Components\DateTimePicker::make('locked_at')
                                ->label('Thời gian khóa')
                                ->displayFormat('d/m/Y H:i')
                                ->disabled()
                                ->visible(fn(Forms\Get $get) => $get('is_locked')),
                        ]),

                        Forms\Components\Textarea::make('lock_reason')
                            ->label('Lý do khóa')
                            ->placeholder('Nhập lý do khóa tài khoản...')
                            ->rows(2)
                            ->visible(fn(Forms\Get $get) => $get('is_locked'))
                            ->required(fn(Forms\Get $get) => $get('is_locked')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Họ tên')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn(User $record): string => $record->email),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('Vai trò')
                    ->color(fn(string $state): string => match ($state) {
                        'SUPER_ADMIN' => 'danger',
                        'OFFICE_ADMIN' => 'warning',
                        'FIELD_STAFF' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'SUPER_ADMIN' => 'heroicon-m-shield-check',
                        'OFFICE_ADMIN' => 'heroicon-m-building-office',
                        'FIELD_STAFF' => 'heroicon-m-user',
                        default => 'heroicon-m-user-circle',
                    }),

                Tables\Columns\IconColumn::make('is_locked')
                    ->boolean()
                    ->label('Khóa')
                    ->trueIcon('heroicon-m-lock-closed')
                    ->falseIcon('heroicon-m-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime('d/m/Y')
                    ->label('Xác thực')
                    ->placeholder('Chưa xác thực')
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->icon(fn($state) => $state ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Vai trò')
                    ->options([
                        'SUPER_ADMIN' => 'Super Admin',
                        'OFFICE_ADMIN' => 'Quản lý Văn phòng',
                        'FIELD_STAFF' => 'Nhân viên Thực địa',
                    ]),

                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Trạng thái khóa')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đã khóa')
                    ->falseLabel('Đang hoạt động'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Xác thực email')
                    ->nullable()
                    ->placeholder('Tất cả')
                    ->trueLabel('Đã xác thực')
                    ->falseLabel('Chưa xác thực'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->slideOver(),
                    Tables\Actions\EditAction::make()->slideOver(),

                    Tables\Actions\Action::make('lock')
                        ->label('Khóa tài khoản')
                        ->icon('heroicon-m-lock-closed')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Lý do khóa')
                                ->required()
                                ->placeholder('Nhập lý do...'),
                        ])
                        ->action(function (User $record, array $data) {
                            app(UserService::class)->lock($record, $data['reason']);
                            Notification::make()
                                ->title('Đã khóa tài khoản')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn(User $record) => !$record->is_locked && $record->id !== auth()->id()),

                    Tables\Actions\Action::make('unlock')
                        ->label('Mở khóa')
                        ->icon('heroicon-m-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            app(UserService::class)->unlock($record);
                            Notification::make()
                                ->title('Đã mở khóa tài khoản')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(User $record) => $record->is_locked),

                    Tables\Actions\Action::make('reset_password')
                        ->label('Đặt lại mật khẩu')
                        ->icon('heroicon-m-key')
                        ->color('warning')
                        ->modalHeading('Đặt lại mật khẩu')
                        ->modalDescription(fn(User $record) => "Đặt mật khẩu mới cho: {$record->name}")
                        ->form([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('generate_password')
                                    ->label('🎲 Random mật khẩu')
                                    ->color('info')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        // Generate random password: 8 chars with letters and numbers
                                        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
                                        $set('new_password', $password);
                                        $set('new_password_confirmation', $password);
                                        $set('generated_password', $password);
                                    }),
                            ])->columnSpanFull(),

                            Forms\Components\Placeholder::make('generated_password_display')
                                ->label('Mật khẩu đã tạo (copy ngay!)')
                                ->content(fn(Forms\Get $get) => $get('generated_password') ?: '—')
                                ->extraAttributes([
                                    'class' => 'text-lg font-mono font-bold text-primary-600 dark:text-primary-400 select-all',
                                ])
                                ->visible(fn(Forms\Get $get) => filled($get('generated_password'))),

                            Forms\Components\Hidden::make('generated_password'),

                            Forms\Components\TextInput::make('new_password')
                                ->label('Mật khẩu mới')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->prefixIcon('heroicon-m-lock-closed')
                                ->helperText('Tối thiểu 8 ký tự')
                                ->live(),

                            Forms\Components\TextInput::make('new_password_confirmation')
                                ->label('Xác nhận mật khẩu')
                                ->password()
                                ->required()
                                ->same('new_password')
                                ->prefixIcon('heroicon-m-lock-closed'),
                        ])
                        ->action(function (User $record, array $data) {
                            $record->update([
                                'password' => Hash::make($data['new_password']),
                            ]);

                            Notification::make()
                                ->title('Đã đặt lại mật khẩu')
                                ->body("Mật khẩu của {$record->name} đã được cập nhật.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn(User $record) => $record->id !== auth()->id()),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn(User $record) => $record->id !== auth()->id()),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Thao tác'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có người dùng nào')
            ->emptyStateDescription('Tạo người dùng mới để quản lý hệ thống')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
