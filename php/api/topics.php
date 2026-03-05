<?php
require_once __DIR__ . '/_bootstrap.php';

$pdo = db();
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

if ($subjectId > 0) {
    $stmt = $pdo->prepare('SELECT id, subject_id, name FROM topics WHERE subject_id=:subject_id ORDER BY name');
    $stmt->execute([':subject_id' => $subjectId]);
    respond(['topics' => $stmt->fetchAll()]);
}

$rows = $pdo->query('SELECT id, subject_id, name FROM topics ORDER BY name')->fetchAll();
respond(['topics' => $rows]);
