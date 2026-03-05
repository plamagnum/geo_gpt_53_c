<?php
require_once __DIR__ . '/_bootstrap.php';

$user = requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Метод не підтримується'], 405);
}

$input = jsonInput();
$testId = (int)($input['test_id'] ?? 0);
$answers = $input['answers'] ?? [];

if ($testId <= 0 || !is_array($answers)) {
    respond(['error' => 'Некоректні дані'], 422);
}

$pdo = db();
$qStmt = $pdo->prepare('SELECT id FROM questions WHERE test_id=:test_id');
$qStmt->execute([':test_id' => $testId]);
$questions = $qStmt->fetchAll();

$correct = 0;
$total = count($questions);
$details = [];

$cStmt = $pdo->prepare('SELECT id FROM options WHERE question_id=:question_id AND is_correct=1 LIMIT 1');

foreach ($questions as $q) {
    $questionId = (int)$q['id'];
    $selectedOptionId = isset($answers[$questionId]) ? (int)$answers[$questionId] : 0;

    $cStmt->execute([':question_id' => $questionId]);
    $correctOption = $cStmt->fetch();
    $correctOptionId = $correctOption ? (int)$correctOption['id'] : 0;
    $isRight = $selectedOptionId > 0 && $selectedOptionId === $correctOptionId;

    if ($isRight) {
        $correct++;
    }

    $details[] = [
        'question_id' => $questionId,
        'selected_option_id' => $selectedOptionId,
        'correct_option_id' => $correctOptionId,
        'is_correct' => $isRight,
    ];
}

$insert = $pdo->prepare('INSERT INTO attempts (user_id, test_id, score, total) VALUES (:user_id, :test_id, :score, :total)');
$insert->execute([
    ':user_id' => $user['id'],
    ':test_id' => $testId,
    ':score' => $correct,
    ':total' => $total,
]);

respond(['score' => $correct, 'total' => $total, 'details' => $details]);
