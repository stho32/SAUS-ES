<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/comment_formatter.php';

// Prüfe Master-Link
requireMasterLink();

// Hole Benutzernamen
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

// Standardzeitraum: Anfang des Vormonats bis zum ersten Montag des aktuellen Monats
$now = new DateTime();
$firstDayOfCurrentMonth = new DateTime('first day of this month');
$firstMondayOfCurrentMonth = new DateTime('first monday of this month');
$firstDayOfLastMonth = (clone $firstDayOfCurrentMonth)->modify('-1 month');

// Hole Filter-Parameter
$fromDate = isset($_GET['from']) ? new DateTime($_GET['from']) : $firstDayOfLastMonth;
$toDate = isset($_GET['to']) ? new DateTime($_GET['to']) : $firstMondayOfCurrentMonth;

// Stelle sicher, dass die Daten im korrekten Format sind
$fromStr = $fromDate->format('Y-m-d');
$toStr = $toDate->format('Y-m-d');

// Hole die Daten aus der Datenbank
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT DISTINCT 
        t.id as ticket_id,
        t.title,
        t.description,
        t.created_at as ticket_created,
        ts.name as status_name,
        c.id as comment_id,
        c.content as comment_content,
        c.username as comment_username,
        c.created_at as comment_created
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    JOIN comments c ON t.id = c.ticket_id
    WHERE (DATE(c.created_at) BETWEEN ? AND ?)
    ORDER BY t.id ASC, c.created_at ASC
");

$stmt->execute([$fromStr, $toStr]);
$results = $stmt->fetchAll();

// Gruppiere die Ergebnisse nach Tickets
$tickets = [];
foreach ($results as $row) {
    $ticketId = $row['ticket_id'];
    if (!isset($tickets[$ticketId])) {
        $tickets[$ticketId] = [
            'id' => $ticketId,
            'title' => $row['title'],
            'description' => $row['description'],
            'created_at' => $row['ticket_created'],
            'status' => $row['status_name'],
            'comments' => []
        ];
    }
    $tickets[$ticketId]['comments'][] = [
        'id' => $row['comment_id'],
        'content' => $row['comment_content'],
        'username' => $row['comment_username'],
        'created_at' => $row['comment_created']
    ];
}

$pageTitle = 'SAUS-News-Bericht';
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-muted">
                <i class="bi bi-info-circle"></i>
                Dieser Bericht dient als Datengrundlage für die SAUS-News und zeigt alle Ticket-Aktivitäten im gewählten Zeitraum.
            </p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    <!-- Zeitraum-Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label for="from" class="form-label">Von:</label>
                    <input type="date" class="form-control" id="from" name="from" 
                           value="<?= htmlspecialchars($fromStr) ?>">
                </div>
                <div class="col-md-5">
                    <label for="to" class="form-label">Bis:</label>
                    <input type="date" class="form-control" id="to" name="to" 
                           value="<?= htmlspecialchars($toStr) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Aktualisieren
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Keine Aktivitäten im gewählten Zeitraum gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="text-decoration-none">
                                #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
                            </a>
                        </h5>
                        <span class="badge bg-secondary"><?= htmlspecialchars($ticket['status']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">
                            Erstellt am <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                        </small>
                        <div class="mt-2">
                            <?= nl2br(htmlspecialchars($ticket['description'])) ?>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3">Kommentare im Zeitraum:</h6>
                    <?php foreach ($ticket['comments'] as $comment): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                <small class="text-muted">
                                    <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                </small>
                            </div>
                            <div class="mt-1">
                                <?= formatComment($comment['content']) ?>
                                <hr>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
