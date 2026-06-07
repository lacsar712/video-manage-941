<?php

namespace App\Helpers;

use App\Core\Database;

class FormatHelper
{
    public static function formatDateTime(mixed $datetime): string
    {
        if (empty($datetime)) {
            return '';
        }
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
