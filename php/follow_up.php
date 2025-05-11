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

// Prüfe Authentifizierung
requireMasterLink();

// Stelle sicher, dass ein Benutzername gesetzt ist
$currentUsername = getCurrentUsername();
if (!$currentUsername) {
    header('Location: index.php');
    exit;
}

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
    // Standardmäßig "In Bearbeitung" auswählen (wie in der Gesamtübersicht)
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
           t.follow_up_date,
           CASE 
                WHEN t.follow_up_date < CURDATE() THEN 1 -- abgelaufen
                WHEN t.follow_up_date = CURDATE() THEN 2 -- heute
                WHEN t.follow_up_date > CURDATE() THEN 4 -- zukünftig
                ELSE 3 -- kein Datum
           END as follow_up_priority,
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
           (SELECT COUNT(*) FROM ticket_votes WHERE ticket_id = t.id AND value = 'up') as up_votes,
           (SELECT COUNT(*) FROM ticket_votes WHERE ticket_id = t.id AND value = 'down') as down_votes
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE 1=1
    AND t.do_not_track = 0
    AND (
        (t.follow_up_date IS NULL AND DATE(last_activity) < CURDATE()) 
        OR t.follow_up_date <= CURDATE()
    )
";

$params = [];

// Füge Kategoriefilter hinzu
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
            $term = '%' . $term . '%';
            $termConditions = [
                "t.title LIKE ?",
                "t.description LIKE ?",
                "t.assignee LIKE ?",
                "t.public_comment LIKE ?",
                "t.id LIKE ?"
            ];
            
            for ($i = 0; $i < count($termConditions); $i++) {
                $params[] = $term;
            }
            
            $searchConditions[] = '(' . implode(' OR ', $termConditions) . ')';
        }
        
        $sql .= " AND " . implode(' AND ', $searchConditions);
    }
}

// "Dran bleiben"-Sortierung:
// 1. Tickets mit abgelaufenem Wiedervorlagedatum (oben)
// 2. Tickets mit heutigem Wiedervorlagedatum
// 3. Tickets ohne Wiedervorlagedatum (nach letzter Aktivität sortiert)
// 4. Tickets mit zukünftigem Wiedervorlagedatum (unten)
$sql .= " ORDER BY follow_up_priority ASC, 
         CASE 
            WHEN follow_up_priority = 3 THEN last_activity
            ELSE follow_up_date
         END ASC";

// Führe Query aus
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Definiere Kategorie-Labels
$categoryLabels = [
    'zurueckgestellt' => 'Zurückgestellt',
    'in_bearbeitung' => 'In Bearbeitung',
    'ready' => 'Bereit',
    'geschlossen' => 'Geschlossen',
    'archiviert' => 'Archiviert'
];

