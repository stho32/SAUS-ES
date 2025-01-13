<?php
declare(strict_types=1);

require_once '../includes/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Prüfe Authentifizierung
if (!isset($_SESSION['master_code']) && !isPartnerLink($_GET['partner'] ?? null)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ticket ID fehlt']);
    exit;
}

try {
    $votes = countVotes($ticketId);
    echo json_encode([
        'success' => true,
        'up_votes' => (int)$votes['up_votes'],
        'down_votes' => (int)$votes['down_votes']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
