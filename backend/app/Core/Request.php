<?php

namespace App\Core;

class Request
{
    private string $path;
    private string $method;

    public function __construct()
    {
        $this->path = $this->resolvePath();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    private function resolvePath(): string
    {
        $path = $_GET['path'] ?? '';
        if (empty($path) && isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }
            if (preg_match('#^/api/(.+)$#', $requestUri, $matches)) {
                $path = $matches[1];
            }
        }
        return $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPathParts(): array
    {
        return explode('/', $this->path);
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }
}
