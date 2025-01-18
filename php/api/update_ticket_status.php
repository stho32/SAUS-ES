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
$statusId = filter_input(INPUT_POST, 'statusId', FILTER_VALIDATE_INT);

if (!$ticketId || !$statusId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Prüfe ob der Status existiert
    $checkStmt = $db->prepare("SELECT id FROM ticket_status WHERE id = ?");
    $checkStmt->execute([$statusId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Invalid status');
    }
    
    // Update den Ticket-Status
    $stmt = $db->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
    $stmt->execute([$statusId, $ticketId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
