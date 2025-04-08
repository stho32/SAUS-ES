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
    $asThumbnail = isset($_GET['asThumbnail']) && $_GET['asThumbnail'] === 'true';
    
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
    
    // Setze Header für Caching
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    header('Content-Type: ' . $attachment['file_type']);
    
    // Wenn es ein Bild ist und Thumbnail angefordert wurde
    if ($asThumbnail && str_starts_with($attachment['file_type'], 'image/')) {
        // Prüfe ob GD verfügbar ist
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD library nicht verfügbar');
        }
        
        // Lade Originalbild
        $sourceImage = null;
        switch ($attachment['file_type']) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($filePath);
                break;
            default:
                throw new RuntimeException('Nicht unterstütztes Bildformat');
        }
        
        if (!$sourceImage) {
            throw new RuntimeException('Bild konnte nicht geladen werden');
        }
        
        // Berechne neue Dimensionen
        $maxWidth = 200;
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $ratio = $sourceWidth / $maxWidth;
        $newWidth = $maxWidth;
        $newHeight = (int)round($sourceHeight / $ratio);
        
        // Erstelle Thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Behandle Transparenz für PNG und GIF
        if ($attachment['file_type'] === 'image/png' || $attachment['file_type'] === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Skaliere Bild
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Ausgabe Thumbnail
        switch ($attachment['file_type']) {
            case 'image/jpeg':
                imagejpeg($thumbnail, null, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, null, 6);
                break;
            case 'image/gif':
                imagegif($thumbnail);
                break;
        }
        
        // Aufräumen
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    } else {
        // Normal file output
        header('Content-Disposition: inline; filename="' . $attachment['original_filename'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        readfile($filePath);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
