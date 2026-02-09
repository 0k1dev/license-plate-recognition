<?php

declare(strict_types=1);
namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Filament\Resources\FileResource\RelationManagers;
use App\Models\File;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                            ->options([
                                'PROPERTY_IMAGE' => 'Ảnh Bất động sản',
                                'LEGAL_DOC' => 'Tài liệu pháp lý',
                                'other' => 'Khác',
                            ])
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
                            ->view('filament.forms.components.file-preview') // We might need a custom view or just use an image placeholder
                            ->hidden(), // For now, let's just stick to table preview or simple image if it is an image
                        Forms\Components\FileUpload::make('path')
                            ->label('File')
                            ->disk('public')
                            ->visibility('public')
                            ->disabled() // Prevent changing the file content, only metadata
                            ->dehydrated(false) // Do not save this field
                    ])
                    ->visible(fn($record) => $record && str_contains($record->mime_type, 'image')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->disk('public')
                    ->label('Ảnh')
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
                    ->colors([
                        'primary' => 'PROPERTY_IMAGE',
                        'danger' => 'LEGAL_DOC',
                        'secondary' => 'other',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'PROPERTY_IMAGE' => 'Ảnh BĐS',
                        'LEGAL_DOC' => 'Pháp lý',
                        'other' => 'Khác',
                        default => $state,
                    })
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
                    ->options([
                        'PROPERTY_IMAGE' => 'Ảnh Bất động sản',
                        'LEGAL_DOC' => 'Tài liệu pháp lý',
                        'other' => 'Khác',
                    ])
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
                    ->url(fn(File $record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->path))
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
}