// Heutiges Datum
$today = new DateTime();
$todayStr = $today->format('Y-m-d');

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Dran bleiben</h1>
            <small class="text-muted">
                Zeigt Tickets, die Ihre Aufmerksamkeit benötigen
            </small>
        </div>
        <div>
            <a href="<?= $basePath ?>/create_ticket.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Neues Ticket
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="filter_applied" value="1">
                <?php foreach ($selectedCategories as $category): ?>
                <input type="hidden" name="category[]" value="<?= htmlspecialchars($category) ?>">
                <?php endforeach; ?>
                
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               value="<?= htmlspecialchars($searchText) ?>" 
                               placeholder="Tickets durchsuchen...">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Filter</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($filterCategories as $category): 
                            $isActive = in_array($category['filter_category'], $selectedCategories);
                            $btnClass = $isActive 
                                ? 'btn-primary' 
                                : 'btn-outline-secondary';
                                
                            // Erstelle Kopie des aktuellen Filters
                            $newCategories = $selectedCategories;
                            
                            // Toggle-Verhalten: Entferne, wenn aktiv, füge hinzu, wenn inaktiv
                            if ($isActive) {
                                $newCategories = array_values(array_filter($newCategories, function($c) use ($category) {
                                    return $c !== $category['filter_category'];
                                }));
                            } else {
                                $newCategories[] = $category['filter_category'];
                            }
                            
                            $queryParams = [
                                'filter_applied' => '1',
                                'category' => $newCategories
                            ];
                            
                            if (!empty($searchText)) {
                                $queryParams['search'] = $searchText;
                            }
                        ?>
                        <a href="?<?= http_build_query($queryParams) ?>" 
                           class="btn <?= $btnClass ?>">
                            <?= htmlspecialchars($categoryLabels[$category['filter_category']]) ?>
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
                    <th style="width: 10%">#</th>
                    <th style="width: 40%">Titel</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 10%" class="text-center">Stimmen</th>
                    <th style="width: 10%" class="text-center">Wiedervorlage</th>
                    <th style="width: 20%">Letzte Aktivität</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <p class="text-muted mb-0">Keine Tickets gefunden.</p>
                        <?php if (!empty($searchText) || !empty($selectedCategories)): ?>
                        <p class="text-muted mb-0">Versuchen Sie andere Filterbedingungen.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): 
                        $activityClass = getActivityClass($ticket['last_activity']);
                        
                        // Bestimme Hintergrundfarbe je nach Wiedervorlagedatum
                        $bgColor = 'transparent';
                        $iconClass = '';
                        $displayDate = '';
                        
                        if ($ticket['follow_up_date']) {
                            $followUpDate = new DateTime($ticket['follow_up_date']);
                            $displayDate = $followUpDate->format('d.m.Y');
                            
                            if ($ticket['follow_up_date'] < $todayStr) {
                                // Abgelaufenes Datum
                                $bgColor = '#ffecec'; // leichtes rot
                                $iconClass = 'bi bi-exclamation-circle-fill text-danger';
                            } else if ($ticket['follow_up_date'] == $todayStr) {
                                // Heutiges Datum
                                $bgColor = '#fff7e6'; // leichtes gelb
                                $iconClass = 'bi bi-star-fill text-warning';
                            } else {
                                // Zukünftiges Datum
                                $bgColor = '#f2f2f2'; // grau
                            }
                        }
                    ?>
                    <tr onclick="window.location='ticket_view.php?id=<?= $ticket['id'] ?>&ref=follow_up.php';" style="cursor: pointer; background-color: <?= htmlspecialchars($bgColor) ?>;">
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            #<?= $ticket['id'] ?>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <div class="d-flex flex-column">
                                <div>
                                    <a href="ticket_view.php?id=<?= $ticket['id'] ?>&ref=follow_up.php" class="link-dark text-decoration-none fw-medium">
                                        <?= htmlspecialchars($ticket['title']) ?>
                                        <?php if ($ticket['show_on_website']): ?>
                                        <i class="bi bi-globe ms-1 text-muted" title="Wird auf der Website angezeigt"></i>
                                        <?php endif; ?>
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
                            </div>
                        </td>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <span class="badge fw-normal" style="background-color: <?= htmlspecialchars($ticket['background_color']) ?>; color: #000000">
                                <?= htmlspecialchars($ticket['status_name']) ?>
                            </span>
                        </td>
                        <td class="text-center <?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <?php if ($ticket['up_votes'] > 0 || $ticket['down_votes'] > 0): ?>
                                <?php if ($ticket['up_votes'] > 0): ?>
                                    <?= $ticket['up_votes'] ?>× <i class="bi bi-hand-thumbs-up-fill text-success"></i>
                                <?php endif; ?>
                                <?php if ($ticket['up_votes'] > 0 && $ticket['down_votes'] > 0): ?>, <?php endif; ?>
                                <?php if ($ticket['down_votes'] > 0): ?>
                                    <?= $ticket['down_votes'] ?>× <i class="bi bi-hand-thumbs-down-fill text-danger"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-center <?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <?php if ($ticket['follow_up_date']): ?>
                                <?php if ($iconClass): ?>
                                <i class="<?= $iconClass ?>" title="<?= $ticket['follow_up_date'] < $todayStr ? 'Überfällig' : 'Heute fällig' ?>"></i>
                                <?php endif; ?>
                                <?= $displayDate ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
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
