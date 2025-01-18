<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$content = $_POST['content'] ?? '';

// Importiere die formatComment Funktion
require_once __DIR__ . '/../ticket_view.php';

try {
    $formattedContent = formatComment($content);
    echo json_encode([
        'success' => true,
        'formattedContent' => $formattedContent
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
