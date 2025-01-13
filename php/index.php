<?php
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Master-Link
requireMasterLink();

// Setze Benutzernamen, falls angegeben
if (isset($_POST['username']) && isValidUsername($_POST['username'])) {
    setCurrentUsername($_POST['username']);
}

// Prüfe ob Benutzername gesetzt ist
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    // Zeige Formular für Benutzernamen
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SAUS-ES - Benutzername</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Benutzername festlegen</h3>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Ihr Namenskürzel</label>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           pattern="[a-zA-Z0-9_-]{2,50}" 
                                           title="2-50 Zeichen, nur Buchstaben, Zahlen, Unterstrich und Bindestrich erlaubt">
                                </div>
                                <button type="submit" class="btn btn-primary">Speichern</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$db = Database::getInstance()->getConnection();

// Hole alle aktiven Tickets
$stmt = $db->query("
    SELECT t.*, ts.name as status_name 
    FROM tickets t 
    JOIN ticket_status ts ON t.status_id = ts.id 
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - Übersicht</title>
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
                        <a class="nav-link active" href="index.php">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_ticket.php">Neues Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="completed_tickets.php">Abgeschlossene Tickets</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Ticket-Übersicht</h1>
        
        <div class="row mt-4">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= sanitizeInput($ticket['ticket_number']) ?></h5>
                            <span class="badge bg-<?= $ticket['status_name'] === 'geschlossen' ? 'secondary' : 'primary' ?>">
                                <?= sanitizeInput($ticket['status_name']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= sanitizeInput($ticket['title']) ?></h6>
                            <p class="card-text small">
                                Erstellt am: <?= formatDateTime($ticket['created_at']) ?>
                            </p>
                            <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="btn btn-primary">Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
