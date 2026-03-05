<?php
require_once __DIR__ . '/_bootstrap.php';

$admin = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Метод не підтримується'], 405);
}

$input = jsonInput();
$pdo = db();

$topicId = (int)($input['topic_id'] ?? 0);
$testTitle = trim((string)($input['test_title'] ?? ''));
$questionText = trim((string)($input['question'] ?? ''));
$options = $input['options'] ?? [];
$correctIndex = (int)($input['correct_index'] ?? -1);

if ($topicId <= 0 || $testTitle === '' || $questionText === '' || !is_array($options) || count($options) !== 4 || $correctIndex < 0 || $correctIndex > 3) {
    respond(['error' => 'Некоректні дані'], 422);
}

$pdo->beginTransaction();

$testStmt = $pdo->prepare('SELECT id FROM tests WHERE topic_id=:topic_id AND title=:title LIMIT 1');
$testStmt->execute([':topic_id' => $topicId, ':title' => $testTitle]);
$test = $testStmt->fetch();

if (!$test) {
    $insertTest = $pdo->prepare('INSERT INTO tests (topic_id, title, created_by) VALUES (:topic_id, :title, :created_by)');
    $insertTest->execute([':topic_id' => $topicId, ':title' => $testTitle, ':created_by' => $admin['id']]);
    $testId = (int)$pdo->lastInsertId();
} else {
    $testId = (int)$test['id'];
}

$questionStmt = $pdo->prepare('INSERT INTO questions (test_id, text) VALUES (:test_id, :text)');
$questionStmt->execute([':test_id' => $testId, ':text' => $questionText]);
$questionId = (int)$pdo->lastInsertId();

$optionStmt = $pdo->prepare('INSERT INTO options (question_id, text, is_correct) VALUES (:question_id, :text, :is_correct)');
foreach ($options as $index => $optionText) {
    $optionStmt->execute([
        ':question_id' => $questionId,
        ':text' => trim((string)$optionText),
        ':is_correct' => $index === $correctIndex ? 1 : 0,
    ]);
}

$pdo->commit();
respond(['test_id' => $testId, 'question_id' => $questionId], 201);
