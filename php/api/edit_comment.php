<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

// JSON-Daten aus dem Request-Body lesen
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['commentId']) || !isset($data['content'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Parameter']);
    exit;
}

$commentId = (int)$data['commentId'];
$content = trim($data['content']);
$username = getCurrentUsername();

if (!$username) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Kommentarinhalt darf nicht leer sein']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Prüfen ob der Kommentar dem Benutzer gehört
    $stmt = $db->prepare("SELECT id FROM comments WHERE id = ? AND username = ?");
    $stmt->execute([$commentId, $username]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Keine Berechtigung diesen Kommentar zu bearbeiten']);
        exit;
    }
    
    // Kommentar aktualisieren
    $stmt = $db->prepare("
        UPDATE comments 
        SET content = ?, 
            updated_at = CURRENT_TIMESTAMP,
            is_edited = TRUE
        WHERE id = ? 
        AND username = ?
    ");
    
    $success = $stmt->execute([$content, $commentId, $username]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Kommentar wurde aktualisiert'
        ]);
    } else {
        throw new Exception('Fehler beim Aktualisieren des Kommentars');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Serverfehler: ' . $e->getMessage()]);
}
