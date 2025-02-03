<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/attachment_functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file']) || !isset($_POST['ticketId'])) {
        throw new RuntimeException('Fehlende Parameter');
    }

    $ticketId = (int)$_POST['ticketId'];
    
    // Validiere Datei
    $fileInfo = validateUploadedFile($_FILES['file']);
    
    // Erstelle Upload-Verzeichnis
    $uploadDir = getUploadPath($ticketId);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0750, true);
    }
    
    // Erstelle .htaccess wenn nicht vorhanden
    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, 
            "Deny from all\n\n" .
            "<FilesMatch \"\.(php|php3|php4|php5|php7|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$\">\n" .
            "    Deny from all\n" .
            "</FilesMatch>\n\n" .
            "php_flag engine off\n" .
            "AddHandler cgi-script .php .php3 .php4 .php5 .php7 .phtml .pl .py .jsp .asp .htm .html .shtml .sh .cgi"
        );
    }
    
    // Verschiebe Datei
    $destination = $uploadDir . '/' . $fileInfo['safe_filename'];
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        throw new RuntimeException('Fehler beim Speichern der Datei');
    }
    
    // Speichere in Datenbank
    $attachmentId = saveAttachment($ticketId, $fileInfo, getCurrentUsername());
    
    echo json_encode([
        'success' => true,
        'id' => $attachmentId,
        'filename' => $fileInfo['original_filename']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
