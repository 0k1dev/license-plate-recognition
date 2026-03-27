<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Models\File;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SUPER_ADMIN', 'OFFICE_ADMIN', 'FIELD_STAFF'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    #[Test]
    public function users_can_report_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAsApi($user)->postJson('/api/v1/reports', [
            'post_id' => $post->id,
            'type' => 'SPAM',
            'content' => 'Spam content',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('reports', [
            'post_id' => $post->id,
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'reporter_id' => $user->id,
            'content' => 'Spam content',
            'status' => 'OPEN',
        ]);
    }

    #[Test]
    public function users_can_report_posts_with_evidence_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAsApi($user)->post('/api/v1/reports', [
            'post_id' => $post->id,
            'type' => 'FRAUD_SCAM',
            'content' => 'Co dau hieu lua dao',
            'files' => [
                UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertCreated();

        $report = Report::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('files', [
            'owner_type' => Report::class,
            'owner_id' => $report->id,
            'purpose' => 'REPORT_EVIDENCE',
            'uploaded_by' => $user->id,
            'visibility' => 'PUBLIC',
        ]);

        $this->assertSame(1, File::where('owner_type', Report::class)->where('owner_id', $report->id)->count());
    }

    #[Test]
    public function admin_can_resolve_report_by_hiding_post(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $post = Post::factory()->create(['status' => 'VISIBLE']);
        $report = Report::factory()->create([
            'post_id' => $post->id,
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
            'action' => 'HIDE_POST',
            'admin_note' => 'Hidden due to spam',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'RESOLVED',
            'resolved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'HIDDEN',
        ]);
    }

    #[Test]
    public function admin_can_resolve_report_by_locking_post_creator(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        $targetUser = User::factory()->create(['is_locked' => false]);
        $post = Post::factory()->create(['created_by' => $targetUser->id]);

        $report = Report::factory()->create([
            'post_id' => $post->id,
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAsApi($admin)->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
            'action' => 'LOCK_USER',
            'admin_note' => 'Locking user for spam',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_locked' => true,
        ]);
    }
}
