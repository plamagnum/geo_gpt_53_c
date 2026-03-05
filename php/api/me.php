<?php
require_once __DIR__ . '/_bootstrap.php';

$user = $_SESSION['user'] ?? null;
respond(['user' => $user]);
