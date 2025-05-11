<?php
declare(strict_types=1);
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

$error = null;
$success = false;
$pageTitle = 'Neues Ticket erstellen';

// Hole alle Status für das Formular
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM ticket_status WHERE is_active = 1 AND NOT is_archived AND NOT is_closed ORDER BY sort_order, name");
$allStatus = $stmt->fetchAll();

// Verarbeite POST-Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $statusId = $_POST['status'] ?? null;
        $assignee = trim($_POST['assignee'] ?? '');

        // Validiere Eingaben
        if (empty($title)) {
            throw new Exception("Bitte geben Sie einen Titel ein.");
        }
        if (empty($description)) {
            throw new Exception("Bitte geben Sie eine Beschreibung ein.");
        }
        if (!$statusId) {
            throw new Exception("Bitte wählen Sie einen Status aus.");
        }

        // Generiere Ticket-Nummer (wird für Kompatibilität beibehalten)
        $ticketNumber = 'T' . date('Ym') . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Speichere Ticket
        $stmt = $db->prepare("
            INSERT INTO tickets (
                ticket_number, 
                title, 
                description, 
                status_id, 
                assignee,
                show_on_website,
                public_comment
            ) 
            VALUES (?, ?, ?, ?, ?, FALSE, NULL)
        ");
        
        $stmt->execute([$ticketNumber, $title, $description, $statusId, $assignee]);
        $ticketId = $db->lastInsertId();

        // Füge Status-Kommentar hinzu
        require_once 'includes/comment_functions.php';
        addStatusChangeComment((int)$ticketId, (int)$statusId);

        $db->commit();

        // Leite zur Ticket-Ansicht weiter
        header("Location: ticket_view.php?id=$ticketId");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

require_once 'includes/header.php';
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Abbrechen
            </a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="title" class="form-label">Titel</label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Beschreibung</label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="5" 
                              required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">Bitte wählen...</option>
                        <?php foreach ($allStatus as $status): ?>
                            <option value="<?= $status['id'] ?>" 
                                    <?= (isset($_POST['status']) && $_POST['status'] == $status['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="assignee" class="form-label">Bearbeiter</label>
                    <input type="text" 
                           class="form-control" 
                           id="assignee" 
                           name="assignee"
                           value="<?= htmlspecialchars($_POST['assignee'] ?? '') ?>">
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Ticket erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
