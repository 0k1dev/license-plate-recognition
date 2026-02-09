<?php

declare(strict_types=1);
namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Create Role
        $role = static::getModel()::create(['name' => $data['name'], 'guard_name' => 'web']);

        // 2. Collect permissions
        $permissions = collect($data)
            ->filter(fn($value, $key) => str_starts_with($key, 'permissions_'))
            ->flatten()
            ->unique()
            ->map(fn($id) => (int) $id) // Cast to int to ensure Spatie treats them as IDs
            ->toArray();

        // 3. Sync
        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role;
    }
}
