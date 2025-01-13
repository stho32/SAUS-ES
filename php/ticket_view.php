<?php
declare(strict_types=1);

require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Authentifizierung
$partnerLink = $_GET['partner'] ?? null;
$partner = $partnerLink ? isPartnerLink($partnerLink) : null;

if (!$partner && !isset($_SESSION['master_code'])) {
    header('Location: error.php?type=unauthorized');
    exit;
}

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$ticketId) {
    header('Location: error.php?type=not_found');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Hole Ticket-Details mit Partner-Liste
    $stmt = $db->prepare("
        SELECT t.*, ts.name as status_name, 
               (SELECT partner_list FROM partners WHERE ticket_id = t.id LIMIT 1) as partner_list
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    // Hole alle Kommentare mit Voting-Statistiken
    $stmt = $db->prepare("
        SELECT c.*, cs.up_votes, cs.down_votes,
               COALESCE(cv.value, 'none') as user_vote
        FROM comments c
        LEFT JOIN comment_statistics cs ON c.id = cs.comment_id
        LEFT JOIN comment_votes cv ON c.id = cv.comment_id AND cv.username = ?
        WHERE c.ticket_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([getCurrentUsername(), $ticketId]);
    $comments = $stmt->fetchAll();

} catch (Exception $e) {
    header('Location: error.php?type=error&message=' . urlencode($e->getMessage()));
    exit;
}

// Template-Rendering
$pageTitle = htmlspecialchars($ticket['title']);
require_once 'templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1><?= htmlspecialchars($ticket['title']) ?></h1>
        <span class="badge bg-<?= $ticket['status_name'] === 'offen' ? 'success' : 'secondary' ?>">
            <?= htmlspecialchars($ticket['status_name']) ?>
        </span>
    </div>

    <?php if ($ticket['partner_list']): ?>
    <div class="alert alert-info">
        <strong>Partner:</strong> <?= htmlspecialchars($ticket['partner_list']) ?>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Kommentare</h5>
                <?php if (!$partner): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCommentModal">
                    Kommentar hinzufügen
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php foreach ($comments as $comment): ?>
            <div class="comment mb-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                        <small class="text-muted">
                            <?= formatDateTime($comment['created_at']) ?>
                        </small>
                    </div>
                    <?php if (!$partner): ?>
                    <div class="btn-group" role="group">
                        <button type="button" 
                                class="btn btn-sm btn-outline-success <?= $comment['user_vote'] === 'up' ? 'active' : '' ?>"
                                onclick="voteComment(<?= $comment['id'] ?>, 'up')">
                            👍 <span class="vote-count"><?= $comment['up_votes'] ?></span>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger <?= $comment['user_vote'] === 'down' ? 'active' : '' ?>"
                                onclick="voteComment(<?= $comment['id'] ?>, 'down')">
                            👎 <span class="vote-count"><?= $comment['down_votes'] ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$partner): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Partner-Link erstellen</h5>
        </div>
        <div class="card-body">
            <form id="partnerForm" class="row g-3">
                <div class="col-md-6">
                    <label for="partnerName" class="form-label">Partner Name</label>
                    <input type="text" class="form-control" id="partnerName" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Partner-Link generieren</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal für neuen Kommentar -->
<div class="modal fade" id="addCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Neuer Kommentar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="commentForm">
                    <div class="mb-3">
                        <label for="commentContent" class="form-label">Kommentar</label>
                        <textarea class="form-control" id="commentContent" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="submitComment()">Kommentar speichern</button>
            </div>
        </div>
    </div>
</div>

<script>
const ticketId = <?= $ticketId ?>;

async function voteComment(commentId, voteType) {
    try {
        const response = await fetch('api/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                comment_id: commentId,
                vote_type: voteType
            })
        });
        
        if (!response.ok) throw new Error('Fehler beim Abstimmen');
        
        // Aktualisiere die Ansicht
        location.reload();
    } catch (error) {
        alert(error.message);
    }
}

async function submitComment() {
    const content = document.getElementById('commentContent').value;
    if (!content) return;

    try {
        const response = await fetch('api/add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                content: content
            })
        });

        if (!response.ok) throw new Error('Fehler beim Speichern des Kommentars');

        // Schließe Modal und aktualisiere die Ansicht
        bootstrap.Modal.getInstance(document.getElementById('addCommentModal')).hide();
        location.reload();
    } catch (error) {
        alert(error.message);
    }
}

// Partner-Link-Formular
document.getElementById('partnerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const partnerName = document.getElementById('partnerName').value;
    
    try {
        const response = await fetch('api/create_partner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                partner_name: partnerName
            })
        });

        if (!response.ok) throw new Error('Fehler beim Erstellen des Partner-Links');

        const data = await response.json();
        if (data.success) {
            alert(`Partner-Link erstellt: ${window.location.origin}/ticket_view.php?partner=${data.partner_link}`);
            location.reload();
        }
    } catch (error) {
        alert(error.message);
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>
