<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$ticketId = filter_input(INPUT_POST, 'ticketId', FILTER_VALIDATE_INT);
$assignee = trim($_POST['assignee'] ?? '');

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ticket ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Update den Zuständigen
    $stmt = $db->prepare("UPDATE tickets SET assignee = ? WHERE id = ?");
    $stmt->execute([$assignee ?: null, $ticketId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
