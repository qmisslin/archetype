<?php

namespace Archetype\Core;

class Router
{
    private array $body = [];
    private string $method;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->parseBody();
    }

    private function parseBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $this->body = json_decode($input, true) ?? [];
        } else {
            $this->body = $_POST;
        }
        $this->body = array_merge($_GET, $this->body);
    }

    public function getBody(): array { return $this->body; }

    // Helper to validate the HTTP method
    public function enforceMethod(string $expected): void {
        if ($this->method !== strtoupper($expected)) {
            APIHelper::error("Method not allowed. Expected $expected", 405);
        }
    }
}
