<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Filament\Resources\AreaResource\RelationManagers;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AreaResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'Khu vực';
    protected static ?string $pluralModelLabel = 'Danh sách Khu vực';
    protected static ?string $navigationLabel = 'Khu vực';
    protected static ?string $navigationGroup = 'Danh mục';
    protected static ?int $navigationSort = 1;

    // Ẩn khỏi navigation vì đã tách thành ProvinceResource và SubdivisionResource
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Tên khu vực'),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->label('Mã khu vực'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Mô tả'),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->label('Kích hoạt'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Tên khu vực'),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->label('Mã khu vực'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Kích hoạt'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'view' => Pages\ViewArea::route('/{record}'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
