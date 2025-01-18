<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Database.php';

// Prüfe ob ein gültiger Session-Cookie existiert
session_start();

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}
