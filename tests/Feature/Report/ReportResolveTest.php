<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReportResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $r) {
            Role::firstOrCreate(['name' => $r]);
        }
    }

    #[Test]
    public function users_can_report_posts()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAsApi($user)->postJson('/api/v1/reports', [
            'post_id' => $post->id,
            'reportable_type' => get_class($post),
            'reportable_id' => $post->id,
            'type' => 'SPAM',
            'content' => 'Spam content',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reports', [
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'reporter_id' => $user->id,
            'content' => 'Spam content',
            'status' => 'OPEN',
        ]);
    }

    #[Test]
    public function admin_can_resolve_report_by_hiding_post()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $post = Post::factory()->create(['status' => 'VISIBLE']);
        $report = Report::factory()->create([
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
            'action' => 'HIDE_POST',
            'admin_note' => 'Hidden due to spam',
        ]);

        $response->assertOk();

        // Check report resolved
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'RESOLVED',
            'resolved_by' => $admin->id,
        ]);

        // Check post hidden
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'HIDDEN',
        ]);
    }

    #[Test]
    public function admin_can_resolve_report_by_locking_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $targetUser = User::factory()->create(['is_locked' => false]);
        $post = Post::factory()->create(['created_by' => $targetUser->id]);

        $report = Report::factory()->create([
            'reportable_id' => $targetUser->id,
            'reportable_type' => User::class,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
            'action' => 'LOCK_USER',
            'admin_note' => 'Locking user for spam',
        ]);

        $response->assertOk();

        // Check user locked
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_locked' => true,
        ]);
    }
}
