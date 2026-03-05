<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Метод не підтримується'], 405);
}

$input = jsonInput();
$name = trim((string)($input['name'] ?? ''));
$password = (string)($input['password'] ?? '');

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name, password_hash, role, class_id FROM users WHERE name = :name LIMIT 1');
$stmt->execute([':name' => $name]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond(['error' => 'Невірні облікові дані'], 401);
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'role' => $user['role'],
    'class_id' => $user['class_id'] !== null ? (int)$user['class_id'] : null,
];

respond(['user' => $_SESSION['user']]);
