<?php

namespace Archetype\Core;

/**
 * APIHelper class
 * Provides static methods for standardized API output (JSON responses, errors).
 */
class APIHelper
{
    // Common HTTP Status Codes
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Sends a successful JSON response and terminates execution.
     *
     * @param mixed $data The data payload to send.
     * @param int $statusCode The HTTP status code (default: 200).
     */
    public static function success(mixed $data = [], int $statusCode = self::HTTP_OK): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit();
    }

    /**
     * Sends an error JSON response and terminates execution.
     *
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code (default: 400).
     * @param string|null $errorCode An optional specific error code (e.g., 'E_AUTH_001').
     */
    public static function error(string $message, int $statusCode = self::HTTP_BAD_REQUEST, ?string $errorCode = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'code' => $errorCode
        ]);
        exit();
    }
}