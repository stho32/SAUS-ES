<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/comment_formatter.php';
require_once __DIR__ . '/includes/ticket_functions.php';
require_once __DIR__ . '/includes/comment_functions.php';
require_once __DIR__ . '/includes/attachment_functions.php';
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

// Bestimme, woher der Benutzer kommt (Standardmäßig index.php)
$referer = isset($_GET['ref']) ? $_GET['ref'] : 'index.php';
// Erlaubte Referer-Werte validieren
if (!in_array($referer, ['index.php', 'follow_up.php'])) {
    $referer = 'index.php';
}

try {
    // Hole Ticket-Details und Kommentare
    $ticket = getTicketDetails($ticketId, getCurrentUsername());
    $comments = getTicketComments($ticketId, getCurrentUsername());
    $attachments = getTicketAttachments($ticketId);
    $allStatus = getAllTicketStatus();
} catch (Exception $e) {
    error_log("Fehler in ticket_view.php: " . $e->getMessage());
    header('Location: error.php?type=error&message=' . urlencode($e->getMessage()));
    exit;
}

// Template-Rendering
$pageTitle = htmlspecialchars($ticket['title']);
require_once 'includes/header.php';
?>
<div class="container mt-4">
    <div class="mb-4">
        <div class="mb-3">
            <h1 class="mb-0"><?= htmlspecialchars($ticket['title']) ?></h1>
            <small class="text-muted">
                Ticket #<?= $ticket['id'] ?>
                <a href="ticket_email.php?id=<?= $ticket['id'] ?>" class="text-muted ms-2" title="E-Mail-Ansicht">
                    <i class="bi bi-envelope"></i>
                </a>
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!$partner): ?>
            <div class="btn-group" role="group" aria-label="Voting">
                <button type="button" 
                        class="btn <?= $ticket['user_vote'] === 'up' ? 'btn-success' : 'btn-outline-success' ?>"
                        onclick="voteTicket(<?= $ticket['id'] ?>, '<?= $ticket['user_vote'] === 'up' ? 'none' : 'up' ?>')"
                        title="<?= $ticket['upvoters'] ? 'Upvotes von: ' . htmlspecialchars($ticket['upvoters']) : 'Keine Upvotes' ?>">
                    <i class="bi bi-hand-thumbs-up"></i>
                    <span class="upvote-count"><?= $ticket['up_votes'] ?></span>
                </button>
                <button type="button" 
                        class="btn <?= $ticket['user_vote'] === 'down' ? 'btn-danger' : 'btn-outline-danger' ?>"
                        onclick="voteTicket(<?= $ticket['id'] ?>, '<?= $ticket['user_vote'] === 'down' ? 'none' : 'down' ?>')"
                        title="<?= $ticket['downvoters'] ? 'Downvotes von: ' . htmlspecialchars($ticket['downvoters']) : 'Keine Downvotes' ?>">
                    <i class="bi bi-hand-thumbs-down"></i>
                    <span class="downvote-count"><?= $ticket['down_votes'] ?></span>
                </button>
            </div>
            <?php endif; ?>
            <?php if ($isMasterLink): ?>
            <a href="ticket_edit.php?id=<?= $ticket['id'] ?>&ref=<?= $referer ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Bearbeiten
            </a>
            <?php endif; ?>
            <a href="<?= $referer ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Zurück
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-person-circle fs-3 text-muted"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Zuständig</h6>
                            <p class="card-text fs-5 mb-0">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#assigneeModal" style="text-decoration: none; color: inherit;">
                                    <?= !empty($ticket['assignee']) ? htmlspecialchars($ticket['assignee']) : '<span class="text-muted">Nicht zugewiesen</span>' ?>
                                    <i class="bi bi-pencil-square ms-2 small"></i>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-flag-fill fs-3" style="color: <?= htmlspecialchars($ticket['status_color']) ?>"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle mb-1 text-muted">Status</h6>
                            <p class="card-text fs-5 mb-0">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#statusModal" style="text-decoration: none; color: inherit;">
                                    <?= htmlspecialchars($ticket['status_name']) ?>
                                    <i class="bi bi-pencil-square ms-2 small"></i>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
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

    <?php if ($ticket['show_on_website']): ?>
    <div class="card mb-4 border-info">
        <div class="card-header bg-info bg-opacity-10 text-info">
            <h5 class="card-title mb-0">
                <i class="bi bi-globe"></i> Website-Informationen
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-0">
                <h6 class="text-muted mb-2">Öffentlicher Kommentar</h6>
                <?php if (!empty($ticket['public_comment'])): ?>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['public_comment'])) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0"><em>Kein öffentlicher Kommentar vorhanden</em></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attachments Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Anhänge</h5>
            
            <!-- Upload Form -->
            <form id="uploadForm" class="mb-3">
                <div class="input-group">
                    <input type="file" class="form-control" id="fileInput" name="file">
                    <button type="submit" class="btn btn-primary">Hochladen</button>
                </div>
                <div id="uploadError" class="alert alert-danger mt-2" style="display: none;"></div>
            </form>
            
            <!-- Attachments Grid -->
            <div class="attachment-grid" id="attachmentGrid">
                <?php if (empty($attachments)): ?>
                    <p class="text-muted">Keine Anhänge vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($attachments as $attachment): ?>
                        <div class="attachment-item" id="attachment-<?= $attachment['id'] ?>">
                            <?php if (str_starts_with($attachment['file_type'], 'image/')): ?>
                                <a href="api/get_attachment.php?id=<?= $attachment['id'] ?>&ticketId=<?= $ticketId ?>" 
                                   target="_blank" class="attachment-preview">
                                    <img src="api/get_attachment.php?id=<?= $attachment['id'] ?>&ticketId=<?= $ticketId ?>" 
                                         alt="<?= htmlspecialchars($attachment['original_filename']) ?>">
                                </a>
                            <?php else: ?>
                                <a href="api/get_attachment.php?id=<?= $attachment['id'] ?>&ticketId=<?= $ticketId ?>" 
                                   target="_blank" class="attachment-file">
                                    <i class="bi bi-file-earmark"></i>
                                </a>
                            <?php endif; ?>
                            <div class="attachment-info">
                                <div class="attachment-name" title="<?= htmlspecialchars($attachment['original_filename']) ?>">
                                    <?= htmlspecialchars($attachment['original_filename']) ?>
                                </div>
                                <div class="attachment-meta">
                                    <small class="text-muted">
                                        <?= htmlspecialchars($attachment['uploaded_by']) ?> - 
                                        <?= date('d.m.Y H:i', strtotime($attachment['upload_date'])) ?>
                                    </small>
                                    <button class="btn btn-sm btn-danger delete-attachment" 
                                            data-id="<?= $attachment['id'] ?>"
                                            title="Löschen">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        <div class="card-body comments-container">
            <?php if (empty($comments)): ?>
            <p class="text-muted text-center my-4">
                <i class="bi bi-chat-square-text"></i><br>
                Noch keine Kommentare vorhanden
            </p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <?= renderComment($comment, $partner !== null) ?>
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

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status ändern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">Neuer Status</label>
                        <select class="form-select" id="statusSelect" name="statusId">
                            <?php foreach ($allStatus as $status): ?>
                            <option value="<?= $status['id'] ?>" <?= $status['id'] == $ticket['status_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Speichern</button>
            </div>
        </div>
    </div>
</div>

<!-- Assignee Modal -->
<div class="modal fade" id="assigneeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Zuständigkeit bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateAssigneeForm">
                    <div class="mb-3">
                        <label for="assigneeInput" class="form-label">Zuständig</label>
                        <input type="text" class="form-control" id="assigneeInput" name="assignee" 
                               value="<?= htmlspecialchars($ticket['assignee'] ?? '') ?>" 
                               placeholder="Name oder Gruppe eingeben">
                        <div class="form-text">
                            Mehrere Zuständige können durch Komma getrennt werden.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="updateAssignee()">Speichern</button>
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
                        <small class="text-muted">
                            Unterstützte Formatierung:<br>
                            **fett** oder __fett__, *kursiv* oder _kursiv_<br>
                            [ ] Checkbox leer, [X] Checkbox ausgefüllt<br>
                            URLs werden automatisch zu Links
                        </small>
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
// Alert-Funktion für Benachrichtigungen
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Füge die Alert-Box am Anfang der Seite ein
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Automatisch nach 5 Sekunden ausblenden
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
}

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
        // Wenn der Button bereits aktiv ist (also bereits gevoted wurde),
        // setzen wir voteType auf 'none' um die Stimme zurückzunehmen
        const voteButton = event.currentTarget;
        const isActive = voteButton.classList.contains(voteType === 'up' ? 'btn-success' : 'btn-danger');
        const finalVoteType = isActive ? 'none' : voteType;

        const response = await fetch('api/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                commentId: commentId,
                voteType: finalVoteType
            })
        });
        
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

