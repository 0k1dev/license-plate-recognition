<?php

declare(strict_types=1);
namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Update Role
        $record->update(['name' => $data['name']]);

        // 2. Collect permissions
        $permissions = collect($data)
            ->filter(fn($value, $key) => str_starts_with($key, 'permissions_'))
            ->flatten()
            ->unique()
            ->map(fn($id) => (int) $id) // Cast to int to ensure Spatie treats them as IDs
            ->toArray();

        // 3. Sync
        if (!empty($permissions)) {
            $record->syncPermissions($permissions);
        } else {
            $record->syncPermissions([]); // Clear if none selected
        }

        return $record;
    }
}
