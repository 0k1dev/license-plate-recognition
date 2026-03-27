<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'Dự án';
    protected static ?string $pluralModelLabel = 'Danh sách Dự án';
    protected static ?string $navigationLabel = 'Dự án';
    protected static ?string $navigationGroup = 'Danh mục';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Tên dự án'),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->label('Slug'),
                Forms\Components\Select::make('area_id')
                    ->relationship(
                        name: 'area',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->provinces()
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Chọn khu vực')
                    ->label('Khu vực'),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->directory('projects')
                    ->label('Hình ảnh')
                    ->disabled(fn(string $operation): bool => $operation === 'view'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Mô tả'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Tên dự án'),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->label('Slug'),
                Tables\Columns\TextColumn::make('area.name')
                    ->sortable()
                    ->label('Tỉnh/Thành phố'),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Hình ảnh')
                    ->state(fn($record) => $record->image ? app(\App\Services\ImageService::class)->thumbnailUrl($record->image, 'thumb') : null)
                    ->defaultImageUrl(asset('images/no-image.svg')),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
