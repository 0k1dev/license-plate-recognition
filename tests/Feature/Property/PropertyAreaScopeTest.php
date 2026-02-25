<?php

declare(strict_types=1);

namespace Tests\Feature\Property;

use App\Models\Area;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PropertyAreaScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        foreach (['view_property', 'view_any_property'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
    }

    #[Test]
    public function field_staff_only_sees_properties_in_their_area()
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $staff = User::factory()->create();
        $staff->assignRole('FIELD_STAFF');
        $staff->area_ids = [$area1->id];
        $staff->save();
        $staff->givePermissionTo('view_property');

        // Create properties
        $prop1 = Property::factory()->create(['area_id' => $area1->id, 'approval_status' => 'APPROVED']);
        $prop2 = Property::factory()->create(['area_id' => $area2->id, 'approval_status' => 'APPROVED']);

        // List
        $response = $this->actingAsApi($staff)->getJson('/api/v1/properties');

        $response->assertOk();
        $data = $response->json('data');

        // Should contain prop1, not prop2
        $ids = collect($data)->pluck('id');
        $this->assertTrue($ids->contains($prop1->id));
        $this->assertFalse($ids->contains($prop2->id));
    }

    #[Test]
    public function field_staff_without_areas_sees_nothing()
    {
        $area1 = Area::factory()->create();
        $staff = User::factory()->create();
        $staff->assignRole('FIELD_STAFF');
        $staff->area_ids = []; // No areas
        $staff->save();
        $staff->givePermissionTo('view_property');

        Property::factory()->create(['area_id' => $area1->id, 'approval_status' => 'APPROVED']);

        $response = $this->actingAsApi($staff)->getJson('/api/v1/properties');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    #[Test]
    public function admin_sees_all_areas()
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        // Admin usually has view_any_property permission
        Permission::firstOrCreate(['name' => 'view_any_property']);
        $admin->givePermissionTo('view_any_property');

        $prop1 = Property::factory()->create(['area_id' => $area1->id, 'approval_status' => 'APPROVED']);
        $prop2 = Property::factory()->create(['area_id' => $area2->id, 'approval_status' => 'APPROVED']);

        // Admin API endpoint might be different (/api/v1/admin/properties) but PropertyController also has logic.
        // Let's test standard index first, assuming admin isn't filtered there either.
        $response = $this->actingAsApi($admin)->getJson('/api/v1/properties');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($prop1->id));
        $this->assertTrue($ids->contains($prop2->id));
    }
}
