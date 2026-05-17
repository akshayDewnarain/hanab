<?php

namespace Domain\Support\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function default(mixed $data, int $code = 200): JsonResponse
    {
        return response()
            ->json($data, $code);
    }

    public static function success(string $message = 'Success', mixed $data = null, int $code = 200): JsonResponse
    {
        return static::default([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function created(string $message = 'Resource created successfully', mixed $data = null, int $code = 201): JsonResponse
    {
        return static::default([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function updated(string $message = 'Resource updated successfully', mixed $data = null, int $code = 200): JsonResponse
    {
        return static::default([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function deleted(string $message = 'Resource deleted successfully', int $code = 200): JsonResponse
    {
        return static::default([
            'success' => true,
            'message' => $message,
        ], $code);
    }

    public static function error(?string $message = null, mixed $errors = null, int $code = 422): JsonResponse
    {
        return static::default([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function unauthorized(int $code = 401): JsonResponse
    {
        return static::default([
            'success' => false,
            'message' => 'Unauthorized',
        ], $code);
    }

    public static function forbidden(int $code = 403): JsonResponse
    {
        return static::default([
            'success' => false,
            'message' => 'Forbidden',
        ], $code);
    }

    public static function validationFailed(array $errors, string $message = 'Validation failed', int $code = 422): JsonResponse
    {
        return static::default([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
