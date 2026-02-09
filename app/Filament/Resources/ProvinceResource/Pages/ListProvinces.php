<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use Filament\Resources\Pages\ListRecords;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Không cho tạo mới vì dữ liệu import từ API
            // Actions\CreateAction::make(),
        ];
    }
}
