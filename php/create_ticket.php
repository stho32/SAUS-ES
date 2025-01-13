<?php
declare(strict_types=1);

require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Master-Link
requireMasterLink();

// Prüfe ob Benutzername gesetzt ist
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = 'Bitte füllen Sie alle Pflichtfelder aus.';
    } else {
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Hole den "offen" Status
            $stmt = $db->prepare("SELECT id FROM ticket_status WHERE name = 'offen' LIMIT 1");
            $stmt->execute();
            $statusId = (int)$stmt->fetchColumn();
            
            if (!$statusId) {
                throw new RuntimeException('Status "offen" nicht gefunden');
            }
            
            // Erstelle das Ticket
            $ticketNumber = generateTicketNumber();
            $stmt = $db->prepare("
                INSERT INTO tickets (ticket_number, title, ki_summary, status_id)
                VALUES (?, ?, ?, ?)
            ");
            
            // TODO: Hier KI-Zusammenfassung generieren
            $kiSummary = $description; // Vorläufig nur die Beschreibung
            
            $stmt->execute([$ticketNumber, $title, $kiSummary, $statusId]);
            $ticketId = (int)$db->lastInsertId();
            
            // Erstelle den ersten Kommentar mit der Beschreibung
            $stmt = $db->prepare("
                INSERT INTO comments (ticket_id, username, content)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ticketId, $currentUsername, $description]);
            
            $db->commit();
            
            header("Location: ticket_view.php?id=$ticketId");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Ein Fehler ist aufgetreten: ' . $e->getMessage();
        }
    }
}

// Template-Rendering
$pageTitle = 'Neues Ticket erstellen';
require_once 'templates/header.php';
?>

<div class="container mt-4">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="title" class="form-label">Titel *</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           maxlength="255"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Beschreibung *</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="5" required maxlength="65535"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="form-text">
                        Beschreiben Sie das Thema ausführlich. Eine KI-Zusammenfassung wird automatisch erstellt.
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Ticket erstellen</button>
                <a href="index.php" class="btn btn-secondary">Abbrechen</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
