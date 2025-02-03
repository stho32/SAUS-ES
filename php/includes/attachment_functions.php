<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
global $db;
if (!isset($db)) {
    $db = Database::getInstance();
}

function validateUploadedFile(array $file): array {
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/plain' => 'txt'
    ];

    // Prüfe Dateiupload-Fehler
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload fehlgeschlagen');
    }

    // Prüfe Dateigröße (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('Datei zu groß (max 10MB)');
    }

    // Prüfe MIME-Type mit FileInfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!array_key_exists($mimeType, $allowedTypes)) {
        throw new RuntimeException('Nicht erlaubter Dateityp');
    }

    // Generiere sicheren Dateinamen
    $extension = $allowedTypes[$mimeType];
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    
    return [
        'safe_filename' => $newFilename,
        'mime_type' => $mimeType,
        'original_filename' => $file['name'],
        'size' => $file['size']
    ];
}

function getUploadPath(int $ticketId): string {
    $basePath = __DIR__ . '/../uploads/tickets';
    
    // Erstelle Basis-Upload-Verzeichnis falls nicht vorhanden
    if (!is_dir($basePath)) {
        if (!mkdir($basePath, 0750, true)) {
            throw new RuntimeException('Konnte Upload-Verzeichnis nicht erstellen');
        }
        
        // Erstelle .htaccess wenn nicht vorhanden
        $htaccessPath = $basePath . '/.htaccess';
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
    }
    
    $ticketPath = $basePath . '/' . $ticketId;
    
    // Erstelle Ticket-Verzeichnis falls nicht vorhanden
    if (!is_dir($ticketPath)) {
        if (!mkdir($ticketPath, 0750, true)) {
            throw new RuntimeException('Konnte Ticket-Verzeichnis nicht erstellen');
        }
    }
    
    return $ticketPath;
}

function saveAttachment(int $ticketId, array $fileInfo, string $uploadedBy): int {
    global $db;
    
    $stmt = $db->getConnection()->prepare('INSERT INTO ticket_attachments 
        (ticket_id, filename, original_filename, file_type, file_size, uploaded_by, upload_date) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())');
        
    $stmt->execute([
        $ticketId,
        $fileInfo['safe_filename'],
        $fileInfo['original_filename'],
        $fileInfo['mime_type'],
        $fileInfo['size'],
        $uploadedBy
    ]);
    
    return (int)$db->getConnection()->lastInsertId();
}

function getTicketAttachments(int $ticketId): array {
    global $db;
    
    try {
        $stmt = $db->getConnection()->prepare('SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY upload_date DESC');
        $stmt->execute([$ticketId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("Fehler beim Abrufen der Anhänge: " . $e->getMessage());
        return [];
    }
}

function deleteAttachment(int $attachmentId, int $ticketId): bool {
    global $db;
    
    // Hole Dateiinformationen
    $stmt = $db->getConnection()->prepare('SELECT filename FROM ticket_attachments WHERE id = ? AND ticket_id = ?');
    $stmt->execute([$attachmentId, $ticketId]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        return false;
    }
    
    // Lösche Datei
    $filePath = getUploadPath($ticketId) . '/' . $attachment['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Lösche Datenbankeintrag
    $stmt = $db->getConnection()->prepare('DELETE FROM ticket_attachments WHERE id = ? AND ticket_id = ?');
    return $stmt->execute([$attachmentId, $ticketId]);
}
