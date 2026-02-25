<?php

declare(strict_types=1);

namespace Tests\Feature\Post;

use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function user_can_create_post_for_their_property()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        $property = Property::factory()->create([
            'created_by' => $user->id,
            'approval_status' => 'APPROVED',
        ]);

        $response = $this->actingAsApi($user)->postJson('/api/v1/posts', [
            'property_id' => $property->id,
            'visible_until' => now()->addDays(30)->toIso8601String(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('posts', [
            'property_id' => $property->id,
            'status' => 'PENDING',
        ]);
    }

    #[Test]
    public function cannot_create_duplicate_active_post()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        $property = Property::factory()->create([
            'created_by' => $user->id,
            'approval_status' => 'APPROVED',
        ]);

        // Create first post
        Post::factory()->create([
            'property_id' => $property->id,
            'status' => 'VISIBLE',
            'visible_until' => now()->addDays(10),
        ]);

        // Try create second post
        $response = $this->actingAsApi($user)->postJson('/api/v1/posts', [
            'property_id' => $property->id,
            'title' => 'Duplicate',
            'visible_until' => now()->addDays(30)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);
    }

    #[Test]
    public function user_can_hide_their_post()
    {
        $user = User::factory()->create();
        $user->assignRole('FIELD_STAFF');
        $property = Property::factory()->create([
            'created_by' => $user->id,
            'approval_status' => 'APPROVED',
        ]);
        $post = Post::factory()->create([
            'property_id' => $property->id,
            'created_by' => $user->id,
            'status' => 'VISIBLE',
        ]);

        $response = $this->actingAsApi($user)->postJson("/api/v1/posts/{$post->id}/hide");

        $response->assertOk();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'HIDDEN',
        ]);
    }

    #[Test]
    public function expired_post_automator_works()
    {
        // This tests the command logic indirectly or we can call the service
        $post = Post::factory()->create([
            'status' => 'VISIBLE',
            'visible_until' => now()->subDay(), // Expired yesterday
        ]);

        $this->artisan('posts:expire')
            ->assertSuccessful();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'EXPIRED',
        ]);
    }
}
