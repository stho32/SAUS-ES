<?php
declare(strict_types=1);

require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Prüfe Authentifizierung
if (!isset($_SESSION['master_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// Hole POST-Daten
$data = json_decode(file_get_contents('php://input'), true);

$commentId = isset($data['comment_id']) ? (int)$data['comment_id'] : null;
$voteType = $data['vote_type'] ?? null;
$username = getCurrentUsername();

if (!$commentId || !$voteType || !$username || !in_array($voteType, ['up', 'down'], true)) {
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

    $db->beginTransaction();

    try {
        // Lösche vorherige Stimme des Benutzers
        $stmt = $db->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND username = ?");
        $stmt->execute([$commentId, $username]);

        // Füge neue Stimme hinzu
        $stmt = $db->prepare("INSERT INTO comment_votes (comment_id, username, value) VALUES (?, ?, ?)");
        $stmt->execute([$commentId, $username, $voteType]);

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
