<?php
require_once __DIR__ . '/_bootstrap.php';

$classes = db()->query('SELECT id, name FROM classes ORDER BY CAST(name AS UNSIGNED), name')->fetchAll();
respond(['classes' => $classes]);
