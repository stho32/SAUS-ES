<?php
declare(strict_types=1);

// Aktiviere Fehleranzeige
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Lade Pfad-Konfiguration
$pathsConfigFile = __DIR__ . '/paths_config.php';
$pathsConfigExampleFile = __DIR__ . '/paths_config.example.php';

// Wenn die eigentliche Konfiguration nicht existiert, aber das Example, warnen
if (!file_exists($pathsConfigFile) && file_exists($pathsConfigExampleFile)) {
    error_log("WARNUNG: paths_config.php nicht gefunden. Bitte kopieren Sie paths_config.example.php zu paths_config.php und passen Sie sie an.");
    // Trotzdem weitermachen mit der Example-Datei
    $pathsConfigFile = $pathsConfigExampleFile;
}

// Standard-Konfiguration falls keine Datei existiert
$defaultConfig = [
    'base_path' => __DIR__ . '/../../',
    'uploads_path' => 'php/uploads/tickets/'
];

$pathsConfig = file_exists($pathsConfigFile) ? require_once $pathsConfigFile : $defaultConfig;

// Pfad zur Hauptanwendung für Datenbankverbindung
$basePath = $pathsConfig['base_path'];
require_once '../includes/Database.php';

// Sicherstellen, dass ein Geheimcode übergeben wurde
$secretCode = $_GET['code'] ?? '';
if (empty($secretCode) || strlen($secretCode) !== 50) {
    http_response_code(404);
    echo "<h1>Fehler 404</h1><p>Die angeforderte Seite wurde nicht gefunden.</p>";
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Überprüfung, ob $db direkt ein PDO-Objekt ist
    $connection = ($db instanceof PDO) ? $db : $db->getConnection();
    
    // Hole Ticket-Informationen anhand des Secret Codes
    $stmt = $connection->prepare("
        SELECT t.id, t.title
        FROM tickets t
        WHERE t.secret_string = ?
    ");
    $stmt->execute([$secretCode]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo "<h1>Fehler 404</h1><p>Die angeforderte Seite wurde nicht gefunden.</p>";
        exit;
    }
    
    // Hole die Anhänge des Tickets
    $ticketId = $ticket['id'];
    $stmt = $connection->prepare("
        SELECT id, filename, original_filename, file_type, file_size, upload_date
        FROM ticket_attachments
        WHERE ticket_id = ? AND file_type LIKE 'image/%'
        ORDER BY upload_date DESC
    ");
    $stmt->execute([$ticketId]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>Fehler 500</h1><p>Ein Serverfehler ist aufgetreten.</p>";
    exit;
}

// Verzeichnispfad für die Anhänge
$uploadPath = $basePath . $pathsConfig['uploads_path'] . $ticketId . '/';

// Bild anzeigen, wenn ID angegeben
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    
    // Suche nach dem Anhang mit der ID
    $attachmentToView = null;
    foreach ($attachments as $attachment) {
        if ((int)$attachment['id'] === $viewId) {
            $attachmentToView = $attachment;
            break;
        }
    }
    
    if ($attachmentToView) {
        $filePath = $uploadPath . $attachmentToView['filename'];
        if (file_exists($filePath)) {
            header('Content-Type: ' . $attachmentToView['file_type']);
            readfile($filePath);
            exit;
        }
    }
    
    // Wenn Datei nicht gefunden
    http_response_code(404);
    echo "<h1>Fehler 404</h1><p>Das angeforderte Bild wurde nicht gefunden.</p>";
    exit;
}

// HTML für die Galerieansicht
$pageTitle = "Bildergalerie: " . htmlspecialchars($ticket['title']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .gallery-container {
            padding: 20px;
        }
        
        .gallery-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .gallery-card {
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
            height: 200px;
        }
        
        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 8px 12px;
            font-size: 0.85rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .gallery-card:hover .gallery-overlay {
            opacity: 1;
        }
        
        .modal-img {
            max-width: 100%;
            max-height: 80vh;
        }
        
        .img-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .empty-message {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-message i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container gallery-container">
        <div class="gallery-header">
            <h1><?= $pageTitle ?></h1>
            <p class="text-muted">Bilder anklicken für Vollansicht</p>
        </div>
        
        <?php if (empty($attachments)): ?>
            <div class="empty-message">
                <i class="bi bi-images"></i>
                <h3>Keine Bilder vorhanden</h3>
                <p>Für dieses Ticket wurden bisher keine Bilder hochgeladen.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="gallery-card" data-bs-toggle="modal" data-bs-target="#imageModal" 
                             data-src="?code=<?= urlencode($secretCode) ?>&view=<?= $attachment['id'] ?>"
                             data-info="<?= htmlspecialchars($attachment['original_filename']) ?> (<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)">
                            <img src="?code=<?= urlencode($secretCode) ?>&view=<?= $attachment['id'] ?>" 
                                 alt="<?= htmlspecialchars($attachment['original_filename']) ?>" 
                                 class="gallery-img">
                            <div class="gallery-overlay">
                                <?= htmlspecialchars(substr($attachment['original_filename'], 0, 25) . (strlen($attachment['original_filename']) > 25 ? '...' : '')) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bild-Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bildansicht</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-img" alt="Bild in Vollansicht">
                    <div class="img-info"></div>
                </div>
                <div class="modal-footer">
                    <a href="" class="btn btn-primary" download>Download</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal-Funktionalität für Bildanzeige
        document.addEventListener('DOMContentLoaded', function() {
            const imageModal = document.getElementById('imageModal');
            if (imageModal) {
                imageModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const imgSrc = button.getAttribute('data-src');
                    const imgInfo = button.getAttribute('data-info');
                    
                    const modalImg = this.querySelector('.modal-img');
                    const modalImgInfo = this.querySelector('.img-info');
                    const modalDownload = this.querySelector('.modal-footer a');
                    
                    modalImg.src = imgSrc;
                    modalImgInfo.textContent = imgInfo;
                    modalDownload.href = imgSrc;
                });
            }
        });
    </script>
</body>
</html>
