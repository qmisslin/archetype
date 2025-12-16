<?php

namespace Archetype\Core;

use Throwable;

class Router
{
    private string $method;
    private string $path;
    private array $body = [];
    private array $query = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Clean the URL to keep only the path (e.g. /api/users/login)
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $this->query = $_GET;
        $this->parseBody();
    }

    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $this->body = json_decode($input, true) ?? [];
        } elseif (str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $this->body = $_POST;
        }

        $this->body = array_merge($this->query, $this->body);
    }

    /**
     * Attempts to locate and execute the API file matching the URL.
     */
    public function dispatch(): void
    {
        // 1. Clean the path
        // Remove the leading slash to get a relative path (e.g. "api/users/login")
        $relativePath = ltrim($this->path, '/');

        // If empty, this is the root
        if (empty($relativePath)) {
            APIHelper::success(['message' => 'Archetype API Root']);
            return;
        }

        // 2. Build the path to the PHP file
        // Example: URL "/api/users/login" -> File "api/users/login.php"
        $targetFile = $relativePath . '.php';

        // 3. Execute
        if (file_exists($targetFile)) {
            try {
                // Include the file. It will execute within the scope of this function.
                // The called file can use $this->getBody() if needed,
                // or instantiate its own classes.
                require $targetFile;
            } catch (Throwable $e) {
                // Error inside the endpoint code
                Logs::error('system', 'Endpoint execution failed', $e);
                throw $e; // Rethrow so the global handler in index.php can catch it
            }
        } else {
            APIHelper::error("Endpoint not found: /" . $relativePath, 404);
        }
    }

    // Useful getters for API files
    public function getBody(): array { return $this->body; }
    public function getMethod(): string { return $this->method; }
}
