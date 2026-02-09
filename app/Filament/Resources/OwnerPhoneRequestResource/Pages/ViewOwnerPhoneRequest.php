<?php

declare(strict_types=1);
namespace App\Filament\Resources\OwnerPhoneRequestResource\Pages;

use App\Filament\Resources\OwnerPhoneRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOwnerPhoneRequest extends ViewRecord
{
    protected static string $resource = OwnerPhoneRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
