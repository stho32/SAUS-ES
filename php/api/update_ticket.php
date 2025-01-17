<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/error_logger.php';

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

// Lese JSON-Daten
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
    exit;
}

// Validiere Pflichtfelder
if (!isset($data['ticketId'], $data['title'], $data['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fehlende Pflichtfelder']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Debug-Logging
    ErrorLogger::getInstance()->logError("Update-Daten: " . print_r($data, true));
    
    // Bereite Update-Daten vor
    $updateData = [
        'title' => $data['title'],
        'description' => $data['description'],
        'status_id' => $data['statusId']
    ];
    
    // Baue SQL-Query
    $sql = "UPDATE tickets SET ";
    $params = [];
    foreach ($updateData as $field => $value) {
        $sql .= "`$field` = ?, ";
        $params[] = $value;
    }
    $sql = rtrim($sql, ', ');
    $sql .= " WHERE id = ?";
    $params[] = $data['ticketId'];
    
    // Debug-Logging
    ErrorLogger::getInstance()->logError("SQL: $sql");
    ErrorLogger::getInstance()->logError("Parameter: " . print_r($params, true));
    
    // Führe Update durch
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Ticket nicht gefunden oder keine Änderungen');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Aktualisieren des Tickets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
