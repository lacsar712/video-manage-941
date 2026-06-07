<?php

namespace App\Core;

use Exception;

class ApiException extends Exception
{
    protected int $apiCode;
    protected ?array $apiData;

    public function __construct(string $message, int $code = 1, ?array $data = null)
    {
        parent::__construct($message, $code);
        $this->apiCode = $code;
        $this->apiData = $data;
    }

    public function getApiCode(): int
    {
        return $this->apiCode;
    }

    public function getApiData(): ?array
    {
        return $this->apiData;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->apiCode,
            'message' => $this->getMessage(),
            'data' => $this->apiData
        ];
    }
}
