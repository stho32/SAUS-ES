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
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($ticket['title']) ?></h1>
            <div class="text-muted">
                Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?>
                <span class="mx-2">•</span>
                Erstellt: <?= formatDateTime($ticket['created_at']) ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-<?= $ticket['status_name'] === 'offen' ? 'success' : 'secondary' ?> fs-6">
                <?= htmlspecialchars($ticket['status_name']) ?>
            </span>
            <?php if ($isMasterLink): ?>
            <a href="ticket_edit.php?id=<?= $ticketId ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> Bearbeiten
            </a>
            <?php endif; ?>
            <?php if (!$partner): ?>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($ticket['partner_link']): ?>
                    <li>
                        <button class="dropdown-item" onclick="copyPartnerLink('<?= htmlspecialchars($ticket['partner_link']) ?>')">
                            <i class="bi bi-link-45deg"></i> Partner-Link kopieren
                        </button>
                    </li>
                    <?php endif; ?>
                    <li>
                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#partnerModal">
                            <i class="bi bi-people"></i> Partner verwalten
                        </button>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
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
                <?php if (!$partner): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCommentModal">
                    <i class="bi bi-plus-lg"></i> Kommentar hinzufügen
                </button>
                <?php endif; ?>
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
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
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
                ticketId: <?= $ticketId ?>,
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
</script>

<?php require_once 'includes/footer.php'; ?>
