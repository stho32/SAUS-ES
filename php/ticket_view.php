<?php
declare(strict_types=1);

require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Authentifizierung
$partnerLink = $_GET['partner'] ?? null;
$partner = $partnerLink ? isPartnerLink($partnerLink) : null;
$isMasterLink = isset($_SESSION['master_code']);

if (!$partner && !$isMasterLink) {
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
               (SELECT partner_list FROM partners WHERE ticket_id = t.id LIMIT 1) as partner_list,
               (SELECT partner_link FROM partners WHERE ticket_id = t.id LIMIT 1) as partner_link
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    // Hole alle Kommentare mit Voting-Statistiken und Voting-Details
    $stmt = $db->prepare("
        SELECT c.*, cs.up_votes, cs.down_votes,
               COALESCE(cv.value, 'none') as user_vote,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM comment_votes
                   WHERE comment_id = c.id AND value = 'up'
               ) as upvoters,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM comment_votes
                   WHERE comment_id = c.id AND value = 'down'
               ) as downvoters,
               c.is_visible,
               c.hidden_by,
               c.hidden_at
        FROM comments c
        LEFT JOIN comment_statistics cs ON c.id = cs.comment_id
        LEFT JOIN comment_votes cv ON c.id = cv.comment_id AND cv.username = ?
        WHERE c.ticket_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([getCurrentUsername(), $ticketId]);
    $comments = $stmt->fetchAll();

    // Hole alle verfügbaren Status
    $stmt = $db->query("SELECT * FROM ticket_status ORDER BY name");
    $allStatus = $stmt->fetchAll();

} catch (Exception $e) {
    header('Location: error.php?type=error&message=' . urlencode($e->getMessage()));
    exit;
}

// Template-Rendering
$pageTitle = htmlspecialchars($ticket['title']);
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($ticket['title']) ?></h1>
            <small class="text-muted">Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></small>
        </div>
        <div class="d-flex gap-2">
            <?php if ($isMasterLink): ?>
            <a href="ticket_edit.php?id=<?= $ticket['id'] ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Bearbeiten
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    <?php if ($ticket['ki_summary']): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-robot"></i> KI-Zusammenfassung
            </h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($ticket['ki_summary'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Beschreibung</h5>
            <div class="ticket-description">
                <?= nl2br(htmlspecialchars($ticket['description'])) ?>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Kommentare</h5>
                <div class="d-flex gap-2">
                    <?php if (!$partner): ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showAllComments">
                        <label class="form-check-label" for="showAllComments">Alle Kommentare anzeigen</label>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCommentModal">
                        <i class="bi bi-plus-lg"></i> Kommentar hinzufügen
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($comments)): ?>
            <p class="text-muted text-center my-4">
                <i class="bi bi-chat-square-text"></i><br>
                Noch keine Kommentare vorhanden
            </p>
            <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="comment mb-4 <?= !$comment['is_visible'] ? 'comment-hidden' : '' ?>" 
                 data-visible="<?= $comment['is_visible'] ? 'true' : 'false' ?>">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                        <small class="text-muted">
                            <?= formatDateTime($comment['created_at']) ?>
                            <?php if (!$comment['is_visible']): ?>
                            <span class="text-danger">
                                (Ausgeblendet von <?= htmlspecialchars($comment['hidden_by']) ?> 
                                am <?= formatDateTime($comment['hidden_at']) ?>)
                            </span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php if (!$partner): ?>
                    <div class="btn-group" role="group">
                        <button type="button" 
                                class="btn btn-sm btn-outline-<?= $comment['user_vote'] === 'up' ? 'success' : 'secondary' ?>"
                                onclick="voteComment(<?= $comment['id'] ?>, 'up')">
                            <i class="bi bi-hand-thumbs-up"></i> 
                            <span class="vote-count"><?= $comment['up_votes'] ?></span>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-outline-<?= $comment['user_vote'] === 'down' ? 'danger' : 'secondary' ?>"
                                onclick="voteComment(<?= $comment['id'] ?>, 'down')">
                            <i class="bi bi-hand-thumbs-down"></i>
                            <span class="vote-count"><?= $comment['down_votes'] ?></span>
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                onclick="toggleCommentVisibility(<?= $comment['id'] ?>, <?= $comment['is_visible'] ? 'false' : 'true' ?>)">
                            <i class="bi bi-eye<?= $comment['is_visible'] ? '-slash' : '' ?>"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
                <?php 
                $upvoters = $comment['upvoters'] ? explode(',', $comment['upvoters']) : [];
                $downvoters = $comment['downvoters'] ? explode(',', $comment['downvoters']) : [];
                if (!empty($upvoters) || !empty($downvoters)): 
                ?>
                <div class="text-end mt-2">
                    <small class="text-muted">
                        <?php
                        $parts = [];
                        if (!empty($upvoters)) {
                            $parts[] = 'dafür: ' . implode(', ', array_map('htmlspecialchars', $upvoters));
                        }
                        if (!empty($downvoters)) {
                            $parts[] = 'dagegen: ' . implode(', ', array_map('htmlspecialchars', $downvoters));
                        }
                        echo implode(' / ', $parts);
                        ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$partner): ?>
    <div class="text-center mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommentModal">
            <i class="bi bi-plus-lg"></i> Neuen Kommentar hinzufügen
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Partner Modal -->
<div class="modal fade" id="partnerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Partner verwalten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="partnerForm">
                    <div class="mb-3">
                        <label for="partnerList" class="form-label">Partner-Liste</label>
                        <textarea class="form-control" id="partnerList" rows="3"
                            placeholder="Liste der Partner (z.B. Name, Abteilung)"><?= htmlspecialchars($ticket['partner_list'] ?? '') ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="savePartners()">Speichern</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Comment Modal -->
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
                <button type="button" class="btn btn-primary" onclick="addComment()">Speichern</button>
            </div>
        </div>
    </div>
