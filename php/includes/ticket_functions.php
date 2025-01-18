<?php
declare(strict_types=1);

/**
 * Lädt die Details eines Tickets mit allen zugehörigen Informationen
 */
function getTicketDetails(int $ticketId, ?string $username): array {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT t.*, ts.name as status_name, ts.background_color as status_color, t.assignee,
               (SELECT partner_list FROM partners WHERE ticket_id = t.id LIMIT 1) as partner_list,
               (SELECT partner_link FROM partners WHERE ticket_id = t.id LIMIT 1) as partner_link,
               COALESCE(tst.up_votes, 0) as up_votes,
               COALESCE(tst.down_votes, 0) as down_votes,
               COALESCE(tv.value, 'none') as user_vote,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM ticket_votes
                   WHERE ticket_id = t.id AND value = 'up'
               ) as upvoters,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM ticket_votes
                   WHERE ticket_id = t.id AND value = 'down'
               ) as downvoters
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        LEFT JOIN ticket_statistics tst ON t.id = tst.ticket_id
        LEFT JOIN ticket_votes tv ON t.id = tv.ticket_id AND tv.username = ?
        WHERE t.id = ?
    ");
    $stmt->execute([$username, $ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new RuntimeException('Ticket nicht gefunden');
    }

    return $ticket;
}

/**
 * Lädt alle verfügbaren Ticket-Status
 */
function getAllTicketStatus(): array {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, name, background_color FROM ticket_status ORDER BY sort_order ASC, name ASC");
    return $stmt->fetchAll();
}
