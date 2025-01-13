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

$db = Database::getInstance()->getConnection();

// Hole alle abgeschlossenen Tickets
$stmt = $db->prepare("
    SELECT t.*, ts.name as status_name 
    FROM tickets t 
    JOIN ticket_status ts ON t.status_id = ts.id 
    WHERE ts.name IN ('geschlossen', 'archiviert')
    ORDER BY t.closed_at DESC
");
$stmt->execute();
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - Abgeschlossene Tickets</title>
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
                        <a class="nav-link" href="create_ticket.php">Neues Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="completed_tickets.php">Abgeschlossene Tickets</a>
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
        <h1>Abgeschlossene Tickets</h1>
        
        <div class="row mt-4">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($ticket['ticket_number']) ?></h5>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($ticket['status_name']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($ticket['title']) ?></h6>
                            <p class="card-text small">
                                Abgeschlossen am: <?= formatDateTime($ticket['closed_at']) ?>
                            </p>
                            <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="btn btn-primary">Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($tickets)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Es gibt noch keine abgeschlossenen Tickets.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
