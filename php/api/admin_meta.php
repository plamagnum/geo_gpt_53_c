<?php
require_once __DIR__ . '/_bootstrap.php';

requireAdmin();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$entity = (string)($_GET['entity'] ?? '');

$allowed = ['classes', 'subjects', 'topics'];
if (!in_array($entity, $allowed, true)) {
    respond(['error' => 'Невідома сутність'], 422);
}

if ($method === 'GET') {
    $rows = $pdo->query("SELECT * FROM {$entity} ORDER BY id DESC")->fetchAll();
    respond([$entity => $rows]);
}

if ($method === 'POST') {
    $input = jsonInput();
    if ($entity === 'classes') {
        $name = trim((string)($input['name'] ?? ''));
        $stmt = $pdo->prepare('INSERT INTO classes (name) VALUES (:name)');
        $stmt->execute([':name' => $name]);
    } elseif ($entity === 'subjects') {
        $name = trim((string)($input['name'] ?? ''));
        $classId = isset($input['class_id']) ? (int)$input['class_id'] : null;
        $stmt = $pdo->prepare('INSERT INTO subjects (class_id, name) VALUES (:class_id, :name)');
        $stmt->execute([':class_id' => $classId, ':name' => $name]);
    } else {
        $name = trim((string)($input['name'] ?? ''));
        $subjectId = (int)($input['subject_id'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO topics (subject_id, name) VALUES (:subject_id, :name)');
        $stmt->execute([':subject_id' => $subjectId, ':name' => $name]);
    }
    respond(['id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM {$entity} WHERE id=:id");
    $stmt->execute([':id' => $id]);
    respond(['deleted' => $stmt->rowCount() > 0]);
}

respond(['error' => 'Метод не підтримується'], 405);
