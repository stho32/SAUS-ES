<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/news_functions.php';
require_once __DIR__ . '/../includes/error_logger.php';

header('Content-Type: application/json');

// Only master-link can delete news
if (!isset($_SESSION['master_code'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Read JSON data
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    if (!$data) {
        throw new Exception('Ungültige JSON-Anfrage: ' . json_last_error_msg());
    }

    $newsId = intval($data['id'] ?? 0);

    if (!$newsId) {
        throw new Exception('News-ID fehlt');
    }

    // Check if news exists
    $checkStmt = $db->prepare('SELECT id FROM news WHERE id = ?');
    $checkStmt->execute([$newsId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('News-Artikel nicht gefunden');
    }

    // Delete from database first
    $stmt = $db->prepare('DELETE FROM news WHERE id = ?');
    $stmt->execute([$newsId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('News konnte nicht gelöscht werden');
    }

    // Delete associated files
    try {
        deleteNewsFiles($newsId);
    } catch (Exception $fileError) {
        // Log file deletion error but don't fail the operation
        ErrorLogger::getInstance()->logError("Fehler beim Löschen der News-Dateien: " . $fileError->getMessage());
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Löschen der News: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
