<?php
declare(strict_types=1);

require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/error_logger.php';

$logger = ErrorLogger::getInstance();

// Aktiviere Error Logging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('error_log', '../logs/error.log');

// Log incoming data
$logger->logError("Received POST data: " . print_r(file_get_contents('php://input'), true));

header('Content-Type: application/json');

// Prüfe Authentifizierung
if (!isset($_SESSION['master_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// Hole POST-Daten
$data = json_decode(file_get_contents('php://input'), true);

$logger->logError("Decoded data: " . print_r($data, true));
$commentId = isset($data['commentId']) ? (int)$data['commentId'] : null;
$logger->logError("commentId: " . var_export($commentId, true));
$voteType = $data['voteType'] ?? null;
$logger->logError("voteType: " . var_export($voteType, true));
$username = getCurrentUsername();
$logger->logError("username: " . var_export($username, true));

if (!$commentId || !$voteType || !$username || !in_array($voteType, ['up', 'down'], true)) {
    $logger->logError("Ungültige Parameter: commentId=$commentId, voteType=$voteType, username=$username");
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
        $logger->logError("Kommentar nicht gefunden: commentId=$commentId");
        throw new RuntimeException('Kommentar nicht gefunden');
    }

    if ($comment['status_name'] === 'geschlossen' || $comment['status_name'] === 'archiviert') {
        $logger->logError("Ticket ist bereits geschlossen: commentId=$commentId, status={$comment['status_name']}");
        throw new RuntimeException('Ticket ist bereits geschlossen');
    }

    $db->beginTransaction();

    try {
        // Prüfe ob bereits eine Stimme existiert
        $stmt = $db->prepare("SELECT value FROM comment_votes WHERE comment_id = ? AND username = ?");
        $stmt->execute([$commentId, $username]);
        $existingVote = $stmt->fetch();

        // Lösche vorherige Stimme des Benutzers
        $stmt = $db->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND username = ?");
        $stmt->execute([$commentId, $username]);

        // Wenn keine vorherige Stimme existiert oder sie anders war als die neue, füge neue Stimme hinzu
        if (!$existingVote || $existingVote['value'] !== $voteType) {
            // Füge neue Stimme hinzu
            $stmt = $db->prepare("INSERT INTO comment_votes (comment_id, username, value) VALUES (?, ?, ?)");
            $stmt->execute([$commentId, $username, $voteType]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        $logger->logError("Datenbankfehler beim Abstimmen", $e);
        throw $e;
    }

} catch (Exception $e) {
    $logger->logError("Fehler beim Abstimmen", $e);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
