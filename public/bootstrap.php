<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function view(string $path, array $data = []): void
{
    extract($data);
    $viewPath = __DIR__ . '/../src/Views/' . str_replace('.', '/', $path) . '.php';
    
    if (!file_exists($viewPath)) {
        die("View not found: {$path}");
    }
    
    require $viewPath;
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function error(string $key): ?string
{
    return $_SESSION['errors'][$key] ?? null;
}

function flash(string $key, mixed $value = null): mixed
{
    if ($value === null) {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    
    $_SESSION['flash'][$key] = $value;
    return null;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}
