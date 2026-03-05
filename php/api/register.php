<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Метод не підтримується'], 405);
}

$input = jsonInput();
$name = trim((string)($input['name'] ?? ''));
$password = (string)($input['password'] ?? '');
$classId = isset($input['class_id']) ? (int)$input['class_id'] : null;

if ($name === '' || $password === '') {
    respond(['error' => 'Ім\'я та пароль обов\'язкові'], 422);
}

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO users (name, password_hash, class_id, role) VALUES (:name, :password_hash, :class_id, :role)');

try {
    $stmt->execute([
        ':name' => $name,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':class_id' => $classId,
        ':role' => 'user',
    ]);
} catch (Throwable $e) {
    respond(['error' => 'Не вдалося зареєструвати користувача'], 400);
}

$userId = (int)$pdo->lastInsertId();
$_SESSION['user'] = [
    'id' => $userId,
    'name' => $name,
    'role' => 'user',
    'class_id' => $classId,
];

respond(['user' => $_SESSION['user']], 201);
