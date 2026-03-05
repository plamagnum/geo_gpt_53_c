<?php
require_once __DIR__ . '/_bootstrap.php';

$pdo = db();
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

if ($classId) {
    $stmt = $pdo->prepare('SELECT id, class_id, name FROM subjects WHERE class_id=:class_id OR class_id IS NULL ORDER BY name');
    $stmt->execute([':class_id' => $classId]);
    respond(['subjects' => $stmt->fetchAll()]);
}

$rows = $pdo->query('SELECT id, class_id, name FROM subjects ORDER BY name')->fetchAll();
respond(['subjects' => $rows]);
