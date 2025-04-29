<?php
declare(strict_types=1);
/**
 * API endpoint to remove a contact person from a ticket
 * Implementation for REQ0008
 */

require_once '../includes/api_auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/contact_functions.php';
require_once '../includes/comment_functions.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Prüfe Authentifizierung
if (!isset($_SESSION['master_code'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ticketId']) || !isset($data['contactPersonId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$ticketId = (int)$data['ticketId'];
$contactPersonId = (int)$data['contactPersonId'];

try {
    // Get contact person details for the comment before removing
    $contactPerson = getContactPerson($contactPersonId);
    if (!$contactPerson) {
        throw new Exception('Ansprechpartner nicht gefunden');
    }
    
    // Unlink the contact person from the ticket
    $result = unlinkContactPersonFromTicket($ticketId, $contactPersonId);
    
    if ($result) {
        // Add a comment to the ticket history
        $username = getCurrentUsername();
        $formattedInfo = formatContactPersonInfoForComment($contactPerson);
        $commentContent = "Ansprechpartner {$formattedInfo} wurde entfernt.";
        
        $commentSuccess = addTicketComment($ticketId, $commentContent, $username);
        
        if (!$commentSuccess) {
            // If we can't add the comment, but the unlink was successful, it's still a partial success
            echo json_encode([
                'success' => true,
                'message' => 'Ansprechpartner entfernt, aber der Kommentar konnte nicht erstellt werden.'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Ansprechpartner erfolgreich entfernt'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Entfernen des Ansprechpartners'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server-Fehler: ' . $e->getMessage()
    ]);
}
?>
