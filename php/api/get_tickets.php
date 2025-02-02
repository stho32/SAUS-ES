<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

try {
    // JSON-Daten aus dem Request-Body lesen
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['filterType']) || !isset($data['filterValue'])) {
        throw new Exception('Fehlende Parameter');
    }

    $filterType = $data['filterType'];
    $filterValue = $data['filterValue'];

    $db = Database::getInstance()->getConnection();
    
    // Basis-Query
    $sql = "
        SELECT 
            t.id,
            t.title,
            t.assignee,
            t.created_at,
            t.affected_neighbors,
            ts.name as status
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter basierend auf dem Typ anwenden
    switch ($filterType) {
        case 'status':
            $sql .= " AND ts.name = ?";
            $params[] = $filterValue;
            break;
            
        case 'assignee_in_progress':
            $sql .= " AND ts.filter_category = 'in_bearbeitung'";
            $sql .= " AND (t.assignee LIKE ? OR t.assignee LIKE ? OR t.assignee LIKE ? OR t.assignee = ?)";
            $params[] = $filterValue . ',%';
            $params[] = '%,' . $filterValue;
            $params[] = '%+' . $filterValue;
            $params[] = $filterValue;
            break;
            
        case 'assignee_completed':
            $sql .= " AND ts.filter_category IN ('ready', 'geschlossen')";
            $sql .= " AND (t.assignee LIKE ? OR t.assignee LIKE ? OR t.assignee LIKE ? OR t.assignee = ?)";
            $params[] = $filterValue . ',%';
            $params[] = '%,' . $filterValue;
            $params[] = '%+' . $filterValue;
            $params[] = $filterValue;
            break;
            
        default:
            throw new Exception('Ungültiger Filter-Typ');
    }
    
    $sql .= " ORDER BY t.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tickets);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
