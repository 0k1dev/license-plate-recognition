<?php

declare(strict_types=1);
namespace App\Filament\Resources\OwnerPhoneRequestResource\Pages;

use App\Filament\Resources\OwnerPhoneRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerPhoneRequest extends EditRecord
{
    protected static string $resource = OwnerPhoneRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
