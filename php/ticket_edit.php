<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';

require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Nur Master-Link darf Tickets bearbeiten
if (!isset($_SESSION['master_code'])) {
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
            <a href="ticket_view.php?id=<?= $ticketId ?>" class="btn btn-outline-secondary">
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
        assignee: document.getElementById('assignee').value
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

<?php require_once 'includes/footer.php'; ?>
