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

$ticketId = isset($data['ticket_id']) ? (int)$data['ticket_id'] : null;
$voteType = $data['vote_type'] ?? null;
$username = getCurrentUsername();

if (!$ticketId || !$voteType || !$username || !in_array($voteType, ['up', 'down'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Prüfe ob Ticket existiert und nicht geschlossen ist
    $stmt = $db->prepare("
        SELECT t.id, ts.name as status_name
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    if ($ticket['status_name'] === 'geschlossen' || $ticket['status_name'] === 'archiviert') {
        throw new RuntimeException('Ticket ist bereits geschlossen');
    }

    $db->beginTransaction();

    try {
        // Lösche vorherige Stimme des Benutzers
        $stmt = $db->prepare("DELETE FROM votes WHERE ticket_id = ? AND username = ?");
        $stmt->execute([$ticketId, $username]);

        // Füge neue Stimme hinzu
        $stmt = $db->prepare("INSERT INTO votes (ticket_id, username, value) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $username, $voteType]);

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
