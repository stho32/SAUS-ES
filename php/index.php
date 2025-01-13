<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Database.php';

// Prüfe Master-Link
requireMasterLink();

// Hole Benutzernamen, wenn noch nicht gesetzt
if (!getCurrentUsername()) {
    if (isset($_POST['username'])) {
        setCurrentUsername($_POST['username']);
    } else {
        // Zeige Formular für Benutzernamen
        require_once __DIR__ . '/includes/header.php';
        ?>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Willkommen bei SAUS-ES</h5>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Ihr Namenskürzel:</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Weiter</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

// Header einbinden
require_once __DIR__ . '/includes/header.php';

// Suchparameter
$search = trim($_GET['search'] ?? '');
$searchCondition = '';
$searchParams = [];

if ($search !== '') {
    $searchCondition = "WHERE (
        t.ticket_number LIKE :search 
        OR t.title LIKE :search 
        OR t.ki_summary LIKE :search
    )";
    $searchParams[':search'] = "%$search%";
}

// Hole alle Tickets
$db = Database::getInstance()->getConnection();
$query = "
    SELECT t.*, ts.name as status_name, 
           (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as comment_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    $searchCondition
    ORDER BY t.created_at DESC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($searchParams);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    ErrorLogger::getInstance()->logError("Fehler beim Laden der Tickets", $e);
    $tickets = [];
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1>Ticket-Übersicht</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <form method="get" class="d-flex">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Suche nach Ticket-Nr., Titel oder Inhalt..." 
                           name="search"
                           value="<?= htmlspecialchars($search) ?>"
                           aria-label="Suchbegriff">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= $basePath ?>/index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= $basePath ?>/create_ticket.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Neues Ticket
            </a>
        </div>
    </div>

    <?php if ($search !== '' && empty($tickets)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Keine Tickets gefunden für "<?= htmlspecialchars($search) ?>".
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($tickets as $ticket): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title">
                            <a href="ticket_view.php?id=<?= $ticket['id'] ?>">
                                <?= htmlspecialchars($ticket['ticket_number']) ?>
                            </a>
                        </h5>
                        <span class="badge bg-<?= $ticket['status_name'] === 'offen' ? 'success' : 'secondary' ?>">
                            <?= htmlspecialchars($ticket['status_name']) ?>
                        </span>
                    </div>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <?= htmlspecialchars($ticket['title']) ?>
                    </h6>
                    <?php if ($ticket['ki_summary']): ?>
                        <p class="card-text"><?= nl2br(htmlspecialchars($ticket['ki_summary'])) ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Erstellt: <?= (new DateTime($ticket['created_at']))->format('d.m.Y H:i') ?>
                        </small>
                        <span class="badge bg-info">
                            <?= $ticket['comment_count'] ?> Kommentar(e)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
