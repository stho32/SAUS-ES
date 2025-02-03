<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance();
require_once __DIR__ . '/../includes/attachment_functions.php';

try {
    if (!isset($_GET['id']) || !isset($_GET['ticketId'])) {
        throw new RuntimeException('Fehlende Parameter');
    }

    $attachmentId = (int)$_GET['id'];
    $ticketId = (int)$_GET['ticketId'];
    
    // Hole Attachment-Informationen
    $stmt = $db->getConnection()->prepare('SELECT * FROM ticket_attachments WHERE id = ? AND ticket_id = ?');
    $stmt->execute([$attachmentId, $ticketId]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        throw new RuntimeException('Datei nicht gefunden');
    }
    
    $filePath = getUploadPath($ticketId) . '/' . $attachment['filename'];
    if (!file_exists($filePath)) {
        throw new RuntimeException('Datei nicht gefunden');
    }
    
    // Setze Header
    header('Content-Type: ' . $attachment['file_type']);
    header('Content-Disposition: inline; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . $attachment['file_size']);
    
    // Sende Datei
    readfile($filePath);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
