<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Database.php';

// Prüfe Master-Link
requireMasterLink();

// Hole Ticket-ID aus der URL
$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$ticketId) {
    header('Location: index.php');
    exit;
}

// Hole Ticket-Daten
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT t.*, ts.name as status_name
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: index.php');
    exit;
}

// Hole Kommentare
$stmt = $db->prepare("
    SELECT *
    FROM comments
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticketId]);
$comments = $stmt->fetchAll();

// Header einbinden
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="emailSubject" class="form-label">Betreff</label>
                        <input type="text" 
                               class="form-control" 
                               id="emailSubject" 
                               value="<?= htmlspecialchars($ticket['title']) ?> [Unser Vorgang #<?= $ticket['id'] ?>]" 
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Inhalt</label>
                        <div class="card">
                            <div class="card-body bg-light">
                                <div class="mb-4">
                                    <strong>Status:</strong> <?= htmlspecialchars($ticket['status_name']) ?><br>
                                    <?php if ($ticket['affected_neighbors'] !== null): ?>
                                    <strong>Betroffene Nachbarn:</strong> <?= (int)$ticket['affected_neighbors'] ?><br>
                                    <?php endif; ?>
                                    <strong>Erstellt am:</strong> <?= (new DateTime($ticket['created_at']))->format('d.m.Y H:i') ?>
                                </div>

                                <div class="mb-4">
                                    <div style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></div>
                                </div>

                                <?php if (!empty($comments)): ?>
                                <hr>
                                <div class="mb-3">
                                    <strong>Verlauf:</strong>
                                </div>
                                <?php foreach ($comments as $comment): ?>
                                <div class="mb-3">
                                    <strong><?= htmlspecialchars($comment['username']) ?></strong> 
                                    (<?= (new DateTime($comment['created_at']))->format('d.m.Y H:i') ?>):<br>
                                    <div style="white-space: pre-wrap;"><?= htmlspecialchars($comment['content']) ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
