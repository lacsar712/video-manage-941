<?php

namespace App\Core;

abstract class Controller
{
    protected function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    protected function getInputParam(string $key, mixed $default = ''): mixed
    {
        $jsonInput = $this->getJsonInput();
        if (isset($jsonInput[$key])) {
            return $jsonInput[$key];
        }
        return $_POST[$key] ?? $default;
    }

    protected function getQueryParam(string $key, mixed $default = ''): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function success(mixed $data = null, string $message = '操作成功'): never
    {
        Response::success($data, $message);
    }

    protected function error(string $message, int $code = 1): never
    {
        Response::error($message, $code);
    }
}