async function updateStatus() {
    const statusId = document.getElementById('statusSelect').value;
    const formData = new FormData();
    formData.append('ticketId', '<?= $ticket['id'] ?>');
    formData.append('statusId', statusId);
    
    try {
        const response = await fetch('api/update_ticket_status.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler beim Aktualisieren des Status');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Aktualisieren des Status');
    }
}

async function updateAssignee() {
    const assignee = document.getElementById('assigneeInput').value.trim();
    const formData = new FormData();
    formData.append('ticketId', '<?= $ticket['id'] ?>');
    formData.append('assignee', assignee);
    
    try {
        const response = await fetch('api/update_ticket_assignee.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler beim Aktualisieren der Zuständigkeit');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Fehler beim Aktualisieren der Zuständigkeit');
    }
}

// Kommentar-Bearbeitung
async function startEditComment(commentId) {
    const commentDiv = document.getElementById(`comment-text-${commentId}`);
    const content = commentDiv.getAttribute('data-raw-content') || commentDiv.textContent.trim();
    
    // Erstelle Bearbeitungsformular
    commentDiv.innerHTML = `
        <div class="mb-2">
            <textarea class="form-control" id="edit-comment-${commentId}" rows="3">${content}</textarea>
        </div>
        <div class="mb-2">
            <small class="text-muted">
                Unterstützte Formatierung:<br>
                **fett** oder __fett__, *kursiv* oder _kursiv_<br>
                [ ] Checkbox leer, [X] Checkbox ausgefüllt<br>
                URLs werden automatisch zu Links
            </small>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" onclick="saveCommentEdit(${commentId})">Speichern</button>
            <button class="btn btn-secondary btn-sm" onclick="cancelCommentEdit(${commentId})">Abbrechen</button>
        </div>
    `;
}

