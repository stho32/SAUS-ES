<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

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
$content = trim($data['content'] ?? '');
$username = getCurrentUsername();

if (!$ticketId || empty($content) || !$username) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Prüfe ob Ticket existiert
    $stmt = $db->prepare("
        SELECT t.id
        FROM tickets t
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    // Füge neuen Kommentar hinzu
    $stmt = $db->prepare("
        INSERT INTO comments (ticket_id, username, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$ticketId, $username, $content]);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
