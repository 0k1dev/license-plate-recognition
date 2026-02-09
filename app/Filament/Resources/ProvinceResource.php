<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProvinceResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProvinceResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Tỉnh/Thành phố';

    protected static ?string $modelLabel = 'Tỉnh/Thành phố';

    protected static ?string $pluralModelLabel = 'Tỉnh/Thành phố';

    protected static ?string $navigationGroup = 'Danh mục';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        // Chỉ lấy Provinces
        return parent::getEloquentQuery()->where('level', 'province');
    }

    // ... (form)

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ...
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                // ...
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProvinces::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
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
}
