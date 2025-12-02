<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
global $db;
if (!isset($db)) {
    $db = Database::getInstance();
}

/**
 * Validates uploaded news image file
 *
 * @param array $file The $_FILES array entry
 * @return array Contains safe_filename, mime_type, original_filename, size
 * @throws RuntimeException on validation failure
 */
function validateNewsImageUpload(array $file): array {
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif'
    ];

    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload fehlgeschlagen');
    }

    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Datei zu groß (max 2MB)');
    }

    // Validate MIME type with FileInfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedTypes)) {
        throw new RuntimeException('Nicht erlaubter Dateityp (nur JPG, PNG, GIF)');
    }

    // Generate safe filename
    $extension = $allowedTypes[$mimeType];
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

    return [
        'safe_filename' => $newFilename,
        'mime_type' => $mimeType,
        'original_filename' => $file['name'],
        'size' => $file['size']
    ];
}

/**
 * Gets the upload path for a news article, creates it if needed
 *
 * @param int $newsId The news article ID
 * @return string The absolute path to the upload directory
 * @throws RuntimeException if directory cannot be created
 */
function getNewsUploadPath(int $newsId): string {
    $basePath = __DIR__ . '/../uploads/news';

    // Create base upload directory if not exists
    if (!is_dir($basePath)) {
        if (!mkdir($basePath, 0750, true)) {
            throw new RuntimeException('Konnte Upload-Verzeichnis nicht erstellen');
        }

        // Create .htaccess for security
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

    $newsPath = $basePath . '/' . $newsId;

    // Create news-specific directory if not exists
    if (!is_dir($newsPath)) {
        if (!mkdir($newsPath, 0750, true)) {
            throw new RuntimeException('Konnte News-Verzeichnis nicht erstellen');
        }
    }

    return $newsPath;
}

/**
 * Deletes all files in a news article's upload directory
 *
 * @param int $newsId The news article ID
 * @return void
 */
function deleteNewsFiles(int $newsId): void {
    $newsPath = __DIR__ . '/../uploads/news/' . $newsId;

    if (!is_dir($newsPath)) {
        return; // Nothing to delete
    }

    // Delete all files in the directory
    $files = glob($newsPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    // Remove the directory itself
    @rmdir($newsPath);
}
