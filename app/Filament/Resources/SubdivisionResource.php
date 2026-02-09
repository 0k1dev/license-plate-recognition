<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SubdivisionResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubdivisionResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Quận/Huyện/Phường/Xã';

    protected static ?string $modelLabel = 'Phường/Xã';

    protected static ?string $pluralModelLabel = 'Quận/Huyện/Phường/Xã';

    protected static ?string $navigationGroup = 'Danh mục';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false; // Ẩn khỏi menu chính để dùng trang Static JSON

    public static function getEloquentQuery(): Builder
    {
        // Chỉ lấy Subdivisions (district và ward) và Eager Load 'parent' để tránh N+1
        return parent::getEloquentQuery()
            ->whereIn('level', ['district', 'ward'])
            ->with(['parent']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin cơ bản')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Tỉnh/Thành phố')
                            ->options(\App\Models\Area::getCachedProvincesOptions())
                            ->searchable()
                            ->required()
                            ->helperText('Chọn tỉnh/thành phố mà đơn vị này thuộc về'),

                        Forms\Components\TextInput::make('name')
                            ->label('Tên quận/huyện/phường/xã')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('Mã code')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('api_code')
                            ->label('Mã API')
                            ->numeric(),

                        Forms\Components\Select::make('division_type')
                            ->label('Loại đơn vị')
                            ->options([
                                'quận' => 'Quận',
                                'huyện' => 'Huyện',
                                'thị xã' => 'Thị xã',
                                'thành phố' => 'Thành phố (thuộc tỉnh)',
                                'phường' => 'Phường',
                                'xã' => 'Xã',
                                'thị trấn' => 'Thị trấn',
                            ])
                            ->required(),

                        Forms\Components\Select::make('level')
                            ->label('Cấp hành chính')
                            ->options([
                                'district' => 'Quận/Huyện',
                                'ward' => 'Phường/Xã',
                            ])
                            ->required()
                            ->default('ward'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cấu hình')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Thứ tự sắp xếp')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Kích hoạt')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label('Mô tả')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Tỉnh/TP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tên')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('division_type')
                    ->label('Loại')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'quận' => 'success',
                        'huyện' => 'info',
                        'thị xã' => 'warning',
                        'phường' => 'primary',
                        'xã' => 'gray',
                        'thị trấn' => 'secondary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('level')
                    ->label('Cấp')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'district' => 'Quận/Huyện',
                        'ward' => 'Phường/Xã',
                        default => $state,
                    })
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('api_code')
                    ->label('Mã API')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Trạng thái')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Tỉnh/Thành phố')
                    ->options(\App\Models\Area::getCachedProvincesOptions())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('division_type')
                    ->label('Loại đơn vị')
                    ->options([
                        'quận' => 'Quận',
                        'huyện' => 'Huyện',
                        'thị xã' => 'Thị xã',
                        'phường' => 'Phường',
                        'xã' => 'Xã',
                        'thị trấn' => 'Thị trấn',
                    ]),

                Tables\Filters\SelectFilter::make('level')
                    ->label('Cấp')
                    ->options([
                        'district' => 'Quận/Huyện',
                        'ward' => 'Phường/Xã',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Trạng thái')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đang hoạt động')
                    ->falseLabel('Đã tắt'),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(), // Bỏ Edit
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([ // Bỏ Bulk Actions
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListSubdivisions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // Filament check canEdit để hiện nút Save, nhưng nếu không có page Edit thì cũng ok.
    // Tuy nhiên override cho chắc.
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
