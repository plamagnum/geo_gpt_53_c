<?php
require_once __DIR__ . '/_bootstrap.php';

$pdo = db();

if (isset($_GET['id'])) {
    $testId = (int)$_GET['id'];

    $testStmt = $pdo->prepare(
        'SELECT t.id, t.title, tp.name AS topic_name, s.name AS subject_name, c.name AS class_name
         FROM tests t
         JOIN topics tp ON tp.id=t.topic_id
         JOIN subjects s ON s.id=tp.subject_id
         LEFT JOIN classes c ON c.id=s.class_id
         WHERE t.id=:id'
    );
    $testStmt->execute([':id' => $testId]);
    $test = $testStmt->fetch();

    if (!$test) {
        respond(['error' => 'Тест не знайдено'], 404);
    }

    $qStmt = $pdo->prepare('SELECT id, text FROM questions WHERE test_id=:test_id ORDER BY id');
    $qStmt->execute([':test_id' => $testId]);
    $questions = $qStmt->fetchAll();

    $oStmt = $pdo->prepare('SELECT id, text FROM options WHERE question_id=:question_id ORDER BY id');
    foreach ($questions as &$question) {
        $oStmt->execute([':question_id' => $question['id']]);
        $question['options'] = $oStmt->fetchAll();
    }

    $test['questions'] = $questions;
    respond(['test' => $test]);
}

$where = [];
$params = [];

if (!empty($_GET['class_id'])) {
    $where[] = 's.class_id = :class_id';
    $params[':class_id'] = (int)$_GET['class_id'];
}
if (!empty($_GET['subject_id'])) {
    $where[] = 's.id = :subject_id';
    $params[':subject_id'] = (int)$_GET['subject_id'];
}
if (!empty($_GET['topic_id'])) {
    $where[] = 'tp.id = :topic_id';
    $params[':topic_id'] = (int)$_GET['topic_id'];
}

$sql = 'SELECT t.id, t.title, tp.name AS topic_name, s.name AS subject_name, c.name AS class_name
        FROM tests t
        JOIN topics tp ON tp.id=t.topic_id
        JOIN subjects s ON s.id=tp.subject_id
        LEFT JOIN classes c ON c.id=s.class_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY t.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

respond(['tests' => $stmt->fetchAll()]);
