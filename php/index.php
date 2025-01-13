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

// Hole alle Status für den Filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM ticket_status WHERE is_active = 1 ORDER BY sort_order, name");
$allStatus = $stmt->fetchAll();

// Bestimme aktive Filter
$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
$showClosed = isset($_GET['closed']) && $_GET['closed'] === '1';

// Wenn keine Filter gesetzt sind, setze Standardfilter
$isFirstVisit = !isset($_GET['filter_applied']);
$selectedStatus = [];

if ($isFirstVisit) {
    // Standardmäßig alle aktiven Status auswählen, die nicht archiviert oder geschlossen sind
    foreach ($allStatus as $status) {
        if (!$status['is_archived'] && !$status['is_closed']) {
            $selectedStatus[] = $status['id'];
        }
    }
} else {
    // Ansonsten die ausgewählten Status aus der Request nehmen
    $selectedStatus = isset($_GET['status']) ? (array)$_GET['status'] : [];
}

// Baue SQL-Query
$sql = "
    SELECT t.*, ts.name as status_name, ts.is_archived, ts.is_closed,
           (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as comment_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE 1=1
";

$params = [];

// Füge Filter hinzu
if (!$showArchived) {
    $sql .= " AND ts.is_archived = 0";
}
if (!$showClosed) {
    $sql .= " AND ts.is_closed = 0";
}
if (!empty($selectedStatus)) {
    $placeholders = str_repeat('?,', count($selectedStatus) - 1) . '?';
    $sql .= " AND ts.id IN ($placeholders)";
    $params = array_merge($params, $selectedStatus);
}

if ($search !== '') {
    $sql .= " AND (
        t.ticket_number LIKE :search 
        OR t.title LIKE :search 
        OR t.ki_summary LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY t.created_at DESC";

// Führe Query aus
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Ticket-Übersicht</h1>
        <a href="<?= $basePath ?>/create_ticket.php" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Neues Ticket
        </a>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="filter_applied" value="1">
                <div class="col-md-6">
                    <label class="form-label">Status-Filter</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <?php foreach ($allStatus as $status): ?>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="status[]" 
                                   value="<?= $status['id'] ?>"
                                   id="status_<?= $status['id'] ?>"
                                   <?= in_array($status['id'], $selectedStatus) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="status_<?= $status['id'] ?>">
                                <?= htmlspecialchars($status['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Weitere Filter</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="archived" 
                                   value="1"
                                   id="show_archived"
                                   <?= $showArchived ? 'checked' : '' ?>>
                            <label class="form-check-label" for="show_archived">
                                Archivierte anzeigen
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="closed" 
                                   value="1"
                                   id="show_closed"
                                   <?= $showClosed ? 'checked' : '' ?>>
                            <label class="form-check-label" for="show_closed">
                                Geschlossene anzeigen
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter anwenden
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Filter zurücksetzen
                    </a>
                    <?php if (!$isFirstVisit): ?>
                    <span class="ms-2 text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Filter aktiv
                    </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Ticket-Liste -->
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

        <?php if (empty($tickets)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Keine Tickets gefunden. Passen Sie die Filter an oder erstellen Sie ein neues Ticket.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
