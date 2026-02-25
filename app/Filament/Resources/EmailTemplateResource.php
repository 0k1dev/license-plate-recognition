<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Filament\Resources\EmailTemplateResource\RelationManagers;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Email Templates';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->disabled(), // Không cho sửa tên system template
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(191)
                            ->disabled() // Không cho sửa key system template
                            ->helperText('System key identifier'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(191),
                    ]),

                Forms\Components\Section::make('Template Content')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Advanced Configuration')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('language')
                            ->default('vi')
                            ->maxLength(8),
                        Forms\Components\TextInput::make('view')
                            ->default('emails.default')
                            ->maxLength(191)
                            ->disabled() // Không cho sửa view path tránh lỗi hệ thống
                            ->helperText('Blade view path linked to system logic'),
                        Forms\Components\TextInput::make('from')
                            ->email(),
                        Forms\Components\TextInput::make('cc'),
                        Forms\Components\TextInput::make('bcc'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => "Blade Preview: {$record->view}")
                    ->modalContent(function ($record) {
                        try {
                            $view = $record->view;

                            // 1. Fake Data
                            $data = [];
                            $user = \App\Models\User::first() ?? new \App\Models\User(['name' => 'Demo User', 'email' => 'demo@example.com']);

                            if (str_contains($view, 'otp')) {
                                $data = [
                                    'user' => $user,
                                    'otp' => '123456',
                                    'expiresIn' => 10,
                                    'userName' => $user->name,
                                    'userEmail' => $user->email,
                                ];
                            } elseif (str_contains($view, 'property-approved')) {
                                $property = \App\Models\Property::first() ?? new \App\Models\Property(['title' => 'Căn hộ River View', 'address' => 'District 2, HCMC']);
                                $data = [
                                    'user' => $user,
                                    'userName' => $user->name,
                                    'property' => $property,
                                ];
                            } elseif (str_contains($view, 'property-rejected')) {
                                $property = \App\Models\Property::first() ?? new \App\Models\Property(['title' => 'Căn hộ River View', 'address' => 'District 2, HCMC']);
                                $data = [
                                    'user' => $user,
                                    'userName' => $user->name,
                                    'property' => $property,
                                    'reason' => 'Thông tin hình ảnh chưa rõ ràng.',
                                ];
                            } elseif (str_contains($view, 'phone')) {
                                $property = \App\Models\Property::first() ?? new \App\Models\Property(['title' => 'Nhà phố mặt tiền Quận 1', 'contact_name' => 'Nguyễn Văn Chủ', 'contact_phone' => '0912345678']);
                                // Make sure property has owner_phone for view
                                $property->owner_phone = $property->owner_phone ?? '0912345678';
                                $property->address = $property->address ?? '123 Đường ABC, Quận 1';

                                $data = [
                                    'user' => $user,
                                    'userName' => $user->name,
                                    'phoneRequest' => (object) ['property' => $property],
                                    'ownerName' => $property->contact_name,
                                    'ownerPhone' => $property->contact_phone,
                                    'propertyTitle' => $property->title,
                                    'property' => $property, // <-- Add this for Blade View
                                ];
                            }

                            // 2. Render Blade
                            if (view()->exists($view)) {
                                $data['dbContent'] = $record->content;
                                $html = view($view, $data)->render();
                            } else {
                                $html = "<div class='text-red-500'>View [{$view}] not found! Please check 'view' column in database.</div>";
                            }

                            return view('filament.components.email-preview', ['content' => $html]);
                        } catch (\Exception $e) {
                            return view('filament.components.email-preview', ['content' => "Error rendering: " . $e->getMessage()]);
                        }
                    })
                    ->modalSubmitAction(false) // Read-only
                    ->modalWidth('7xl')
                    ->modalCancelAction(fn($action) => $action->label('Close')),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk delete
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
            'index' => Pages\ListEmailTemplates::route('/'),
            // 'create' => Pages\CreateEmailTemplate::route('/create'), // Removed
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
