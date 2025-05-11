<?php
declare(strict_types=1);
/**
 * Contact Persons Management Page
 * Implements REQ0008: Contact Persons at the Cooperative
 */

require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/contact_functions.php';

// Prüfe Authentifizierung
requireMasterLink();

// Stelle sicher, dass ein Benutzername gesetzt ist
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle form submission for new/edit contact person
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Process contact person data
    $contactPersonData = [
        'name' => htmlspecialchars(trim($_POST['name'] ?? '')),
        'email' => htmlspecialchars(trim($_POST['email'] ?? '')),
        'phone' => htmlspecialchars(trim($_POST['phone'] ?? '')),
        'contact_notes' => htmlspecialchars(trim($_POST['contact_notes'] ?? '')),
        'responsibility_notes' => htmlspecialchars(trim($_POST['responsibility_notes'] ?? '')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Validate required fields
    if (empty($contactPersonData['name'])) {
        $errorMessage = 'Name ist ein Pflichtfeld.';
    } else {
        if ($action === 'create') {
            // Create new contact person
            $result = createContactPerson($contactPersonData);
            if ($result) {
                $successMessage = 'Ansprechpartner erfolgreich erstellt.';
            } else {
                $errorMessage = 'Fehler beim Erstellen des Ansprechpartners.';
            }
        } elseif ($action === 'update' && isset($_POST['id'])) {
            // Update existing contact person
            $id = (int)$_POST['id'];
            $result = updateContactPerson($id, $contactPersonData);
            if ($result) {
                $successMessage = 'Ansprechpartner erfolgreich aktualisiert.';
            } else {
                $errorMessage = 'Fehler beim Aktualisieren des Ansprechpartners.';
            }
        }
    }
    
    // Toggle active status
    if ($action === 'toggle_status' && isset($_POST['id']) && isset($_POST['status'])) {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'];
        $result = toggleContactPersonStatus($id, $status);
        if ($result) {
            $statusText = $status ? 'aktiviert' : 'deaktiviert';
            $successMessage = "Ansprechpartner erfolgreich $statusText.";
        } else {
            $errorMessage = 'Fehler beim Ändern des Status.';
        }
    }
}

// Get all contact persons
$contactPersons = getContactPersons();

// Include header
$pageTitle = 'Ansprechpartner verwalten';
include 'includes/header.php';
?>

<div class="container mt-4">
    <h1><?= $pageTitle ?></h1>
    
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= $errorMessage ?></div>
    <?php endif; ?>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= $successMessage ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Neuen Ansprechpartner hinzufügen</h5>
        </div>
        <div class="card-body">
            <form action="contact_persons.php" method="post">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Telefonnummer</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                
                <div class="mb-3">
                    <label for="contact_notes" class="form-label">Kontaktinformationen</label>
                    <textarea class="form-control" id="contact_notes" name="contact_notes" rows="3"></textarea>
                    <div class="form-text">Weitere Kontaktinformationen wie Anschrift, Fax, etc.</div>
                </div>
                
                <div class="mb-3">
                    <label for="responsibility_notes" class="form-label">Zuständigkeiten</label>
                    <textarea class="form-control" id="responsibility_notes" name="responsibility_notes" rows="3"></textarea>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">Aktiv</label>
                </div>
                
                <button type="submit" class="btn btn-primary">Ansprechpartner erstellen</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Ansprechpartner</h5>
        </div>
        <div class="card-body">
            <?php if (empty($contactPersons)): ?>
                <p class="text-muted">Keine Ansprechpartner gefunden.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Telefon</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contactPersons as $person): ?>
                                <tr<?= $person['is_active'] ? '' : ' class="table-secondary"' ?>>
                                    <td><?= htmlspecialchars($person['name']) ?></td>
                                    <td><?= htmlspecialchars($person['email'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($person['phone'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($person['is_active']): ?>
                                            <span class="badge bg-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Deaktiviert</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-contact" 
                                                data-bs-toggle="modal" data-bs-target="#editContactModal" 
                                                data-id="<?= $person['id'] ?>"
                                                data-name="<?= htmlspecialchars($person['name']) ?>"
                                                data-email="<?= htmlspecialchars($person['email']) ?>"
                                                data-phone="<?= htmlspecialchars($person['phone']) ?>"
                                                data-contact-notes="<?= htmlspecialchars($person['contact_notes']) ?>"
                                                data-responsibility-notes="<?= htmlspecialchars($person['responsibility_notes']) ?>"
                                                data-is-active="<?= $person['is_active'] ?>">
                                            <i class="bi bi-pencil"></i> Bearbeiten
                                        </button>
                                        
                                        <form method="post" class="d-inline toggle-status-form">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $person['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $person['is_active'] ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-sm <?= $person['is_active'] ? 'btn-warning' : 'btn-success' ?> ms-1">
                                                <?php if ($person['is_active']): ?>
                                                    <i class="bi bi-x-circle"></i> Deaktivieren
                                                <?php else: ?>
                                                    <i class="bi bi-check-circle"></i> Aktivieren
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-sm btn-info ms-1 view-details"
                                                data-bs-toggle="modal" data-bs-target="#viewDetailsModal"
                                                data-name="<?= htmlspecialchars($person['name']) ?>"
                                                data-responsibility-notes="<?= htmlspecialchars($person['responsibility_notes']) ?>"
                                                data-contact-notes="<?= htmlspecialchars($person['contact_notes']) ?>">
                                            <i class="bi bi-info-circle"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editContactModalLabel">Ansprechpartner bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="contact_persons.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit-id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">E-Mail</label>
                        <input type="email" class="form-control" id="edit-email" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-phone" class="form-label">Telefonnummer</label>
                        <input type="text" class="form-control" id="edit-phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-contact-notes" class="form-label">Kontaktinformationen</label>
                        <textarea class="form-control" id="edit-contact-notes" name="contact_notes" rows="3"></textarea>
                        <div class="form-text">Weitere Kontaktinformationen wie Anschrift, Fax, etc.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-responsibility-notes" class="form-label">Zuständigkeiten</label>
                        <textarea class="form-control" id="edit-responsibility-notes" name="responsibility_notes" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit-is-active" name="is_active">
                        <label class="form-check-label" for="edit-is-active">Aktiv</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailsModalLabel">Ansprechpartner Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="details-name"></h6>
                
                <div class="mt-3">
                    <h6>Zuständigkeiten:</h6>
                    <p class="details-responsibility-notes text-wrap"></p>
                </div>
                
                <div class="mt-3">
                    <h6>Kontaktinformationen:</h6>
                    <p class="details-contact-notes text-wrap"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-contact').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const contactNotes = this.getAttribute('data-contact-notes');
            const responsibilityNotes = this.getAttribute('data-responsibility-notes');
            const isActive = this.getAttribute('data-is-active') === '1';
            
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-phone').value = phone;
            document.getElementById('edit-contact-notes').value = contactNotes;
            document.getElementById('edit-responsibility-notes').value = responsibilityNotes;
            document.getElementById('edit-is-active').checked = isActive;
        });
    });
    
    // Handle view details clicks
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.getAttribute('data-name');
            const responsibilityNotes = this.getAttribute('data-responsibility-notes') || 'Keine Informationen vorhanden';
            const contactNotes = this.getAttribute('data-contact-notes') || 'Keine Informationen vorhanden';
            
            document.querySelector('.details-name').textContent = name;
            document.querySelector('.details-responsibility-notes').textContent = responsibilityNotes;
            document.querySelector('.details-contact-notes').textContent = contactNotes;
        });
    });
    
    // Confirm status toggle
    document.querySelectorAll('.toggle-status-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const statusInput = this.querySelector('input[name="status"]');
            const action = statusInput.value === '1' ? 'aktivieren' : 'deaktivieren';
            
            if (!confirm(`Möchten Sie diesen Ansprechpartner wirklich ${action}?`)) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
