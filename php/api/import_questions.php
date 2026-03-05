<?php
require_once __DIR__ . '/_bootstrap.php';

$admin = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Метод не підтримується'], 405);
}

$payload = jsonInput();
$className = trim((string)($payload['class'] ?? ''));
$subjectName = trim((string)($payload['subject'] ?? ''));
$topicName = trim((string)($payload['topic'] ?? ''));
$testTitle = trim((string)($payload['test_title'] ?? ''));
$questions = $payload['questions'] ?? [];

if ($className === '' || $subjectName === '' || $topicName === '' || $testTitle === '' || !is_array($questions) || $questions === []) {
    respond(['error' => 'Некоректний JSON формат'], 422);
}

$pdo = db();
$pdo->beginTransaction();

$getOrCreate = function (string $table, array $where, array $insert) use ($pdo): int {
    $whereSql = implode(' AND ', array_map(fn($k) => "$k = :w_$k", array_keys($where)));
    $select = $pdo->prepare("SELECT id FROM {$table} WHERE {$whereSql} LIMIT 1");
    $params = [];
    foreach ($where as $k => $v) {
        $params[":w_$k"] = $v;
    }
    $select->execute($params);
    $row = $select->fetch();
    if ($row) {
        return (int)$row['id'];
    }

    $columns = implode(', ', array_keys($insert));
    $values = implode(', ', array_map(fn($k) => ":i_$k", array_keys($insert)));
    $insertStmt = $pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$values})");
    $insertParams = [];
    foreach ($insert as $k => $v) {
        $insertParams[":i_$k"] = $v;
    }
    $insertStmt->execute($insertParams);
    return (int)$pdo->lastInsertId();
};

$classId = $getOrCreate('classes', ['name' => $className], ['name' => $className]);
$subjectId = $getOrCreate('subjects', ['name' => $subjectName, 'class_id' => $classId], ['name' => $subjectName, 'class_id' => $classId]);
$topicId = $getOrCreate('topics', ['name' => $topicName, 'subject_id' => $subjectId], ['name' => $topicName, 'subject_id' => $subjectId]);
$testId = $getOrCreate('tests', ['title' => $testTitle, 'topic_id' => $topicId], ['title' => $testTitle, 'topic_id' => $topicId, 'created_by' => $admin['id']]);

$qStmt = $pdo->prepare('INSERT INTO questions (test_id, text) VALUES (:test_id, :text)');
$oStmt = $pdo->prepare('INSERT INTO options (question_id, text, is_correct) VALUES (:question_id, :text, :is_correct)');
$createdQuestions = 0;

foreach ($questions as $q) {
    $text = trim((string)($q['text'] ?? ''));
    $opts = $q['options'] ?? [];
    $correctIndex = (int)($q['correct_index'] ?? -1);

    if ($text === '' || !is_array($opts) || count($opts) !== 4 || $correctIndex < 0 || $correctIndex > 3) {
        continue;
    }

    $qStmt->execute([':test_id' => $testId, ':text' => $text]);
    $questionId = (int)$pdo->lastInsertId();

    foreach ($opts as $index => $optionText) {
        $oStmt->execute([
            ':question_id' => $questionId,
            ':text' => trim((string)$optionText),
            ':is_correct' => $index === $correctIndex ? 1 : 0,
        ]);
    }

    $createdQuestions++;
}

$pdo->commit();
respond(['test_id' => $testId, 'created_questions' => $createdQuestions], 201);
