<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/error_logger.php';

header('Content-Type: application/json');

// Nur Master-Link darf Tickets bearbeiten
if (!isset($_SESSION['master_code'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// Prüfe Request-Methode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Lese JSON-Daten
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Ungültige Anfrage');
    }

    $ticketId = intval($data['ticketId'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $statusId = intval($data['statusId'] ?? 0);
    $assignee = trim($data['assignee'] ?? '');

    // Validiere die Daten
    if (!$ticketId || empty($title)) {
        throw new Exception('Ungültige Daten');
    }

    // Debug-Logging
    ErrorLogger::getInstance()->logError("Update-Daten: " . print_r($data, true));
    
    $stmt = $db->prepare("
        UPDATE tickets 
        SET title = ?, 
            description = ?, 
            status_id = ?,
            assignee = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $statusId, $assignee, $ticketId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Ticket nicht gefunden oder keine Änderungen');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Aktualisieren des Tickets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
