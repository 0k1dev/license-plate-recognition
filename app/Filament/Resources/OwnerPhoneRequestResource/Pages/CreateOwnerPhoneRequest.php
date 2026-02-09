<?php

declare(strict_types=1);
namespace App\Filament\Resources\OwnerPhoneRequestResource\Pages;

use App\Filament\Resources\OwnerPhoneRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOwnerPhoneRequest extends CreateRecord
{
    protected static string $resource = OwnerPhoneRequestResource::class;
}
