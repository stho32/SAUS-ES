<?php
declare(strict_types=1);

/**
 * Lädt alle Kommentare für ein Ticket mit Voting-Statistiken
 */
function getTicketComments(int $ticketId, string $username): array {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT c.*, cs.up_votes, cs.down_votes,
               COALESCE(cv.value, 'none') as user_vote,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM comment_votes
                   WHERE comment_id = c.id AND value = 'up'
               ) as upvoters,
               (
                   SELECT GROUP_CONCAT(username)
                   FROM comment_votes
                   WHERE comment_id = c.id AND value = 'down'
               ) as downvoters,
               c.is_visible,
               c.hidden_by,
               c.hidden_at
        FROM comments c
        LEFT JOIN comment_statistics cs ON c.id = cs.comment_id
        LEFT JOIN comment_votes cv ON c.id = cv.comment_id AND cv.username = ?
        WHERE c.ticket_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$username, $ticketId]);
    return $stmt->fetchAll();
}

/**
 * Rendert einen einzelnen Kommentar
 */
function renderComment(array $comment): string {
    $visibilityClass = !$comment['is_visible'] ? 'comment-hidden' : '';
    $html = sprintf('
        <div class="comment mb-4 %s" id="comment-%d" data-visible="%s">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>%s</strong>
                    <small class="text-muted">%s</small>
                </div>
                <div class="btn-group">
                    <button type="button" 
                            class="btn btn-sm %s"
                            onclick="voteComment(%d, \'%s\')"
                            title="%s">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <span class="upvote-count">%d</span>
                    </button>
                    <button type="button" 
                            class="btn btn-sm %s"
                            onclick="voteComment(%d, \'%s\')"
                            title="%s">
                        <i class="bi bi-hand-thumbs-down"></i>
                        <span class="downvote-count">%d</span>
                    </button>
                </div>
            </div>
            <div class="comment-content mt-2">%s</div>
        </div>',
        $visibilityClass,
        $comment['id'],
        $comment['is_visible'] ? 'true' : 'false',
        htmlspecialchars($comment['username']),
        htmlspecialchars(date('d.m.Y H:i', strtotime($comment['created_at']))),
        $comment['user_vote'] === 'up' ? 'btn-success' : 'btn-outline-success',
        $comment['id'],
        $comment['user_vote'] === 'up' ? 'none' : 'up',
        $comment['upvoters'] ? 'Upvotes von: ' . htmlspecialchars($comment['upvoters']) : 'Keine Upvotes',
        $comment['up_votes'],
        $comment['user_vote'] === 'down' ? 'btn-danger' : 'btn-outline-danger',
        $comment['id'],
        $comment['user_vote'] === 'down' ? 'none' : 'down',
        $comment['downvoters'] ? 'Downvotes von: ' . htmlspecialchars($comment['downvoters']) : 'Keine Downvotes',
        $comment['down_votes'],
        nl2br(htmlspecialchars($comment['content']))
    );
    return $html;
}
