<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Models\File;
use App\Models\Property;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->appendNewFiles();
    }

    /**
     * Append new files from the FileUpload component (do NOT affect existing files)
     */
    protected function appendNewFiles(): void
    {
        /** @var Property $record */
        $record = $this->record;
        $data = $this->form->getRawState();

        // Append new property images from form
        $newImages = $data['new_property_images'] ?? [];
        if (!empty($newImages)) {
            $this->saveFilesToDatabase($record, $newImages, 'PROPERTY_IMAGE', 'PUBLIC');
        }
    }

    /**
     * Save files to database (append mode)
     */
    protected function saveFilesToDatabase(
        Property $record,
        array $filePaths,
        string $purpose,
        string $visibility
    ): void {
        // Get max order for this purpose
        $maxOrder = $record->files()->where('purpose', $purpose)->max('order') ?? -1;

        // Check if there's already a primary image
        $hasPrimary = $record->files()
            ->where('purpose', $purpose)
            ->where('is_primary', true)
            ->exists();

        $count = 0;
        foreach ($filePaths as $path) {
            if (empty($path)) continue;

            $disk = Storage::disk('public');

            // Only create if file exists
            if (!$disk->exists($path)) continue;

            File::create([
                'filename' => basename($path),
                'original_name' => basename($path),
                'path' => $path,
                'thumbnail_path' => null,
                'mime_type' => $disk->mimeType($path) ?? 'application/octet-stream',
                'size' => $disk->size($path) ?? 0,
                'purpose' => $purpose,
                'visibility' => $visibility,
                'owner_type' => Property::class,
                'owner_id' => $record->id,
                'uploaded_by' => auth()->id(),
                'order' => ++$maxOrder,
                'is_primary' => !$hasPrimary && $purpose === 'PROPERTY_IMAGE',
            ]);

            $hasPrimary = true;
            $count++;
        }

        if ($count > 0) {
            Notification::make()
                ->title("Đã thêm {$count} file mới")
                ->success()
                ->send();
        }
    }
}
