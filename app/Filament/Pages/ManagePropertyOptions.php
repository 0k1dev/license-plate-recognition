<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\PropertyOptionsSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManagePropertyOptions extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Tiện ích';
    protected static ?string $title = 'Quản lý Tiện ích';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 7;

    protected static string $settings = PropertyOptionsSettings::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Danh sách lựa chọn')
                ->columns(2)
                ->schema([
                    Forms\Components\TagsInput::make('amenities')
                        ->label('Tiện ích')
                        ->placeholder('Nhập tiện ích rồi Enter')
                        ->helperText('Dùng cho field tiện ích xung quanh BĐS.')
                        ->columnSpanFull()
                        ->required(),

                    Forms\Components\TagsInput::make('directions')
                        ->label('Hướng nhà')
                        ->placeholder('Nhập hướng rồi Enter')
                        ->required(),

                    Forms\Components\TagsInput::make('shapes')
                        ->label('Hình dạng đất')
                        ->placeholder('Nhập hình dạng rồi Enter')
                        ->required(),

                    Forms\Components\TagsInput::make('location_types')
                        ->label('Vị trí')
                        ->placeholder('Nhập vị trí rồi Enter')
                        ->required(),
                ]),

            Forms\Components\Section::make('Tình trạng pháp lý')
                ->schema([
                    Forms\Components\KeyValue::make('legal_statuses')
                        ->label('Pháp lý')
                        ->keyLabel('Mã')
                        ->valueLabel('Nhãn hiển thị')
                        ->addActionLabel('Thêm trạng thái')
                        ->columnSpanFull()
                        ->required(),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['amenities'] = $data['amenities'] ?? [];
        $data['directions'] = $data['directions'] ?? [];
        $data['shapes'] = $data['shapes'] ?? [];
        $data['location_types'] = $data['location_types'] ?? [];
        $data['legal_statuses'] = $data['legal_statuses'] ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['amenities', 'directions', 'shapes', 'location_types'] as $field) {
            $data[$field] = collect($data[$field] ?? [])
                ->map(fn($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $data['legal_statuses'] = collect($data['legal_statuses'] ?? [])
            ->mapWithKeys(function ($value, $key): array {
                $normalizedKey = trim((string) $key);
                $normalizedValue = trim((string) $value);

                if ($normalizedKey === '' || $normalizedValue === '') {
                    return [];
                }

                return [$normalizedKey => $normalizedValue];
            })
            ->all();

        return $data;
    }
}
