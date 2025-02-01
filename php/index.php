<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Database.php';

// Funktion zur Berechnung der Aktivitätsklasse
function getActivityClass(string $lastActivity): string {
    $lastActivityDate = new DateTime($lastActivity);
    $today = new DateTime();
    $diff = $today->diff($lastActivityDate);
    $daysDiff = (int)$diff->format('%r%a');  // Negative Zahl für Vergangenheit

    if ($daysDiff > 14) {
        return 'activity-old';
    }

    return 'activity-' . abs($daysDiff);
}

// Prüfe Master-Link
requireMasterLink();

// Hole Benutzernamen, wenn noch nicht gesetzt
if (!getCurrentUsername()) {
    if (isset($_POST['username'])) {
        setCurrentUsername($_POST['username']);
    } else {
        // Zeige Formular für Benutzernamen
        require_once __DIR__ . '/includes/header.php';
        ?>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Willkommen bei SAUS-ES</h5>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Ihr Namenskürzel:</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Weiter</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

// Header einbinden
require_once __DIR__ . '/includes/header.php';

// Hole alle Status für den Filter
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT DISTINCT filter_category, 
           COUNT(*) as status_count 
    FROM ticket_status 
    WHERE is_active = 1 
    GROUP BY filter_category 
    ORDER BY FIELD(filter_category, 'zurueckgestellt', 'in_bearbeitung', 'ready', 'geschlossen', 'archiviert')
");
$filterCategories = $stmt->fetchAll();

// Bestimme aktive Filter
$isFirstVisit = !isset($_GET['filter_applied']);
$selectedCategories = [];
$searchText = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($isFirstVisit) {
    // Standardmäßig "In Bearbeitung" auswählen
    $selectedCategories[] = 'in_bearbeitung';
} else {
    // Ansonsten die ausgewählten Kategorien aus der Request nehmen
    $selectedCategories = isset($_GET['category']) ? (array)$_GET['category'] : [];
}

// Baue SQL-Query
$sql = "
    SELECT t.*, ts.name as status_name, ts.background_color, ts.filter_category,
           t.show_on_website,
           t.affected_neighbors,
           (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as comment_count,
           GREATEST(t.created_at, COALESCE((SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id), t.created_at)) as last_activity,
           (SELECT username FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_commenter,
           (SELECT GROUP_CONCAT(DISTINCT username)
            FROM comments 
            WHERE ticket_id = t.id 
            AND username != (
                SELECT username 
                FROM comments c2 
                WHERE c2.ticket_id = t.id 
                ORDER BY created_at DESC 
                LIMIT 1
            )) as other_participants,
           (SELECT GROUP_CONCAT(username) FROM ticket_votes WHERE ticket_id = t.id AND value = 'up') as up_voters,
           (SELECT GROUP_CONCAT(username) FROM ticket_votes WHERE ticket_id = t.id AND value = 'down') as down_voters
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE 1=1
";

$params = [];

// Füge Filter hinzu
if (!empty($selectedCategories)) {
    $placeholders = str_repeat('?,', count($selectedCategories) - 1) . '?';
    $sql .= " AND ts.filter_category IN ($placeholders)";
    $params = array_merge($params, $selectedCategories);
}

// Füge Textsuche hinzu
if ($searchText !== '') {
    // Teile den Suchtext in einzelne Wörter
    $searchTerms = array_filter(explode(' ', $searchText));
    
    if (!empty($searchTerms)) {
        $searchConditions = [];
        foreach ($searchTerms as $term) {
            $searchFields = [
                't.id',
                't.title',
                'ts.name',
                't.assignee',
                '(SELECT GROUP_CONCAT(username) FROM comments WHERE ticket_id = t.id)'
            ];
            
            $termConditions = [];
            foreach ($searchFields as $field) {
                $termConditions[] = "$field LIKE ?";
                $params[] = '%' . $term . '%';
            }
            
            $searchConditions[] = '(' . implode(' OR ', $termConditions) . ')';
        }
        
        $sql .= " AND " . implode(' AND ', $searchConditions);
    }
}

$sql .= " ORDER BY last_activity DESC";

// Führe Query aus
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Ticket-Übersicht</h1>
            <small class="text-muted">
                Brauchen Sie Hilfe? 
                <a href="https://chatgpt.com/g/g-AYCDjxFTR-saus" target="_blank" class="text-decoration-none">
                    <i class="bi bi-robot"></i> SAUS-Berater-GPT
                </a>
            </small>
        </div>
        <a href="<?= $basePath ?>/create_ticket.php" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Neues Ticket
        </a>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="filter_applied" value="1">
                <?php foreach ($selectedCategories as $category): ?>
                <input type="hidden" name="category[]" value="<?= htmlspecialchars($category) ?>">
                <?php endforeach; ?>
                <div class="col-12">
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control" 
                               placeholder="Suche in Tickets..." 
                               name="search"
                               value="<?= htmlspecialchars($searchText) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Filter</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($filterCategories as $category): 
                            $isSelected = in_array($category['filter_category'], $selectedCategories);
                        ?>
                        <a href="<?= '?' . http_build_query(array_merge($_GET, [
                            'filter_applied' => '1',
                            'category' => $isSelected 
                                ? array_diff($selectedCategories, [$category['filter_category']])
                                : array_merge($selectedCategories, [$category['filter_category']])
                        ])) ?>"
                           class="btn <?= $isSelected ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <?php
                            $categoryLabels = [
                                'zurueckgestellt' => 'Zurückgestellt',
                                'in_bearbeitung' => 'In Bearbeitung',
                                'ready' => 'Bereit zur Vorstellung',
                                'geschlossen' => 'Erledigt',
                                'archiviert' => 'Archiviert'
                            ];
                            echo htmlspecialchars($categoryLabels[$category['filter_category']]);
                            ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12">
                    <a href="<?= '?' . http_build_query(['filter_applied' => '1', 'category' => ['in_bearbeitung']]) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Filter zurücksetzen
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ticket-Liste -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 90px">Nr.</th>
                    <th style="width: 40px"></th>
                    <th>Titel</th>
                    <th style="width: 130px">Status</th>
                    <th style="width: 140px" class="text-center">Votes</th>
                    <th style="width: 50px" class="text-center">ABN</th>
                    <th style="width: 140px">Aktivität</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="7" class="text-center py-3">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> 
                            Keine Tickets gefunden. Passen Sie die Filter an oder erstellen Sie ein neues Ticket.
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                    <?php 
                        // Activity-Klasse nur für "in_bearbeitung" anwenden
                        $activityClass = $ticket['filter_category'] === 'in_bearbeitung' ? getActivityClass($ticket['last_activity']) : '';
                        $bgColor = $ticket['filter_category'] === 'in_bearbeitung' ? $ticket['background_color'] : '#f8f9fa';
                    ?>
                    <tr>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="text-decoration-none">
                                #<?= $ticket['id'] ?>
                            </a>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>; text-align: center;">
                            <?php if ($ticket['show_on_website']): ?>
                                <i class="bi bi-globe text-primary" title="Öffentlich auf der Website sichtbar"></i>
                            <?php endif; ?>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <div>
                                <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($ticket['title']) ?>
                                </a>
                            </div>
                            <small>
                                <?php if ($ticket['assignee']): ?>
                                <div class="text-dark">Zuständig: <strong><?= htmlspecialchars($ticket['assignee']) ?></strong></div>
                                <?php endif; ?>
                                <?php 
                                $participants = [];
                                if ($ticket['last_commenter']) {
                                    $participants[] = $ticket['last_commenter'];
                                }
                                if ($ticket['other_participants']) {
                                    $participants = array_merge($participants, explode(',', $ticket['other_participants']));
                                }
                                if (!empty($participants)): 
                                ?>
                                <div class="text-muted">
                                    Teilnehmer: <?= htmlspecialchars(implode(', ', array_unique($participants))) ?>
                                    <?php if ($ticket['comment_count'] > 0): ?>
                                    <span class="ms-2">(<?= $ticket['comment_count'] ?> <?= $ticket['comment_count'] === 1 ? 'Kommentar' : 'Kommentare' ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <span class="badge fw-normal" style="background-color: <?= htmlspecialchars($ticket['background_color']) ?>; color: #000000">
                                <?= htmlspecialchars($ticket['status_name']) ?>
                            </span>
                        </td>
                        <td class="text-center <?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <?php
                            $upVoters = $ticket['up_voters'] ? explode(',', $ticket['up_voters']) : [];
                            $downVoters = $ticket['down_voters'] ? explode(',', $ticket['down_voters']) : [];
                            if (!empty($upVoters) || !empty($downVoters)): 
                            ?>
                            <div class="d-flex flex-column gap-1">
                                <?php foreach ($upVoters as $voter): ?>
                                <div class="text-success small">
                                    <?= htmlspecialchars($voter) ?>
                                    <i class="bi bi-hand-thumbs-up-fill"></i>
                                </div>
                                <?php endforeach; ?>
                                <?php foreach ($downVoters as $voter): ?>
                                <div class="text-danger small">
                                    <?= htmlspecialchars($voter) ?>
                                    <i class="bi bi-hand-thumbs-down-fill"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>; text-align: center;">
                            <?= $ticket['affected_neighbors'] !== null ? (int)$ticket['affected_neighbors'] : '-' ?>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <?= (new DateTime($ticket['last_activity']))->format('d.m.Y H:i') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($tickets)): ?>
    <div class="text-muted text-end mt-2">
        <small>
            <?= count($tickets) ?> Ticket<?= count($tickets) !== 1 ? 's' : '' ?> angezeigt
            <?php if (!empty($selectedCategories)): ?>
            (gefiltert)
            <?php endif; ?>
        </small>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
