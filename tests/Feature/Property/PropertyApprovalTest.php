<?php

declare(strict_types=1);

namespace Tests\Feature\Property;

use App\Enums\ApprovalStatus;
use App\Models\AuditLog;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PropertyApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Necessary permissions for AdminPropertyController mainly
        Permission::firstOrCreate(['name' => 'view_any_property']);
        Permission::firstOrCreate(['name' => 'approve_property']);

        $adminRole = Role::findByName('SUPER_ADMIN');
        $adminRole->givePermissionTo(['view_any_property', 'approve_property']);
    }

    #[Test]
    public function property_defaults_to_pending()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        // Give permission to create property if Policy requires it
        Permission::firstOrCreate(['name' => 'create_property']);
        $user->givePermissionTo('create_property');

        // Create area and category first
        $area = \App\Models\Area::factory()->create();
        $category = \App\Models\Category::factory()->create();

        $response = $this->actingAsApi($user)->postJson('/api/v1/properties', [
            'title' => 'Test Property',
            'description' => 'Desc',
            'price' => 1000000000,
            'area' => 100,
            'address' => 'Hanoi',
            'owner_name' => 'Mr A',
            'owner_phone' => '0909090909',
            'area_id' => $area->id,
            'category_id' => $category->id,
            // 'approval_status' => 'APPROVED' // Should be ignored
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('properties', [
            'title' => 'Test Property',
            'approval_status' => 'PENDING',
        ]);
    }

    #[Test]
    public function admin_can_approve_property()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $property = Property::factory()->create(['approval_status' => 'PENDING']);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/properties/{$property->id}/approve", [
            'note' => 'Looks good',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'approval_status' => 'APPROVED',
            'approved_by' => $admin->id,
            'approval_note' => 'Looks good',
        ]);

        // Check AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'approve_properties', // getTable() is properties
            'target_type' => Property::class,
            'target_id' => $property->id,
        ]);
    }

    #[Test]
    public function admin_can_reject_property()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $property = Property::factory()->create(['approval_status' => 'PENDING']);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/properties/{$property->id}/reject", [
            'reason' => 'Bad content',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'approval_status' => 'REJECTED',
            'approval_note' => 'Bad content',
        ]);

        // Check AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reject_properties',
            'target_type' => Property::class,
            'target_id' => $property->id,
        ]);
    }
}
