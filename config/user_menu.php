<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Menu Visibility Configuration
    |--------------------------------------------------------------------------
    |
    | Define which resources should be hidden from the admin sidebar.
    | Uncomment the lines corresponding to the resources you want to hide.
    |
    */

    'hidden_resources' => [
        // Danh mục
        // \App\Filament\Resources\ProvinceResource::class,
        // \App\Filament\Resources\SubdivisionResource::class,
        // \App\Filament\Resources\ProjectResource::class,
        // \App\Filament\Resources\CategoryResource::class,

        // Quản lý BĐS
        \App\Filament\Resources\PropertyResource::class,
        \App\Filament\Resources\PostResource::class,

        // Hệ thống
        // \App\Filament\Resources\UserResource::class,
        \App\Filament\Resources\AuditLogResource::class,
        \App\Filament\Resources\RoleResource::class,
        \App\Filament\Resources\FileResource::class,
        \App\Filament\Resources\PermissionResource::class,
        // \App\Filament\Resources\GeneralSettings::class, // Note: Settings might be a Page, handled differently if needed

        // Kiểm duyệt
        \App\Filament\Resources\OwnerPhoneRequestResource::class,
        \App\Filament\Resources\ReportResource::class,
    ],
];
