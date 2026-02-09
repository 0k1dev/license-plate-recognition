<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(
        protected PostService $postService
    ) {}

    public function create(User $reporter, array $data): Report
    {
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'type' => $data['type'],
            'content' => $data['content'],
            'status' => 'NEW',
        ]);

        AuditLog::log('create_report', Report::class, $report->id);

        return $report;
    }

    public function resolve(
        Report $report,
        User $admin,
        string $action,
        ?string $note = null
    ): void {
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
        $report->loadMissing('reportable');

        $allowed = match (true) {
            $report->reportable instanceof User => ['LOCK_USER', 'WARN', 'NO_ACTION'],
            $report->reportable instanceof \App\Models\Post => ['HIDE_POST', 'WARN', 'NO_ACTION'],
            $report->reportable instanceof \App\Models\Property => ['HIDE_POST', 'WARN', 'NO_ACTION'],
            default => ['NO_ACTION'],
        };

        if (! in_array($action, $allowed, true)) {
            throw ValidationException::withMessages([
                'action' => ['Action is not allowed for this report type.'],
            ]);
        }
    }

    private function lockUser(Report $report): void
    {
        $user = $report->reportable;
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
        $post = $report->reportable;
        if ($post instanceof \App\Models\Post) {
            $this->postService->hide($post, $reason ?? 'Reported');
        } elseif ($report->reportable instanceof \App\Models\Property) {
            // If reported object is Property, find active posts and hide?
            // Or just hide property? Property doesn't have HIDE, only Post.
            // If property is rejected, maybe? But 'HIDE_POST' action suggests hiding the post.
            foreach ($report->reportable->posts as $post) {
                $this->postService->hide($post, $reason ?? 'Property Reported');
            }
        }
    }

    private function warnUser(Report $report): void
    {
        $user = $report->reportable;
        if ($user instanceof User) {
            AuditLog::log('warn_user', User::class, $user->id, [
                'report_id' => $report->id,
            ]);
        }
    }
}
