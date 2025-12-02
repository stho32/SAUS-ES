<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/error_logger.php';

header('Content-Type: application/json');

// Only master-link can update news
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
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    $eventDate = trim($data['event_date'] ?? '');
    $imageFilename = isset($data['image_filename']) ? trim($data['image_filename']) : null;

    // Validate data
    if (!$newsId) {
        throw new Exception('News-ID fehlt');
    }

    if (empty($title)) {
        throw new Exception('Titel ist erforderlich');
    }

    if (empty($content)) {
        throw new Exception('Inhalt ist erforderlich');
    }

    if (empty($eventDate)) {
        throw new Exception('Veranstaltungsdatum ist erforderlich');
    }

    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d', $eventDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $eventDate) {
        throw new Exception('Ungültiges Datumsformat (YYYY-MM-DD erwartet)');
    }

    // Check if news exists
    $checkStmt = $db->prepare('SELECT id FROM news WHERE id = ?');
    $checkStmt->execute([$newsId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('News-Artikel nicht gefunden');
    }

    // Update database
    $query = "UPDATE news
              SET title = ?,
                  content = ?,
                  image_filename = ?,
                  event_date = ?
              WHERE id = ?";

    $stmt = $db->prepare($query);
    $imageValue = !empty($imageFilename) ? $imageFilename : null;

    $stmt->execute([
        $title,
        $content,
        $imageValue,
        $eventDate,
        $newsId
    ]);

    // Success regardless of whether data changed (rowCount can be 0 if data is identical)
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Aktualisieren der News: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
