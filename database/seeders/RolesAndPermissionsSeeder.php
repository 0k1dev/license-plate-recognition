<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $officeAdminRole = Role::firstOrCreate(['name' => 'OFFICE_ADMIN', 'guard_name' => 'web']);
        $fieldStaffRole = Role::firstOrCreate(['name' => 'FIELD_STAFF', 'guard_name' => 'web']);

        // Entities needing permissions
        $entities = [
            'user',
            'role',
            'permission',
            'area',
            'project',
            'category',
            'property',
            'post',
            'owner_phone_request',
            'report',
            'file',
            'audit_log'
        ];

        foreach ($entities as $entity) {
            $permissions = [
                "view_any_{$entity}",
                "view_{$entity}",
                "create_{$entity}",
                "update_{$entity}",
                "delete_{$entity}",
                "delete_any_{$entity}",
                "restore_{$entity}",
                "force_delete_{$entity}",
                "approve_{$entity}", // Added approve permission
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        // Grant ALL permissions to SUPER_ADMIN
        $superAdminRole->givePermissionTo(Permission::all());

        // Grant specific permissions to OFFICE_ADMIN
        // Office Admin manages almost everything except sensitive system settings (Roles/Permissions)
        $officeAdminPermissions = Permission::where('name', 'not like', '%role%')
            ->where('name', 'not like', '%permission%')
            ->where('name', 'not like', '%audit_log%')
            ->get();
        $officeAdminRole->givePermissionTo($officeAdminPermissions);

        // Grant specific permissions to FIELD_STAFF
        // Field Staff can View properties/posts (scoped), Create properties/posts/requests
        $fieldStaffPermissions = [
            'view_any_property',
            'view_property',
            'create_property',
            'update_property',
            'delete_property',
            'view_any_post',
            'view_post',
            'create_post',
            'update_post',
            'delete_post',
            'view_any_owner_phone_request',
            'create_owner_phone_request', // View own requests
            'view_any_project',
            'view_project', // Reference data
            'view_any_category',
            'view_category', // Reference data
            'view_any_area',
            'view_area', // Reference data
            'view_any_file',
            'create_file', // Uploads
        ];

        $fieldStaffRole->givePermissionTo($fieldStaffPermissions);
    }
}
