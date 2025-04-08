<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/auth_check.php';

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/error_logger.php';
require_once '../includes/comment_functions.php';

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
    $jsonInput = file_get_contents('php://input');
    ErrorLogger::getInstance()->logError("Rohe JSON-Eingabe: " . $jsonInput);
    
    $data = json_decode($jsonInput, true);
    if (!$data) {
        throw new Exception('Ungültige JSON-Anfrage: ' . json_last_error_msg());
    }

    $ticketId = intval($data['ticketId'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $statusId = intval($data['statusId'] ?? 0);
    $assignee = trim($data['assignee'] ?? '');
    $showOnWebsite = (bool)($data['showOnWebsite'] ?? false);
    $publicComment = trim($data['publicComment'] ?? '');
    $affectedNeighbors = isset($data['affectedNeighbors']) ? $data['affectedNeighbors'] : null;
    $followUpDate = isset($data['followUpDate']) && !empty($data['followUpDate']) ? $data['followUpDate'] : null;
    $doNotTrack = (bool)($data['doNotTrack'] ?? false);

    // Validiere die Daten
    if (!$ticketId || empty($title)) {
        throw new Exception('Ungültige Daten: Ticket ID oder Titel fehlt');
    }

    // Validiere affected_neighbors
    if ($affectedNeighbors !== null) {
        if (!is_numeric($affectedNeighbors)) {
            throw new Exception('Anzahl betroffener Nachbarn muss eine Zahl sein');
        }
        $affectedNeighbors = intval($affectedNeighbors);
        if ($affectedNeighbors < 0) {
            throw new Exception('Anzahl betroffener Nachbarn kann nicht negativ sein');
        }
    }

    // Hole das aktuelle Wiedervorlagedatum für den Kommentar
    $oldDateStmt = $db->prepare("SELECT follow_up_date FROM tickets WHERE id = ?");
    $oldDateStmt->execute([$ticketId]);
    $oldDate = $oldDateStmt->fetch(PDO::FETCH_COLUMN);

    $query = "
        UPDATE tickets 
        SET title = ?, 
            description = ?, 
            status_id = ?,
            assignee = ?,
            show_on_website = ?,
            public_comment = ?,
            affected_neighbors = ?,
            follow_up_date = ?,
            do_not_track = ?
        WHERE id = ?
    ";
    
    $stmt = $db->prepare($query);
    $params = [
        $title, 
        $description, 
        $statusId, 
        $assignee, 
        $showOnWebsite, 
        $publicComment, 
        $affectedNeighbors,
        $followUpDate,
        $doNotTrack,
        $ticketId
    ];
    
    ErrorLogger::getInstance()->logError("SQL Query: " . $query);
    ErrorLogger::getInstance()->logError("SQL Parameter: " . print_r($params, true));
    
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Ticket nicht gefunden oder keine Änderungen');
    }

    // Füge Kommentar hinzu, wenn sich das Wiedervorlagedatum geändert hat
    addFollowUpDateComment($ticketId, $followUpDate, $oldDate);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Aktualisieren des Tickets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
