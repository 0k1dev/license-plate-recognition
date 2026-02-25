<?php

declare(strict_types=1);

namespace Tests\Feature\Property;

use App\Models\AuditLog;
use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PropertyMaskingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
        }

        // Create permissions
        $permissions = ['view_property', 'view_any_property'];
        foreach ($permissions as $p) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p]);
        }

        // Give basic permission to FIELD_STAFF
        $fieldStaffRole = Role::findByName('FIELD_STAFF');
        $fieldStaffRole->givePermissionTo('view_property');

        // Give all to ADMIN
        $adminRole = Role::findByName('SUPER_ADMIN');
        $adminRole->givePermissionTo($permissions);
    }

    #[Test]
    public function field_staff_sees_masked_phone_by_default()
    {
        $fieldStaff = User::factory()->create();
        $fieldStaff->assignRole('FIELD_STAFF');

        $property = Property::factory()->create([
            'owner_phone' => '0912345678',
            'created_by' => User::factory()->create()->id,
            'approval_status' => 'APPROVED', // Field staff can only see APPROVED usually
        ]);

        // Field staff needs to be in area to view? 
        // Policy: view if (creator) OR (in area AND approved).
        // Let's assign area to field staff
        $fieldStaff->area_ids = [$property->area_id];
        $fieldStaff->save();

        $response = $this->actingAsApi($fieldStaff)->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk();
        $this->assertStringContainsString('****', $response->json('data.owner_phone'));
        $this->assertStringNotContainsString('0912345678', $response->json('data.owner_phone'));
    }

    #[Test]
    public function creator_sees_real_phone()
    {
        $creator = User::factory()->create();
        $creator->assignRole('FIELD_STAFF');

        $property = Property::factory()->create([
            'owner_phone' => '0912345678',
            'created_by' => $creator->id,
            'approval_status' => 'APPROVED',
        ]);

        $response = $this->actingAsApi($creator)->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk();
        $response->assertJsonPath('data.owner_phone', '0912345678');
    }

    #[Test]
    public function admin_sees_real_phone()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $property = Property::factory()->create([
            'owner_phone' => '0912345678',
            'approval_status' => 'PENDING',
        ]);

        $response = $this->actingAsApi($admin)->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk();
        $response->assertJsonPath('data.owner_phone', '0912345678');
    }

    #[Test]
    public function field_staff_sees_real_phone_after_approved_request()
    {
        $fieldStaff = User::factory()->create();
        $fieldStaff->assignRole('FIELD_STAFF');

        $property = Property::factory()->create([
            'owner_phone' => '0912345678',
            'approval_status' => 'APPROVED',
        ]);

        $fieldStaff->area_ids = [$property->area_id];
        $fieldStaff->save();

        // Create approved request
        OwnerPhoneRequest::create([
            'property_id' => $property->id,
            'requester_id' => $fieldStaff->id,
            'status' => 'APPROVED',
            'purpose' => 'Contact owner',
            'created_at' => now(), // Add timestamp manually if factory missing
        ]);

        $response = $this->actingAsApi($fieldStaff)->getJson("/api/v1/properties/{$property->id}");

        $response->assertOk();
        $response->assertJsonPath('data.owner_phone', '0912345678');
    }
}
