<?php
function generateTicketNumber() {
    $timestamp = time();
    $random = rand(1000, 9999);
    return date('Ymd', $timestamp) . '-' . $random;
}

function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_-]{2,50}$/', $username);
}

function getTicketStatus($statusId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM ticket_status WHERE id = ?");
    $stmt->execute([$statusId]);
    return $stmt->fetch();
}

function countVotes($ticketId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN value = 'up' THEN 1 END) as up_votes,
            COUNT(CASE WHEN value = 'down' THEN 1 END) as down_votes
        FROM votes 
        WHERE ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    return $stmt->fetch();
}

function hasEnoughVotes($ticketId) {
    $config = require __DIR__ . '/../config.php';
    $minVotes = $config['app']['min_votes_required'];
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $result = $stmt->fetch();
    
    return $result['vote_count'] >= $minVotes;
}

function generatePartnerLink() {
    return bin2hex(random_bytes(16));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}
