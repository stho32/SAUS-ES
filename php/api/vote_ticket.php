<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Nur POST-Requests sind erlaubt');
    }

    $ticketId = $_POST['ticketId'] ?? null;
    $voteValue = $_POST['value'] ?? null;
    $username = getCurrentUsername();

    if (!$ticketId || !$voteValue || !$username) {
        throw new RuntimeException('Fehlende Parameter');
    }

    if (!in_array($voteValue, ['up', 'down', 'none'])) {
        throw new RuntimeException('Ungültiger Vote-Wert');
    }

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Lösche existierenden Vote falls vorhanden
    $stmt = $db->prepare('DELETE FROM ticket_votes WHERE ticket_id = ? AND username = ?');
    $stmt->execute([$ticketId, $username]);

    // Füge neuen Vote hinzu wenn nicht 'none'
    if ($voteValue !== 'none') {
        $stmt = $db->prepare('INSERT INTO ticket_votes (ticket_id, username, value) VALUES (?, ?, ?)');
        $stmt->execute([$ticketId, $username, $voteValue]);
    }

    // Hole aktualisierte Statistiken
    $stmt = $db->prepare('
        SELECT up_votes, down_votes, total_votes,
               (SELECT GROUP_CONCAT(username) FROM ticket_votes WHERE ticket_id = ? AND value = "up") as upvoters,
               (SELECT GROUP_CONCAT(username) FROM ticket_votes WHERE ticket_id = ? AND value = "down") as downvoters
        FROM ticket_statistics 
        WHERE ticket_id = ?
    ');
    $stmt->execute([$ticketId, $ticketId, $ticketId]);
    $stats = $stmt->fetch();

    $db->commit();

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