async function saveCommentEdit(commentId) {
    const content = document.getElementById(`edit-comment-${commentId}`).value;
    const formData = new FormData();
    formData.append('commentId', commentId);
    formData.append('content', content);
    
    try {
        // Speichere den bearbeiteten Kommentar und hole das formatierte HTML
        const response = await fetch('api/edit_comment.php', {
            method: 'POST',
            credentials: 'include',  // Wichtig: Session-Cookie mitsenden
            body: formData
        });
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Fehler beim Speichern des Kommentars');
        }

        // Aktualisiere die Anzeige
        const commentDiv = document.getElementById(`comment-text-${commentId}`);
        commentDiv.setAttribute('data-raw-content', content);
        commentDiv.innerHTML = data.formattedContent;

        // Zeige eine Erfolgsmeldung
        showAlert('success', 'Kommentar wurde aktualisiert');
        
        // Lade die Seite neu um den "bearbeitet" Status zu aktualisieren
        setTimeout(() => location.reload(), 1000);
        
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message);
    }
}

async function cancelCommentEdit(commentId) {
    const commentDiv = document.getElementById(`comment-text-${commentId}`);
    const rawContent = commentDiv.getAttribute('data-raw-content');
    
    // Hole das formatierte HTML vom Server
    const formData = new FormData();
    formData.append('content', rawContent);
    
    try {
        const response = await fetch('api/format_comment.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            commentDiv.innerHTML = data.formattedContent;
        } else {
            throw new Error('Fehler beim Formatieren des Kommentars');
        }
    } catch (error) {
        console.error('Error:', error);
        commentDiv.innerHTML = rawContent; // Fallback zur unformatierten Anzeige
    }
}

