<?php

namespace App\Core;

class Validator
{
    public static function required(array $fields, array $data): void
    {
        foreach ($fields as $field => $label) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                throw new ApiException("{$label}不能为空");
            }
        }
    }

    public static function length(string $value, int $min, int $max, string $label): void
    {
        $len = mb_strlen($value, 'UTF-8');
        if ($len < $min || $len > $max) {
            throw new ApiException("{$label}长度必须在{$min}-{$max}个字符之间");
        }
    }

    public static function url(string $url, string $label): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ApiException("{$label}格式不正确");
        }
    }

    public static function int(mixed $value, string $label): void
    {
        if (!is_numeric($value) || intval($value) != $value) {
            throw new ApiException("{$label}必须是整数");
        }
    }

    public static function sanitizeInput(mixed $input): mixed
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeOutput(mixed $output): mixed
    {
        if (is_array($output)) {
            return array_map([self::class, 'sanitizeOutput'], $output);
        }
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
}
