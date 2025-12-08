<?php
declare(strict_types=1);

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/news_functions.php';

// Check authentication (without JSON response)
session_start();
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    // Return 1x1 transparent PNG for unauthorized access
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}

try {
    $newsId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $thumbnail = isset($_GET['thumbnail']) && $_GET['thumbnail'] === 'true';

    if (!$newsId) {
        throw new RuntimeException('News-ID fehlt');
    }

    $db = Database::getInstance()->getConnection();

    // Get news image filename
    $stmt = $db->prepare("SELECT image_filename FROM news WHERE id = ?");
    $stmt->execute([$newsId]);
    $news = $stmt->fetch();

    if (!$news || empty($news['image_filename'])) {
        throw new RuntimeException('Kein Bild gefunden');
    }

    // Build file path
    $newsUploadPath = getNewsUploadPath($newsId);
    $filePath = $newsUploadPath . '/' . $news['image_filename'];

    if (!file_exists($filePath)) {
        throw new RuntimeException('Bilddatei existiert nicht: ' . $filePath . ' (Upload-Pfad: ' . $newsUploadPath . ', Filename: ' . $news['image_filename'] . ')');
    }

    // Validate file is within allowed directory
    $realPath = realpath($filePath);
    $allowedBasePath = realpath(__DIR__ . '/../uploads/news');
    if ($realPath === false || $allowedBasePath === false || strpos($realPath, $allowedBasePath) !== 0) {
        throw new RuntimeException('Ungültiger Dateipfad (Real: ' . ($realPath ?: 'false') . ', Allowed: ' . ($allowedBasePath ?: 'false') . ')');
    }

    // Get MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($realPath);

    // Validate MIME type
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        throw new RuntimeException('Ungültiger Dateityp');
    }

    // If thumbnail requested, generate it
    if ($thumbnail) {
        // Check if cached thumbnail exists
        $thumbnailPath = $newsUploadPath . '/thumb_' . $news['image_filename'];

        if (!file_exists($thumbnailPath)) {
            // Generate thumbnail
            $maxWidth = 200;

            // Load image based on type
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($realPath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($realPath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($realPath);
                    break;
                default:
                    throw new RuntimeException('Nicht unterstützter Bildtyp');
            }

            if ($sourceImage === false) {
                throw new RuntimeException('Bild konnte nicht geladen werden');
            }

            // Get original dimensions
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calculate new dimensions
            if ($originalWidth > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int)($originalHeight * ($maxWidth / $originalWidth));
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG and GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resample
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // Save thumbnail
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumbnail, $thumbnailPath, 8);
                    break;
                case 'image/gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
            }

            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
        }

        $filePath = $thumbnailPath;
        $realPath = realpath($filePath);
    }

    // Output image
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($realPath));
    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

    readfile($realPath);
    exit;

} catch (Exception $e) {
    // Return error as text for easy debugging
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "ERROR in get_news_image_preview.php:\n\n";
    echo "Message: " . $e->getMessage() . "\n\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    exit;
}
