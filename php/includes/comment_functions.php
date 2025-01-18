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
function renderComment(array $comment, bool $isPartner = false): string {
    $visibilityClass = !$comment['is_visible'] ? 'comment-hidden' : '';
    $currentUsername = getCurrentUsername();

    $html = sprintf('
        <div class="comment mb-4 %s" id="comment-%d" data-visible="%s">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>%s</strong>
                    <small class="text-muted">
                        %s
                        %s
                        %s
                    </small>
                </div>
                %s
            </div>
            <div class="mt-2">
                <div id="comment-text-%d" class="comment-text" data-raw-content="%s">
                    %s
                </div>
            </div>',
        $visibilityClass,
        $comment['id'],
        $comment['is_visible'] ? 'true' : 'false',
        htmlspecialchars($comment['username']),
        htmlspecialchars(date('d.m.Y H:i', strtotime($comment['created_at']))),
        $comment['is_edited'] ? sprintf('(bearbeitet am %s)', htmlspecialchars(date('d.m.Y H:i', strtotime($comment['updated_at'])))) : '',
        !$comment['is_visible'] ? sprintf('
            <span class="text-danger">
                (Ausgeblendet von %s am %s)
            </span>',
            htmlspecialchars($comment['hidden_by']),
            htmlspecialchars(date('d.m.Y H:i', strtotime($comment['hidden_at'])))
        ) : '',
        !$isPartner ? sprintf('
            <div class="btn-group" role="group">
                <button type="button" 
                        class="btn btn-sm %s"
                        onclick="voteComment(%d, \'%s\')"
                        title="%s">
                    <i class="bi bi-hand-thumbs-%s"></i>
                    <span class="vote-count">%d</span>
                </button>
                <button type="button" 
                        class="btn btn-sm %s"
                        onclick="voteComment(%d, \'%s\')"
                        title="%s">
                    <i class="bi bi-hand-thumbs-%s"></i>
                    <span class="vote-count">%d</span>
                </button>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        onclick="toggleCommentVisibility(%d, %s)">
                    <i class="bi bi-eye%s"></i>
                </button>
                %s
            </div>',
            $comment['user_vote'] === 'up' ? 'btn-success' : 'btn-outline-success',
            $comment['id'],
            $comment['user_vote'] === 'up' ? 'none' : 'up',
            $comment['upvoters'] ? 'Upvotes von: ' . htmlspecialchars($comment['upvoters']) : 'Keine Upvotes',
            $comment['up_votes'] > 0 ? 'up-fill' : 'up',
            $comment['up_votes'],
            $comment['user_vote'] === 'down' ? 'btn-danger' : 'btn-outline-danger',
            $comment['id'],
            $comment['user_vote'] === 'down' ? 'none' : 'down',
            $comment['downvoters'] ? 'Downvotes von: ' . htmlspecialchars($comment['downvoters']) : 'Keine Downvotes',
            $comment['down_votes'] > 0 ? 'down-fill' : 'down',
            $comment['down_votes'],
            $comment['id'],
            $comment['is_visible'] ? 'false' : 'true',
            $comment['is_visible'] ? '-slash' : '',
            $comment['username'] === $currentUsername ? sprintf('
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="startEditComment(%d)">
                    <i class="bi bi-pencil"></i>
                </button>',
                $comment['id']
            ) : ''
        ) : '',
        $comment['id'],
        htmlspecialchars($comment['content']),
        formatComment($comment['content'])
    );

    // Füge Voter-Liste hinzu, wenn vorhanden
    $upvoters = $comment['upvoters'] ? explode(',', $comment['upvoters']) : [];
    $downvoters = $comment['downvoters'] ? explode(',', $comment['downvoters']) : [];
    
    if (!empty($upvoters) || !empty($downvoters)) {
        $parts = [];
        if (!empty($upvoters)) {
            $parts[] = 'dafür: ' . implode(', ', array_map('htmlspecialchars', $upvoters));
        }
        if (!empty($downvoters)) {
            $parts[] = 'dagegen: ' . implode(', ', array_map('htmlspecialchars', $downvoters));
        }
        
        $html .= sprintf('
            <div class="text-end mt-2">
                <small class="text-muted">%s</small>
            </div>',
            implode(' / ', $parts)
        );
    }

    $html .= '</div>';
    return $html;
}
