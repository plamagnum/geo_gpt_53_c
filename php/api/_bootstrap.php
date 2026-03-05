<?php
// Спільна ініціалізація API: сесія, заголовки, допоміжні функції.
require_once __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth(): array
{
    if (!isset($_SESSION['user'])) {
        respond(['error' => 'Потрібна авторизація'], 401);
    }
    return $_SESSION['user'];
}

function requireAdmin(): array
{
    $user = requireAuth();
    if (($user['role'] ?? 'user') !== 'admin') {
        respond(['error' => 'Права адміністратора обовʼязкові'], 403);
    }
    return $user;
}
