<?php
declare(strict_types=1);

function generateTicketNumber(): string {
    $timestamp = time();
    $random = random_int(1000, 9999);
    return date('Ymd', $timestamp) . '-' . $random;
}

function isValidUsername(string $username): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_-]{2,50}$/', $username);
}

function getTicketStatus(int $statusId): ?array {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM ticket_status WHERE id = ?");
    $stmt->execute([$statusId]);
    return $stmt->fetch() ?: null;
}

function countVotes(int $ticketId): array {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN value = 'up' THEN 1 END) as up_votes,
            COUNT(CASE WHEN value = 'down' THEN 1 END) as down_votes
        FROM votes 
        WHERE ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    return $stmt->fetch() ?: ['up_votes' => 0, 'down_votes' => 0];
}

function hasEnoughVotes(int $ticketId): bool {
    $config = require __DIR__ . '/../config.php';
    $minVotes = $config['app']['min_votes_required'];
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $result = $stmt->fetch();
    
    return ($result['vote_count'] ?? 0) >= $minVotes;
}

function generatePartnerLink(): string {
    return bin2hex(random_bytes(16));
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDateTime(string $datetime): string {
    return date('d.m.Y H:i', strtotime($datetime));
}