// Ticket Voting
async function voteTicket(ticketId, value) {
    try {
        const formData = new FormData();
        formData.append('ticketId', ticketId);
        formData.append('value', value);

        const response = await fetch('api/vote_ticket.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            // Update vote counts
            const container = document.querySelector('.btn-group[role="group"][aria-label="Voting"]');
            const upButton = container.querySelector('.btn:first-child');
            const downButton = container.querySelector('.btn:last-child');
            
            // Update counts
            upButton.querySelector('.upvote-count').textContent = data.stats.up_votes;
            downButton.querySelector('.downvote-count').textContent = data.stats.down_votes;
            
            // Update button styles
            upButton.className = `btn ${value === 'up' ? 'btn-success' : 'btn-outline-success'}`;
            downButton.className = `btn ${value === 'down' ? 'btn-danger' : 'btn-outline-danger'}`;
            
            // Update tooltips
            upButton.title = data.stats.upvoters ? `Upvotes von: ${data.stats.upvoters}` : 'Keine Upvotes';
            downButton.title = data.stats.downvoters ? `Downvotes von: ${data.stats.downvoters}` : 'Keine Downvotes';
            
            // Update onclick handlers
            upButton.setAttribute('onclick', `voteTicket(${ticketId}, '${value === 'up' ? 'none' : 'up'}')`);
            downButton.setAttribute('onclick', `voteTicket(${ticketId}, '${value === 'down' ? 'none' : 'down'}')`);
        } else {
            alert('Fehler beim Abstimmen: ' + data.error);
        }
    } catch (error) {
        console.error('Fehler beim Abstimmen:', error);
        alert('Fehler beim Abstimmen: ' + error.message);
    }
}

// Hilfsfunktionen
function nl2br(str) {
    return str.replace(/\n/g, '<br>');
}
    
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle für Kommentar-Sichtbarkeit
document.getElementById('showAllComments').addEventListener('change', function() {
    document.querySelector('.comments-container').classList.toggle('show-all-comments', this.checked);
});

// Initialisiere den Zustand basierend auf localStorage
document.addEventListener('DOMContentLoaded', function() {
    const showAllComments = localStorage.getItem('showAllComments') === 'true';
    const checkbox = document.getElementById('showAllComments');
    const container = document.querySelector('.comments-container');
    
    checkbox.checked = showAllComments;
    if (showAllComments) {
        container.classList.add('show-all-comments');
    }
});

// Speichere den Zustand im localStorage
document.getElementById('showAllComments').addEventListener('change', function() {
    localStorage.setItem('showAllComments', this.checked);
});

// Attachment handling
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const fileInput = document.getElementById('fileInput');
    const errorDiv = document.getElementById('uploadError');
    
    try {
        errorDiv.style.display = 'none';
        
        if (!fileInput.files.length) {
            throw new Error('Bitte wählen Sie eine Datei aus.');
        }
        
        formData.append('file', fileInput.files[0]);
        formData.append('ticketId', <?= $ticketId ?>);
        
        const response = await fetch('api/upload_attachment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.error || 'Upload fehlgeschlagen');
        }
    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.style.display = 'block';
    }
});

document.querySelectorAll('.delete-attachment').forEach(button => {
    button.addEventListener('click', async function() {
        if (!confirm('Möchten Sie diese Datei wirklich löschen?')) {
            return;
        }
        
        const attachmentId = this.dataset.id;
        const formData = new FormData();
        formData.append('id', attachmentId);
        formData.append('ticketId', <?= $ticketId ?>);
        
        try {
            const response = await fetch('api/delete_attachment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('attachment-' + attachmentId).remove();
            } else {
                alert(result.error || 'Löschen fehlgeschlagen');
            }
        } catch (error) {
            alert('Löschen fehlgeschlagen');
        }
    });
});
</script>

<style>
.attachment-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.attachment-item {
    flex: 0 0 200px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.attachment-preview {
    display: block;
    height: 150px;
    overflow: hidden;
    background: #f8f9fa;
    text-align: center;
}

.attachment-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.attachment-file {
    display: flex;
    height: 150px;
    background: #f8f9fa;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    text-decoration: none;
}

.attachment-file i {
    font-size: 3rem;
}

.attachment-info {
    padding: 0.5rem;
}

.attachment-name {
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.25rem;
}

.attachment-meta small {
    font-size: 0.8rem;
}

.delete-attachment {
    padding: 0.1rem 0.3rem;
}

.comment-hidden {
    opacity: 0.5;
    display: none;
}

.show-all-comments .comment-hidden {
    display: block;
}

.btn-outline-secondary.voted-up,
.btn-outline-success.voted-up {
    background-color: #d1e7dd;
    border-color: #198754;
    color: #198754;
}

.btn-outline-secondary.voted-down,
.btn-outline-danger.voted-down {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #dc3545;
}

.vote-count {
    margin-left: 4px;
}
</style>

<?php require_once 'includes/footer.php'; ?>
