<?php

namespace App\Core;

class Response
{
    public static function json(int $code, string $message, mixed $data = null): never
    {
        $payload = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (defined('APP_TESTING') && APP_TESTING) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo $json;
            throw new \JsonResponseException($code, $message, $data);
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo $json;
        exit;
    }

    public static function success(mixed $data = null, string $message = '操作成功'): never
    {
        self::json(0, $message, $data);
    }

    public static function error(string $message, int $code = 1): never
    {
        self::json($code, $message, null);
    }
}
