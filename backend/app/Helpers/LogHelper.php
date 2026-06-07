<?php

namespace App\Helpers;

use App\Core\Database;

class LogHelper
{
    public static function writeOperationLog(
        ?int $adminId,
        string $module,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $content = null,
        string $status = 'success',
        ?string $errorMessage = null
    ): bool {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO operation_log (admin_id, module, action, target_type, target_id, content, status, error_message, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$adminId, $module, $action, $targetType, $targetId, $content, $status, $errorMessage]);
            return true;
        } catch (\Exception $e) {
            error_log('写入操作日志失败: ' . $e->getMessage());
            return false;
        }
    }
}
