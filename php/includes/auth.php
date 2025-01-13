<?php
session_start();

function validateMasterLink($linkCode) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT * FROM master_links 
        WHERE link_code = ? AND is_active = TRUE
    ");
    $stmt->execute([$linkCode]);
    $masterLink = $stmt->fetch();

    if ($masterLink) {
        // Aktualisiere last_used_at
        $updateStmt = $db->prepare("
            UPDATE master_links 
            SET last_used_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $updateStmt->execute([$masterLink['id']]);
        return true;
    }
    return false;
}

function requireMasterLink() {
    $linkCode = $_GET['master_code'] ?? $_SESSION['master_code'] ?? null;
    
    if ($linkCode && validateMasterLink($linkCode)) {
        $_SESSION['master_code'] = $linkCode;
        return true;
    }
    
    // Wenn kein gültiger Master-Link, redirect zur Fehlerseite
    header('Location: error.php?type=unauthorized');
    exit;
}

function isPartnerLink($partnerLink) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT * FROM partners 
        WHERE partner_link = ?
    ");
    $stmt->execute([$partnerLink]);
    return $stmt->fetch();
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function setCurrentUsername($username) {
    $_SESSION['username'] = $username;
}
