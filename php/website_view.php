<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/activity_functions.php';

// Prüfe Master-Link
requireMasterLink();

// Header einbinden
require_once __DIR__ . '/includes/header.php';

try {
    // Hole alle Tickets, die auf der Website angezeigt werden sollen
    $db = Database::getInstance()->getConnection();
    
    // Prüfe zuerst, ob die Spalte show_on_website existiert
    $checkColumnSql = "
        SELECT COUNT(*) as column_exists 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'show_on_website'
    ";
    $stmt = $db->query($checkColumnSql);
    $columnExists = $stmt->fetch(PDO::FETCH_ASSOC)['column_exists'] > 0;
    
    if (!$columnExists) {
        throw new RuntimeException('Die Spalte show_on_website existiert nicht in der Tabelle tickets. Bitte führen Sie die Datenbankmigrationen aus.');
    }
    
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
               (SELECT COUNT(*) FROM ticket_votes WHERE ticket_id = t.id AND value = 'up') as up_votes,
               (SELECT COUNT(*) FROM ticket_votes WHERE ticket_id = t.id AND value = 'down') as down_votes
        FROM tickets t
        LEFT JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.show_on_website = 1
        ORDER BY last_activity DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug-Ausgabe
    if (empty($tickets)) {
        echo '<div class="container mt-4"><div class="alert alert-info">';
        echo '<h4><i class="bi bi-info-circle"></i> Debug-Information</h4>';
        
        // Prüfe, ob überhaupt Tickets existieren
        $countSql = "SELECT COUNT(*) as total FROM tickets";
        $stmt = $db->query($countSql);
        $totalTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Prüfe, wie viele Tickets show_on_website = 1 haben
        $websiteTicketsSql = "SELECT COUNT(*) as total FROM tickets WHERE show_on_website = 1";
        $stmt = $db->query($websiteTicketsSql);
        $websiteTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo '<ul>';
        echo '<li>Gesamtanzahl Tickets in der Datenbank: ' . $totalTickets . '</li>';
        echo '<li>Davon für Website markiert (show_on_website = 1): ' . $websiteTickets . '</li>';
        echo '</ul>';
        
        if ($totalTickets > 0 && $websiteTickets === 0) {
            echo '<p class="mb-0">Es sind zwar Tickets vorhanden, aber keines ist für die Website-Anzeige markiert. ';
            echo 'Markieren Sie Tickets für die Website-Anzeige in der Ticket-Bearbeitung.</p>';
        }
        echo '</div></div>';
    }
    
} catch (Exception $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger">';
    echo '<h4><i class="bi bi-exclamation-triangle"></i> Fehler</h4>';
    echo '<p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div></div>';
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Website-Ansicht</h1>
            <small class="text-muted">
                Zeigt alle Tickets, die auf der Website angezeigt werden
            </small>
        </div>
        <a href="<?= $basePath ?>/create_ticket.php" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Neues Ticket
        </a>
    </div>

    <!-- Ticket-Liste -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Status</th>
                    <th class="text-center"><i class="bi bi-hand-thumbs-up"></i></th>
                    <th class="text-center"><i class="bi bi-people"></i></th>
                    <th>Letzte Aktivität</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-info-circle me-2"></i>
                        Keine Tickets für die Website-Anzeige gefunden
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): 
                        $activityClass = getActivityClass($ticket['last_activity']);
                        $bgColor = 'inherit';
                    ?>
                    <tr>
                        <td class="<?= $activityClass ?>" style="background-color: <?= htmlspecialchars($bgColor) ?>">
                            <div>
                                <a href="ticket_view.php?id=<?= $ticket['id'] ?>" class="text-decoration-none">
                                    #<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['title']) ?>
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
                        <td class="<?= $activityClass ?>" style="text-align: center; background-color: <?= htmlspecialchars($bgColor) ?>">
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
        </small>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
