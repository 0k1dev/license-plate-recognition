<?php

declare(strict_types=1);

namespace Tests\Feature\Property;

use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OwnerPhoneRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }
    }

    #[Test]
    public function field_staff_can_request_owner_phone()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');

        $property = Property::factory()->create();

        $response = $this->actingAsApi($user)->postJson("/api/v1/properties/{$property->id}/owner-phone-requests", [
            'property_id' => $property->id,
            'reason' => 'Need to contact owner',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('owner_phone_requests', [
            'property_id' => $property->id,
            'requester_id' => $user->id,
            'status' => 'PENDING',
        ]);
    }

    #[Test]
    public function cannot_create_duplicate_pending_request()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        $property = Property::factory()->create();

        // First request
        OwnerPhoneRequest::factory()->create([
            'requester_id' => $user->id,
            'property_id' => $property->id,
            'status' => 'PENDING',
        ]);

        // Second request
        $response = $this->actingAsApi($user)->postJson("/api/v1/properties/{$property->id}/owner-phone-requests", [
            'reason' => 'Another request',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function admin_can_approve_request()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $request = OwnerPhoneRequest::factory()->create([
            'status' => 'PENDING',
        ]);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/owner-phone-requests/{$request->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('owner_phone_requests', [
            'id' => $request->id,
            'status' => 'APPROVED',
        ]);

        // Check AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'approve_phone_request',
            'target_id' => $request->id,
        ]);
    }
}
