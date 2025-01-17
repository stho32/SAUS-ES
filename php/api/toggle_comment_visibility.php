<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/error_logger.php';

header('Content-Type: application/json');

// Prüfe Authentifizierung
if (!isset($_SESSION['master_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$logger = ErrorLogger::getInstance();

// Hole POST-Daten
$data = json_decode(file_get_contents('php://input'), true);

$commentId = isset($data['commentId']) ? (int)$data['commentId'] : null;
$visible = isset($data['visible']) ? (bool)$data['visible'] : null;
$username = getCurrentUsername();

if (!$commentId || !$username) {
    $logger->logError("Ungültige Parameter: commentId=$commentId, username=$username");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Prüfe ob Kommentar existiert und Ticket nicht geschlossen ist
    $stmt = $db->prepare("
        SELECT c.id, ts.name as status_name
        FROM comments c
        JOIN tickets t ON c.ticket_id = t.id
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        throw new RuntimeException('Kommentar nicht gefunden');
    }

    if ($comment['status_name'] === 'geschlossen' || $comment['status_name'] === 'archiviert') {
        throw new RuntimeException('Ticket ist bereits geschlossen');
    }

    // Aktualisiere Sichtbarkeit
    $stmt = $db->prepare("
        UPDATE comments 
        SET is_visible = ?,
            hidden_by = CASE WHEN ? = 0 THEN ? ELSE NULL END,
            hidden_at = CASE WHEN ? = 0 THEN CURRENT_TIMESTAMP ELSE NULL END
        WHERE id = ?
    ");
    $stmt->execute([$visible, $visible, $username, $visible, $commentId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $logger->logError("Fehler beim Ändern der Kommentar-Sichtbarkeit", $e);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
