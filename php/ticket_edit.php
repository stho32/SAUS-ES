<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/auth_check.php';

require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Prüfe Authentifizierung
requireMasterLink();

// Stelle sicher, dass ein Benutzername gesetzt ist
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$ticketId) {
    header('Location: error.php?type=not_found');
    exit;
}

// Bestimme den ursprünglichen Referer
$referer = isset($_GET['ref']) ? $_GET['ref'] : 'index.php';
// Erlaubte Referer-Werte validieren
if (!in_array($referer, ['index.php', 'follow_up.php'])) {
    $referer = 'index.php';
}

$db = Database::getInstance()->getConnection();

try {
    // Hole Ticket-Details
    $stmt = $db->prepare("
        SELECT t.*, ts.name as status_name
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    // Hole alle verfügbaren Status
    $stmt = $db->query("SELECT * FROM ticket_status ORDER BY name");
    $allStatus = $stmt->fetchAll();

} catch (Exception $e) {
    header('Location: error.php?type=error&message=' . urlencode($e->getMessage()));
    exit;
}

$pageTitle = "Ticket #" . $ticket['id'] . " bearbeiten";
require_once 'includes/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Ticket bearbeiten</h1>
            <small class="text-muted">Ticket #<?= $ticket['id'] ?></small>
        </div>
        <div>
            <a href="ticket_view.php?id=<?= $ticketId ?>&ref=<?= $referer ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Abbrechen
            </a>
            <button type="button" class="btn btn-primary ms-2" id="saveButton" onclick="updateTicket()">
                <i class="bi bi-check-lg"></i> Speichern
            </button>
        </div>
    </div>

    <form id="ticketForm" class="needs-validation" novalidate>
        <input type="hidden" id="ticketId" value="<?= $ticketId ?>">
        
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Allgemeine Informationen</h5>
                        
                        <div class="mb-3">
                            <label for="ticketNumber" class="form-label">Ticket-Nummer</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="ticketNumber" 
                                   name="ticket_number" 
                                   value="#<?= $ticket['id'] ?>"
                                   readonly>
                            <div class="form-text">Die Ticket-Nummer kann nicht geändert werden.</div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Titel</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   value="<?= htmlspecialchars($ticket['title']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Beschreibung</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      rows="5"
                                      required><?php echo trim(htmlspecialchars($ticket['description'])); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="assignee" class="form-label">Zuständige Bearbeiter</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="assignee" 
                                   name="assignee" 
                                   value="<?= htmlspecialchars($ticket['assignee'] ?? '') ?>" 
                                   maxlength="200"
                                   placeholder="Namen der zuständigen Bearbeiter">
                        </div>

                        <div class="mb-3">
                            <label for="affectedNeighbors" class="form-label">Anzahl betroffener Nachbarn</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="affectedNeighbors" 
                                   name="affected_neighbors" 
                                   value="<?= $ticket['affected_neighbors'] !== null ? htmlspecialchars((string)$ticket['affected_neighbors']) : '' ?>" 
                                   min="0"
                                   placeholder="Anzahl der betroffenen Nachbarn">
                            <div class="form-text">Leer lassen wenn unbekannt.</div>
                        </div>

                        <?php if (!empty($ticket['ki_summary'])): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <i class="bi bi-robot"></i> KI-Zusammenfassung
                                </h6>
                                <p class="card-text"><?= nl2br(htmlspecialchars($ticket['ki_summary'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($ticket['ki_interim'])): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <i class="bi bi-robot"></i> KI-Zwischenstand
                                </h6>
                                <p class="card-text"><?= nl2br(htmlspecialchars($ticket['ki_interim'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Status & Metadaten</h5>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" required>
                                <?php foreach ($allStatus as $status): ?>
                                <option value="<?= $status['id'] ?>" 
                                        <?= $status['id'] == $ticket['status_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="followUpDate" class="form-label">Wiedervorlagedatum</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="followUpDate" 
                                       value="<?= $ticket['follow_up_date'] ? date('Y-m-d', strtotime($ticket['follow_up_date'])) : '' ?>">
                                <button class="btn btn-outline-secondary" type="button" id="clearFollowUpDate">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="form-text">Setzen Sie ein Datum, an dem dieses Ticket erneut betrachtet werden sollte.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="doNotTrack" 
                                       <?= isset($ticket['do_not_track']) && $ticket['do_not_track'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="doNotTrack">
                                    Nicht verfolgen
                                </label>
                                <div class="form-text">Ticket wird in der "Dran bleiben"-Übersicht nicht angezeigt.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showOnWebsite" 
                                       <?= $ticket['show_on_website'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="showOnWebsite">
                                    Auf Website anzeigen
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="publicComment" class="form-label">Öffentlicher Kommentar</label>
                            <textarea class="form-control" 
                                    id="publicComment" 
                                    rows="3"
                                    placeholder="Dieser Kommentar wird auf der Website angezeigt"><?= htmlspecialchars($ticket['public_comment'] ?? '') ?></textarea>
                            <div class="form-text">Dieser Text wird auf der Website neben dem Ticket-Titel angezeigt.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
async function updateTicket() {
    const data = {
        ticketId: document.getElementById('ticketId').value,
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        statusId: document.getElementById('status').value,
        assignee: document.getElementById('assignee').value,
        showOnWebsite: document.getElementById('showOnWebsite').checked,
        publicComment: document.getElementById('publicComment').value,
        affectedNeighbors: document.getElementById('affectedNeighbors').value === '' ? null : parseInt(document.getElementById('affectedNeighbors').value),
        followUpDate: document.getElementById('followUpDate').value,
        doNotTrack: document.getElementById('doNotTrack').checked
    };

    try {
        const response = await fetch('api/update_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (result.success) {
            window.location.href = 'ticket_view.php?id=' + data.ticketId;
        } else {
            alert('Fehler beim Speichern: ' + result.message);
        }
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Speichern des Tickets');
    }
}
</script>

<script>
// Funktion zum Löschen des Wiedervorlagedatums
document.getElementById('clearFollowUpDate').addEventListener('click', function() {
    document.getElementById('followUpDate').value = '';
});
</script>

<?php require_once 'includes/footer.php'; ?>
