<?php
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
            $statusId = $stmt->fetchColumn();
            
            // Erstelle das Ticket
            $ticketNumber = generateTicketNumber();
            $stmt = $db->prepare("
                INSERT INTO tickets (ticket_number, title, ki_summary, status_id)
                VALUES (?, ?, ?, ?)
            ");
            
            // TODO: Hier KI-Zusammenfassung generieren
            $kiSummary = $description; // Vorläufig nur die Beschreibung
            
            $stmt->execute([$ticketNumber, $title, $kiSummary, $statusId]);
            $ticketId = $db->lastInsertId();
            
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
            $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - Neues Ticket erstellen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">SAUS-ES</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="create_ticket.php">Neues Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="completed_tickets.php">Abgeschlossene Tickets</a>
                    </li>
                </ul>
                <div class="ms-auto">
                    <span class="navbar-text">
                        Angemeldet als: <?= htmlspecialchars($currentUsername) ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Neues Ticket erstellen</h1>
        
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
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Beschreibung *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
