<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Models\File;
use App\Support\PropertyOptionResolver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FileResource extends Resource
{
    use \App\Traits\HasUserMenuPreferences;

    protected static ?string $model = File::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Tệp tin';
    protected static ?string $pluralModelLabel = 'Quản lý File';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin tệp')
                    ->schema([
                        Forms\Components\TextInput::make('original_name')
                            ->label('Tên gốc')
                            ->disabled(),
                        Forms\Components\TextInput::make('filename')
                            ->label('Tên lưu trữ')
                            ->disabled(),
                        Forms\Components\Select::make('uploaded_by')
                            ->relationship('uploader', 'name')
                            ->label('Người tải lên')
                            ->disabled(),
                        Forms\Components\TextInput::make('size')
                            ->label('Kích thước')
                            ->formatStateUsing(fn($state) => number_format($state / 1024, 2) . ' KB')
                            ->disabled(),
                        Forms\Components\TextInput::make('mime_type')
                            ->label('Loại')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Ngày tải lên')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Cấu hình')
                    ->schema([
                        Forms\Components\Select::make('purpose')
                            ->label('Mục đích')
                            ->options(self::getPurposeOptions())
                            ->required(),
                        Forms\Components\Select::make('visibility')
                            ->label('Quyền truy cập')
                            ->options([
                                'PUBLIC' => 'Công khai',
                                'PRIVATE' => 'Riêng tư',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Xem trước')
                    ->schema([
                        Forms\Components\ViewField::make('preview')
                            ->view('filament.forms.components.file-preview')
                            ->hidden(),
                        Forms\Components\FileUpload::make('path')
                            ->label('File')
                            ->disk('public')
                            ->visibility('public')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->visible(fn($record) => $record && str_contains($record->mime_type, 'image')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Ảnh')
                    ->state(fn($record) => $record->visibility === 'PUBLIC' && str_contains($record->mime_type ?? '', 'image') ? app(\App\Services\ImageService::class)->thumbnailUrl($record->path, 'thumb') : null)
                    ->visible(fn($record) => str_contains($record->mime_type ?? '', 'image')),
                Tables\Columns\TextColumn::make('original_name')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(fn($record) => $record->original_name)
                    ->label('Tên file'),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Người tải')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        $state === 'PROPERTY_IMAGE' => 'primary',
                        $state === 'AVATAR' => 'info',
                        PropertyOptionResolver::isLegalDocumentPurpose($state) => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => self::getPurposeLabel($state))
                    ->label('Loại'),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->colors([
                        'success' => 'PUBLIC',
                        'warning' => 'PRIVATE',
                    ])
                    ->label('Quyền'),
                Tables\Columns\TextColumn::make('size')
                    ->formatStateUsing(fn($state) => number_format($state / 1024, 0) . ' KB')
                    ->sortable()
                    ->label('Size'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Ngày tạo'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('purpose')
                    ->options(self::getPurposeOptions())
                    ->label('Mục đích'),
                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'PUBLIC' => 'Công khai',
                        'PRIVATE' => 'Riêng tư',
                    ])
                    ->label('Quyền truy cập'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Tải về')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(File $record) => $record->visibility === 'PRIVATE'
                        ? route('files.download', $record)
                        : \Illuminate\Support\Facades\Storage::disk('public')->url($record->path))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFiles::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getPurposeOptions(): array
    {
        return [
            'PROPERTY_IMAGE' => 'Ảnh Bất động sản',
            'AVATAR' => 'Ảnh đại diện',
            ...PropertyOptionResolver::legalStatusMap(),
        ];
    }

    protected static function getPurposeLabel(string $purpose): string
    {
        $options = self::getPurposeOptions();

        return $options[$purpose] ?? $purpose;
    }
}
