<?php
declare(strict_types=1);

// Fehlerbehandlung einrichten
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $errstr]);
    exit;
}
set_error_handler('handleError');

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/comment_formatter.php';

header('Content-Type: application/json');

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

$commentId = filter_input(INPUT_POST, 'commentId', FILTER_VALIDATE_INT);
$content = trim(filter_input(INPUT_POST, 'content') ?? '');
$username = $_SESSION['username'] ?? '';

if (!$commentId || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Parameter']);
    exit;
}

if (!$username) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
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
            is_edited = TRUE,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$content, $commentId]);
    
    // Formatiere den Inhalt
    $formattedContent = formatComment($content);
    
    echo json_encode([
        'success' => true,
        'formattedContent' => $formattedContent
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
