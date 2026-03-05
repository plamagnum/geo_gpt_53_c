<?php
require_once __DIR__ . '/_bootstrap.php';

session_destroy();
respond(['ok' => true]);
