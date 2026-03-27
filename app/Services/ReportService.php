<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ReportService
{
    public function __construct(
        protected PostService $postService,
        protected FileService $fileService,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $evidenceFiles
     */
    public function create(User $reporter, array $data, array $evidenceFiles = []): Report
    {
        $post = Post::query()
            ->with(['property', 'creator'])
            ->findOrFail($data['post_id']);

        $report = Report::create([
            'post_id' => $post->id,
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'type' => $data['type'],
            'content' => $data['content'],
            'status' => 'OPEN',
        ]);

        if ($evidenceFiles !== []) {
            $this->fileService->uploadMultiple(
                $evidenceFiles,
                $reporter,
                'REPORT_EVIDENCE',
                Report::class,
                (int) $report->id,
                'PUBLIC',
            );
        }

        AuditLog::log('create_report', Report::class, $report->id);

        return $report;
    }

    public function markInProgress(Report $report, User $admin, ?string $note = null): void
    {
        if ($report->status !== 'OPEN') {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể tiếp nhận báo cáo đang Mở.'],
            ]);
        }

        $report->update([
            'status' => 'IN_PROGRESS',
            'admin_note' => $note,
            'resolved_by' => $admin->id,
        ]);

        AuditLog::log('in_progress_report', Report::class, $report->id);
    }

    public function resolve(
        Report $report,
        User $admin,
        string $action,
        ?string $note = null
    ): void {
        if (!in_array($report->status, ['OPEN', 'IN_PROGRESS'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Chỉ có thể xử lý báo cáo đang Mở hoặc Đang xử lý.'],
            ]);
        }

        $this->assertActionAllowed($report, $action);

        DB::transaction(function () use ($report, $admin, $action, $note): void {
            $report->update([
                'status' => 'RESOLVED',
                'action' => $action,
                'admin_note' => $note,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            // Execute action
            match ($action) {
                'HIDE_POST' => $this->hidePost($report, $note),
                'LOCK_USER' => $this->lockUser($report),
                'WARN' => $this->warnUser($report),
                default => null,
            };

            AuditLog::log('resolve_report', Report::class, $report->id, [
                'action' => $action,
            ]);
        });
    }

    private function assertActionAllowed(Report $report, string $action): void
    {
        $report->loadMissing('post.creator');

        $allowed = $report->post
            ? ['HIDE_POST', 'LOCK_USER', 'WARN', 'NO_ACTION']
            : ['NO_ACTION'];

        if (! in_array($action, $allowed, true)) {
            throw ValidationException::withMessages([
                'action' => ['Action is not allowed for this report type.'],
            ]);
        }
    }

    private function lockUser(Report $report): void
    {
        $user = $report->post?->creator;
        if ($user instanceof User) {
            $user->update([
                'is_locked' => true,
                'locked_at' => now(),
                'lock_reason' => $report->admin_note,
            ]);

            AuditLog::log('lock_user', User::class, $user->id);

            // Should invalid token? API tokens revoke.
            $user->tokens()->delete();
        }
    }

    private function hidePost(Report $report, ?string $reason): void
    {
        $post = $report->post;
        if ($post instanceof Post) {
            $this->postService->hide($post, $reason ?? 'Reported');
        }
    }

    private function warnUser(Report $report): void
    {
        $user = $report->post?->creator;
        if ($user instanceof User) {
            AuditLog::log('warn_user', User::class, $user->id, [
                'report_id' => $report->id,
            ]);
        }
    }
}
