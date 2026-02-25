<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\PostStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Tin đăng';

    protected static ?string $recordTitleAttribute = 'id';

    public function canCreate(): bool
    {
        // Chỉ cho tạo tin đăng khi BĐS đã được duyệt
        return $this->getOwnerRecord()->approval_status === 'APPROVED';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->options(PostStatus::options())
                    ->required()
                    ->default(PostStatus::VISIBLE->value)
                    ->label('Trạng thái'),
                Forms\Components\DateTimePicker::make('visible_until')
                    ->label('Hiển thị đến')
                    ->displayFormat('d/m/Y H:i')
                    ->default(now()->addDays(30)),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Trạng thái')
                    ->formatStateUsing(fn(string $state): string => PostStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn(string $state): string => PostStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn(string $state): string => PostStatus::tryFrom($state)?->getIcon() ?? 'heroicon-m-question-mark-circle'),
                Tables\Columns\TextColumn::make('visible_until')
                    ->dateTime('d/m/Y H:i')
                    ->label('Hết hạn')
                    ->color(fn($state) => $state && $state->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Người tạo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Ngày tạo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PostStatus::options()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tạo tin đăng')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('renew')
                    ->label('Gia hạn')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => PostStatus::VISIBLE->value,
                            'visible_until' => now()->addDays(30),
                        ]);
                        Notification::make()
                            ->title('Đã gia hạn thêm 30 ngày')
                            ->success()
                            ->send();
                    })
                    ->visible(fn($record) => in_array($record->status, [PostStatus::HIDDEN->value, PostStatus::EXPIRED->value])),
                Tables\Actions\Action::make('hide')
                    ->label('Ẩn')
                    ->icon('heroicon-m-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => PostStatus::HIDDEN->value]);
                    })
                    ->visible(fn($record) => $record->status === PostStatus::VISIBLE->value),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
