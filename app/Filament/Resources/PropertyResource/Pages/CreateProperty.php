<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure created_by is set to current user ID
        $data['created_by'] = Auth::id();

        Log::info("Mutating form data for new property creation", [
            'user_id' => $data['created_by'],
            'has_images' => isset($data['new_property_images']),
            'has_docs' => isset($data['new_legal_documents']),
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::info("Property created successfully", [
            'property_id' => $this->record->id,
        ]);
    }
}
