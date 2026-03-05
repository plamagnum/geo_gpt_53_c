<?php
require_once __DIR__ . '/_bootstrap.php';

$user = requireAuth();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($user['role'] === 'admin') {
        $rows = $pdo->query('SELECT t.id, t.user_id, u.name AS user_name, t.title, t.description, t.status, t.created_at FROM tasks t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC')->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT id, user_id, title, description, status, created_at FROM tasks WHERE user_id=:user_id ORDER BY id DESC');
        $stmt->execute([':user_id' => $user['id']]);
        $rows = $stmt->fetchAll();
    }
    respond(['tasks' => $rows]);
}

if ($method === 'POST') {
    $input = jsonInput();
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    if ($title === '') {
        respond(['error' => 'Заголовок обов\'язковий'], 422);
    }
    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, description, status) VALUES (:user_id, :title, :description, :status)');
    $stmt->execute([
        ':user_id' => $user['id'],
        ':title' => $title,
        ':description' => $description,
        ':status' => 'todo',
    ]);
    respond(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $input = jsonInput();
    $id = (int)($input['id'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $status = (($input['status'] ?? 'todo') === 'done') ? 'done' : 'todo';

    $query = $user['role'] === 'admin'
        ? 'UPDATE tasks SET title=:title, description=:description, status=:status WHERE id=:id'
        : 'UPDATE tasks SET title=:title, description=:description, status=:status WHERE id=:id AND user_id=:user_id';

    $params = [':id' => $id, ':title' => $title, ':description' => $description, ':status' => $status];
    if ($user['role'] !== 'admin') {
        $params[':user_id'] = $user['id'];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    respond(['updated' => $stmt->rowCount() > 0]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        respond(['error' => 'id обов\'язковий'], 422);
    }

    $query = $user['role'] === 'admin'
        ? 'DELETE FROM tasks WHERE id=:id'
        : 'DELETE FROM tasks WHERE id=:id AND user_id=:user_id';
    $params = [':id' => $id];
    if ($user['role'] !== 'admin') {
        $params[':user_id'] = $user['id'];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    respond(['deleted' => $stmt->rowCount() > 0]);
}

respond(['error' => 'Метод не підтримується'], 405);
