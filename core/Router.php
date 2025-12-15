<?php

namespace Archetype\Core;

/**
 * Router class
 * Handles request routing, method checking, and body parsing.
 */
class Router
{
    private string $method;
    private string $path;
    private array $body = [];
    private array $query = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/');
        $this->query = $_GET;
        $this->parseBody();
    }

    /**
     * Parses the request body based on Content-Type.
     */
    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $this->body = json_decode($input, true) ?? [];
        } elseif (str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded')) {
            // Includes standard POST data
            $this->body = $_POST;
            // Handle files separately if needed (via $_FILES)
            // For now, we only focus on data body parsing
        }

        // Merge query params and body for easy access to all input data
        $this->body = array_merge($this->query, $this->body);
    }

    /**
     * Dispatches the request to the correct handler function or file.
     *
     * @param string $basePath The directory where the routes are located (e.g., 'api').
     */
    public function dispatch(string $basePath): void
    {
        // 1. Filter out non-relevant paths (e.g., /index.php or /admin.php)
        if (!str_starts_with($this->path, $basePath)) {
            // This is not an API call, let the main files (index.php/admin.php) handle it.
            return;
        }

        // 2. Extract the relative path within the 'api/' directory
        $routePath = substr($this->path, strlen($basePath) + 1);

        // 3. Resolve the PHP file corresponding to the route (e.g., 'users/login' -> 'api/users/login.php')
        $filePath = $basePath . '/' . $routePath . '.php';

        if (file_exists($filePath)) {
            // Pass the Router instance and all input data to the route file
            // The route file will contain the actual logic and call the CORE classes.
            require $filePath;
        } else {
            APIHelper::error("Route not found: /{$this->path}", APIHelper::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get the parsed request body (merged from query and request body).
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Get the HTTP request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check if the request method matches the expected method.
     * @param string $expectedMethod e.g. 'GET', 'POST', 'PATCH', 'DELETE'
     */
    public function isMethod(string $expectedMethod): bool
    {
        return $this->method === strtoupper($expectedMethod);
    }
}