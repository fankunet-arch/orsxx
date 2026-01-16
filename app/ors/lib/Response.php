<?php
namespace ORS;

/**
 * HTTP Response Helper
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success JSON response
     */
    public static function success(array $data = [], string $message = 'Success'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send error JSON response
     */
    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        self::json($response, $status);
    }

    /**
     * Send validation error response
     */
    public static function validationError(array $errors): void
    {
        self::json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}
