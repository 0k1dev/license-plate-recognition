<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Ghi log hành động quan trọng
     *
     * @param string $action Hành động (ví dụ: approve_property)
     * @param string $targetType Loại đối tượng (ví dụ: App\Models\Property)
     * @param int $targetId ID đối tượng
     * @param array|null $payload Dữ liệu bổ sung (ví dụ: lý do reject)
     */
    public function log(
        string $action,
        string $targetType,
        int $targetId,
        ?array $payload = null
    ): void {
        AuditLog::log($action, $targetType, $targetId, $payload);
    }
}
