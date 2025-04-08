<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/comment_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$ticketId = filter_input(INPUT_POST, 'ticketId', FILTER_VALIDATE_INT);
$followUpDate = trim($_POST['followUpDate'] ?? '');

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ticket ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Hole das aktuelle Wiedervorlagedatum für den Kommentar
    $oldDateStmt = $db->prepare("SELECT follow_up_date FROM tickets WHERE id = ?");
    $oldDateStmt->execute([$ticketId]);
    $oldDate = $oldDateStmt->fetch(PDO::FETCH_COLUMN);

    // Update das Wiedervorlagedatum
    $stmt = $db->prepare("UPDATE tickets SET follow_up_date = ? WHERE id = ?");
    $stmt->execute([$followUpDate ?: null, $ticketId]);
    
    // Füge Kommentar hinzu
    addFollowUpDateComment($ticketId, $followUpDate ?: null, $oldDate);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