</div>

<script>
async function copyPartnerLink(link) {
    try {
        await navigator.clipboard.writeText(window.location.origin + window.location.pathname + '?partner=' + link);
        alert('Partner-Link wurde in die Zwischenablage kopiert!');
    } catch (err) {
        alert('Fehler beim Kopieren des Links: ' + err);
    }
}

async function updateTicket(field, value) {
    try {
        const response = await fetch('api/update_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticketId: <?= $ticketId ?>,
                field: field,
                value: value
            })
        });
        
        if (!response.ok) {
            throw new Error('Netzwerkfehler');
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
        location.reload(); // Lade die Seite neu, um die alten Werte wiederherzustellen
    }
}

async function savePartners() {
    const partnerList = document.getElementById('partnerList').value;
    
    try {
        const response = await fetch('api/update_partners.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticketId: <?= $ticketId ?>,
                partnerList: partnerList
            })
        });
        
        if (!response.ok) {
            throw new Error('Netzwerkfehler');
        }
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
    }
}

async function addComment() {
    const content = document.getElementById('commentContent').value;
    
    try {
        const response = await fetch('api/add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: <?= $ticketId ?>,
                content: content
            })
        });
        
        if (!response.ok) {
            throw new Error('Netzwerkfehler');
        }
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
    }
}

async function voteComment(commentId, voteType) {
    try {
        const response = await fetch('api/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commentId: commentId,
                voteType: voteType
            })
        });
        
        if (!response.ok) {
            throw new Error('Netzwerkfehler');
        }
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        alert('Fehler beim Abstimmen: ' + error.message);
    }
}

async function toggleCommentVisibility(commentId, visible) {
    try {
        const response = await fetch('api/toggle_comment_visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commentId: commentId,
                visible: visible
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message || 'Unbekannter Fehler');
        }
    } catch (error) {
        alert('Fehler beim Ändern der Sichtbarkeit: ' + error.message);
    }
}

// Toggle für Kommentar-Sichtbarkeit
document.getElementById('showAllComments').addEventListener('change', function() {
    document.querySelector('.card-body').classList.toggle('show-all-comments', this.checked);
});
</script>

<style>
.comment-hidden {
    opacity: 0.5;
    display: none;
}

.show-all-comments .comment-hidden {
    display: block;
}
</style>

<?php require_once 'includes/footer.php'; ?>
