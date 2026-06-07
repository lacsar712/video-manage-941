<?php

namespace App\Helpers;

use App\Core\Database;
use App\Core\Response;

class TokenHelper
{
    public static function validate(): array
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            Response::error('未登录或登录已过期', 401);
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT at.*, au.username
                FROM admin_token at
                JOIN admin_user au ON at.admin_id = au.id
                WHERE at.token = ? AND at.expire_at > NOW()
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                Response::error('登录已过期，请重新登录', 401);
            }

            return $tokenData;
        } catch (\Exception $e) {
            Response::error('验证失败：' . $e->getMessage());
        }
    }
}
