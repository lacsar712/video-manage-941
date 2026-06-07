<?php

use App\Core\Response;
use App\Core\Validator;
use App\Helpers\FormatHelper;
use App\Helpers\TokenHelper;
use App\Helpers\LogHelper;

if (!class_exists('JsonResponseException', false)) {
    class JsonResponseException extends Exception
    {
        public $responseCode;
        public $responseMessage;
        public $responseData;

        public function __construct($code, $message, $data = null)
        {
            parent::__construct($message, $code);
            $this->responseCode = $code;
            $this->responseMessage = $message;
            $this->responseData = $data;
        }

        public function toArray()
        {
            return [
                'code' => $this->responseCode,
                'message' => $this->responseMessage,
                'data' => $this->responseData
            ];
        }

        public function toJson()
        {
            return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
        }
    }
}

function jsonResponse($code, $message, $data = null)
{
    Response::json($code, $message, $data);
}

function success($data = null, $message = '操作成功')
{
    Response::success($data, $message);
}

function error($message, $code = 1)
{
    Response::error($message, $code);
}

function validateRequired($fields, $data)
{
    try {
        Validator::required($fields, $data);
    } catch (\App\Core\ApiException $e) {
        Response::error($e->getMessage(), $e->getApiCode());
    }
}

function validateLength($value, $min, $max, $label)
{
    try {
        Validator::length($value, $min, $max, $label);
    } catch (\App\Core\ApiException $e) {
        Response::error($e->getMessage(), $e->getApiCode());
    }
}

function validateUrl($url, $label)
{
    try {
        Validator::url($url, $label);
    } catch (\App\Core\ApiException $e) {
        Response::error($e->getMessage(), $e->getApiCode());
    }
}

function validateInt($value, $label)
{
    try {
        Validator::int($value, $label);
    } catch (\App\Core\ApiException $e) {
        Response::error($e->getMessage(), $e->getApiCode());
    }
}

function sanitizeInput($input)
{
    return Validator::sanitizeInput($input);
}

function sanitizeOutput($output)
{
    return Validator::sanitizeOutput($output);
}

function formatDateTime($datetime)
{
    return FormatHelper::formatDateTime($datetime);
}

function generateToken()
{
    return FormatHelper::generateToken();
}

function validateToken()
{
    return TokenHelper::validate();
}

function writeOperationLog($adminId, $module, $action, $targetType = null, $targetId = null, $content = null, $status = 'success', $errorMessage = null)
{
    return LogHelper::writeOperationLog($adminId, $module, $action, $targetType, $targetId, $content, $status, $errorMessage);
}
