<?php
declare(strict_types=1);

session_start();

function validateMasterLink(?string $linkCode): bool {
    if (!$linkCode) return false;
    
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

function requireMasterLink(): bool {
    $linkCode = $_GET['master_code'] ?? $_SESSION['master_code'] ?? null;
    
    if ($linkCode && validateMasterLink($linkCode)) {
        $_SESSION['master_code'] = $linkCode;
        return true;
    }
    
    // Wenn kein gültiger Master-Link, redirect zur Fehlerseite
    header('Location: error.php?type=unauthorized');
    exit;
}

function isPartnerLink(?string $partnerLink): ?array {
    if (!$partnerLink) return null;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT * FROM partners 
        WHERE partner_link = ?
    ");
    $stmt->execute([$partnerLink]);
    return $stmt->fetch() ?: null;
}

function getCurrentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

function setCurrentUsername(string $username): void {
    $_SESSION['username'] = $username;
}
