<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/attachment_functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !isset($_POST['ticketId'])) {
        throw new RuntimeException('Fehlende Parameter');
    }

    $attachmentId = (int)$_POST['id'];
    $ticketId = (int)$_POST['ticketId'];
    
    if (deleteAttachment($attachmentId, $ticketId)) {
        echo json_encode(['success' => true]);
    } else {
        throw new RuntimeException('Fehler beim Löschen der Datei');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
