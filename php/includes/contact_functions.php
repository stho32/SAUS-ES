<?php
declare(strict_types=1);
/**
 * Functions for managing contact persons at the cooperative
 * Implementation for REQ0008
 */

/**
 * Get all contact persons with optional filter for active only
 * 
 * @param bool $activeOnly Whether to return only active contact persons
 * @return array Array of contact person objects
 */
function getContactPersons(bool $activeOnly = false): array {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM contact_persons";
    
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a single contact person by ID
 * 
 * @param int $id Contact person ID
 * @return array|false Contact person data or false if not found
 */
function getContactPerson(int $id): array|false {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM contact_persons WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create a new contact person
 * 
 * @param array $data Contact person data
 * @return string|false ID of created contact person or false on failure
 */
function createContactPerson(array $data): string|false {
    $db = Database::getInstance()->getConnection();
    
    $sql = "INSERT INTO contact_persons (name, email, phone, contact_notes, responsibility_notes, is_active) 
            VALUES (:name, :email, :phone, :contact_notes, :responsibility_notes, :is_active)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
    $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
    $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
    $stmt->bindParam(':contact_notes', $data['contact_notes'], PDO::PARAM_STR);
    $stmt->bindParam(':responsibility_notes', $data['responsibility_notes'], PDO::PARAM_STR);
    $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $db->lastInsertId();
    }
    
    return false;
}

/**
 * Update an existing contact person
 * 
 * @param int $id Contact person ID
 * @param array $data Contact person data
 * @return bool Success status
 */
function updateContactPerson(int $id, array $data): bool {
    $db = Database::getInstance()->getConnection();
    
    $sql = "UPDATE contact_persons 
            SET name = :name, 
                email = :email, 
                phone = :phone, 
                contact_notes = :contact_notes, 
                responsibility_notes = :responsibility_notes, 
                is_active = :is_active 
            WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
    $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
    $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
    $stmt->bindParam(':contact_notes', $data['contact_notes'], PDO::PARAM_STR);
    $stmt->bindParam(':responsibility_notes', $data['responsibility_notes'], PDO::PARAM_STR);
    $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Toggle a contact person's active status
 * 
 * @param int $id Contact person ID
 * @param int $status New status (0 or 1)
 * @return bool Success status
 */
function toggleContactPersonStatus(int $id, int $status): bool {
    $db = Database::getInstance()->getConnection();
    
    $sql = "UPDATE contact_persons SET is_active = :status WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Get contact persons linked to a ticket
 * 
 * @param int $ticketId Ticket ID
 * @return array Array of contact persons linked to the ticket
 */
function getTicketContactPersons(int $ticketId): array {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT cp.* 
            FROM contact_persons cp
            JOIN ticket_contact_persons tcp ON cp.id = tcp.contact_person_id
            WHERE tcp.ticket_id = :ticket_id
            ORDER BY cp.name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Link a contact person to a ticket
 * 
 * @param int $ticketId Ticket ID
 * @param int $contactPersonId Contact person ID
 * @return bool Success status
 */
function linkContactPersonToTicket(int $ticketId, int $contactPersonId): bool {
    $db = Database::getInstance()->getConnection();
    
    // Check if the link already exists
    $checkSql = "SELECT id FROM ticket_contact_persons 
                WHERE ticket_id = :ticket_id AND contact_person_id = :contact_person_id";
    
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $checkStmt->bindParam(':contact_person_id', $contactPersonId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        // Link already exists
        return true;
    }
    
    // Create the link
    $sql = "INSERT INTO ticket_contact_persons (ticket_id, contact_person_id)
            VALUES (:ticket_id, :contact_person_id)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $stmt->bindParam(':contact_person_id', $contactPersonId, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Unlink a contact person from a ticket
 * 
 * @param int $ticketId Ticket ID
 * @param int $contactPersonId Contact person ID
 * @return bool Success status
 */
function unlinkContactPersonFromTicket(int $ticketId, int $contactPersonId): bool {
    $db = Database::getInstance()->getConnection();
    
    $sql = "DELETE FROM ticket_contact_persons 
            WHERE ticket_id = :ticket_id AND contact_person_id = :contact_person_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $stmt->bindParam(':contact_person_id', $contactPersonId, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Format contact person information for comment
 * 
 * @param array $contactPerson Contact person data
 * @return string Formatted contact person information
 */
function formatContactPersonInfoForComment(array $contactPerson): string {
    $info = $contactPerson['name'];
    
    $details = [];
    if (!empty($contactPerson['phone'])) {
        $details[] = "Telefon: " . $contactPerson['phone'];
    }
    
    if (!empty($contactPerson['email'])) {
        $details[] = "Email: " . $contactPerson['email'];
    }
    
    if (!empty($details)) {
        $info .= " (" . implode(", ", $details) . ")";
    }
    
    return $info;
}
?>
