<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/news_functions.php';
require_once __DIR__ . '/../includes/error_logger.php';

header('Content-Type: application/json');

// Only master-link can upload news images
if (!isset($_SESSION['master_code'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

try {
    if (!isset($_FILES['file']) || !isset($_POST['newsId'])) {
        throw new RuntimeException('Fehlende Parameter');
    }

    $newsId = (int)$_POST['newsId'];

    // Validate that news article exists
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT id FROM news WHERE id = ?');
    $stmt->execute([$newsId]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('News-Artikel nicht gefunden');
    }

    // Validate file
    $fileInfo = validateNewsImageUpload($_FILES['file']);

    // Create upload directory
    $uploadDir = getNewsUploadPath($newsId);

    // Move uploaded file
    $destination = $uploadDir . '/' . $fileInfo['safe_filename'];
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        throw new RuntimeException('Fehler beim Speichern der Datei');
    }

    // Update news table with filename
    $stmt = $db->prepare('UPDATE news SET image_filename = ? WHERE id = ?');
    $stmt->execute([$fileInfo['safe_filename'], $newsId]);

    echo json_encode([
        'success' => true,
        'filename' => $fileInfo['safe_filename'],
        'original_filename' => $fileInfo['original_filename']
    ]);

} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Upload des News-Bildes: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
