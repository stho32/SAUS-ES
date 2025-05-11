<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

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
$partnerName = trim($data['partner_name'] ?? '');

if (!$ticketId || empty($partnerName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Prüfe ob Ticket existiert
    $stmt = $db->prepare("SELECT id FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    
    if (!$stmt->fetch()) {
        throw new RuntimeException('Ticket nicht gefunden');
    }
    
    $partnerLink = generatePartnerLink();
    
    // Füge neuen Partner hinzu
    $stmt = $db->prepare("
        INSERT INTO partners (ticket_id, partner_link, partner_name)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$ticketId, $partnerLink, $partnerName]);
    
    echo json_encode([
        'success' => true,
        'partner_link' => $partnerLink
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
