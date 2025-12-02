<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/Database.php';

// Load configuration
$configFile = __DIR__ . '/../includes/config.php';
if (!file_exists($configFile)) {
    throw new RuntimeException('Konfigurationsdatei nicht gefunden. Bitte config.example.php zu config.php kopieren.');
}
$config = require $configFile;

try {
    if (!isset($_GET['id'])) {
        throw new RuntimeException('Fehlende Parameter');
    }

    $newsId = (int)$_GET['id'];
    $asThumbnail = isset($_GET['thumbnail']) && $_GET['thumbnail'] === 'true';

    // Get news image information
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT image_filename FROM news WHERE id = ? AND image_filename IS NOT NULL');
    $stmt->execute([$newsId]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news || empty($news['image_filename'])) {
        throw new RuntimeException('Bild nicht gefunden');
    }

    // Get news images path from config
    $newsImagesPath = $config['news_images_path'] ?? __DIR__ . '/../../uploads/news';

    // Construct file path
    $filePath = $newsImagesPath . '/' . $newsId . '/' . $news['image_filename'];

    // Security: Verify path is within uploads/news directory
    $realPath = realpath($filePath);
    $uploadBase = realpath($newsImagesPath);

    if (!$realPath || !$uploadBase || strpos($realPath, $uploadBase) !== 0) {
        throw new RuntimeException('Ungültiger Dateipfad');
    }

    if (!file_exists($filePath)) {
        throw new RuntimeException('Datei nicht gefunden');
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($filePath);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($mimeType, $allowedTypes)) {
        throw new RuntimeException('Ungültiger Bildtyp');
    }

    // Set cache headers
    header('Cache-Control: public, max-age=31536000');
    header('Content-Type: ' . $mimeType);

    // If thumbnail is requested and it's an image
    if ($asThumbnail) {
        // Check if GD is available
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD library nicht verfügbar');
        }

        // Load source image
        $sourceImage = null;
        $exif = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($filePath);
                // Load EXIF data for JPEG
                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($filePath);
                }
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

        // Calculate new dimensions
        $maxWidth = 200;
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Correct orientation based on EXIF data
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: // 180 degrees
                    $sourceImage = imagerotate($sourceImage, 180, 0);
                    break;
                case 6: // 90 degrees clockwise
                    $sourceImage = imagerotate($sourceImage, -90, 0);
                    // Swap width and height
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
                case 8: // 90 degrees counter-clockwise
                    $sourceImage = imagerotate($sourceImage, 90, 0);
                    // Swap width and height
                    $temp = $sourceWidth;
                    $sourceWidth = $sourceHeight;
                    $sourceHeight = $temp;
                    break;
            }
        }

        $ratio = $sourceWidth / $maxWidth;
        $newWidth = $maxWidth;
        $newHeight = (int)round($sourceHeight / $ratio);

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Scale image
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );

        // Output thumbnail
        switch ($mimeType) {
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

        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    } else {
        // Output full image
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
