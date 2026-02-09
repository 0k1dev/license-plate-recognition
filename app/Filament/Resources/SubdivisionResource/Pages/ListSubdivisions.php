<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubdivisionResource\Pages;

use App\Filament\Resources\SubdivisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubdivisions extends ListRecords
{
    protected static string $resource = SubdivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
