<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Prüfe Authentifizierung
requireMasterLink();

// Hole die Statistiken
$db = Database::getInstance()->getConnection();

// Hole Status-Statistiken (ohne archiviert)
$stmt = $db->query("
    SELECT ts.id, ts.name, ts.filter_category, ts.background_color, COUNT(t.id) as ticket_count
    FROM ticket_status ts
    LEFT JOIN tickets t ON t.status_id = ts.id
    WHERE ts.is_active = 1 
    AND ts.filter_category != 'archiviert'
    GROUP BY ts.id, ts.name, ts.filter_category, ts.background_color, ts.sort_order
    ORDER BY ts.sort_order
");
$statusStats = $stmt->fetchAll();

// Hole Zuständigen-Statistiken für Tickets in Bearbeitung
$stmt = $db->query("
    WITH RECURSIVE split_assignees AS (
        SELECT 
            t.id,
            SUBSTRING_INDEX(SUBSTRING_INDEX(COALESCE(t.assignee, 'Nicht zugewiesen'), ',', n.n), ',', -1) as single_assignee
        FROM tickets t
        CROSS JOIN (
            SELECT a.N + b.N * 10 + 1 n
            FROM (SELECT 0 N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
            CROSS JOIN (SELECT 0 N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
            ORDER BY n
        ) n
        WHERE n.n <= 1 + LENGTH(COALESCE(t.assignee, 'Nicht zugewiesen')) - LENGTH(REPLACE(COALESCE(t.assignee, 'Nicht zugewiesen'), ',', ''))
    )
    SELECT 
        TRIM(REPLACE(sa.single_assignee, '+', '')) as assignee,
        COUNT(DISTINCT t.id) as ticket_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    JOIN split_assignees sa ON t.id = sa.id
    WHERE ts.filter_category = 'in_bearbeitung'
    GROUP BY TRIM(REPLACE(sa.single_assignee, '+', ''))
    HAVING ticket_count > 0
    ORDER BY ticket_count DESC
");
$assigneeStatsInProgress = $stmt->fetchAll();

// Hole Zuständigen-Statistiken für abgeschlossene Tickets
$stmt = $db->query("
    WITH RECURSIVE split_assignees AS (
        SELECT 
            t.id,
            SUBSTRING_INDEX(SUBSTRING_INDEX(COALESCE(t.assignee, 'Nicht zugewiesen'), ',', n.n), ',', -1) as single_assignee
        FROM tickets t
        CROSS JOIN (
            SELECT a.N + b.N * 10 + 1 n
            FROM (SELECT 0 N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
            CROSS JOIN (SELECT 0 N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
            ORDER BY n
        ) n
        WHERE n.n <= 1 + LENGTH(COALESCE(t.assignee, 'Nicht zugewiesen')) - LENGTH(REPLACE(COALESCE(t.assignee, 'Nicht zugewiesen'), ',', ''))
    )
    SELECT 
        TRIM(REPLACE(sa.single_assignee, '+', '')) as assignee,
        COUNT(DISTINCT t.id) as ticket_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    JOIN split_assignees sa ON t.id = sa.id
    WHERE ts.filter_category IN ('ready', 'geschlossen')
    GROUP BY TRIM(REPLACE(sa.single_assignee, '+', ''))
    HAVING ticket_count > 0
    ORDER BY ticket_count DESC
");
$assigneeStatsCompleted = $stmt->fetchAll();

// Berechne die Nachbarschafts-Statistiken (ohne archivierte)
$stmt = $db->query("
    SELECT 
        COUNT(t.id) as total_tickets,
        COALESCE(SUM(COALESCE(t.affected_neighbors, 0)), 0) as total_neighbors,
        COALESCE(AVG(COALESCE(t.affected_neighbors, 0)), 0) as avg_neighbors
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE ts.filter_category != 'archiviert'
");
$neighborStats = $stmt->fetch();
$totalNeighbors = (int)$neighborStats['total_neighbors'];
$avgNeighbors = round((float)$neighborStats['avg_neighbors'], 1);

// Bereite Daten für das Status-Chart vor
$labels = [];
$data = [];
$colors = [];
$borderColors = [];
foreach ($statusStats as $stat) {
    $labels[] = $stat['name'];
    $data[] = (int)$stat['ticket_count'];
    
    // Konvertiere die Hex-Farbe in rgba für Transparenz
    $hex = ltrim($stat['background_color'], '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $colors[] = "rgba($r, $g, $b, 0.5)";
    $borderColors[] = "rgba($r, $g, $b, 1)";
}

// Bereite Daten für das Zuständigen-Chart (in Bearbeitung) vor
$assigneeLabelsInProgress = [];
$assigneeDataInProgress = [];
foreach ($assigneeStatsInProgress as $stat) {
    $assigneeLabelsInProgress[] = $stat['assignee'];
    $assigneeDataInProgress[] = (int)$stat['ticket_count'];
}

// Bereite Daten für das Zuständigen-Chart (abgeschlossen) vor
$assigneeLabelsCompleted = [];
$assigneeDataCompleted = [];
foreach ($assigneeStatsCompleted as $stat) {
    $assigneeLabelsCompleted[] = $stat['assignee'];
    $assigneeDataCompleted[] = (int)$stat['ticket_count'];
}

// Seitentitel setzen
$pageTitle = 'Statistiken';
require_once __DIR__ . '/includes/header.php';
?>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<!-- JavaScript-Bibliotheken -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<div class="container mt-4">
    <div class="row">
        <div class="col">
            <h1>SAUS-ES Statistiken</h1>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-bar-chart-fill text-primary"></i>
                        Tickets nach Status
                    </h5>
                    <div style="height: 400px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-person-fill-gear text-warning"></i>
                        Tickets aktuell in Bearbeitung durch
                    </h5>
                    <div style="height: 400px;">
                        <canvas id="assigneeChartInProgress"></canvas>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-person-check-fill text-success"></i>
                        Abgeschlossene Aufgaben pro Zuständiger
                    </h5>
                    <div style="height: 400px;">
                        <canvas id="assigneeChartCompleted"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-people-fill text-success"></i>
                        Nachbarschaftshilfe
                    </h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body text-center">
                                    <h3 class="card-title text-primary"><?= $totalNeighbors ?></h3>
                                    <p class="card-text text-primary">
                                        <i class="bi bi-people-fill"></i><br>
                                        Nachbarn insgesamt geholfen
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body text-center">
                                    <h3 class="card-title text-success"><?= $avgNeighbors ?></h3>
                                    <p class="card-text text-success">
                                        <i class="bi bi-calculator"></i><br>
                                        Nachbarn pro Ticket (Durchschnitt)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket-Liste Modal -->
<div class="modal fade" id="ticketListModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tickets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="ticketTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titel</th>
                                <th>Status</th>
                                <th>Zuständig</th>
                                <th>Erstellt</th>
                                <th>Betroffene Nachbarn</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Ticket-Tabelle initialisieren
let ticketTable = new DataTable('#ticketTable', {
    language: {
        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/de-DE.json'
    }
});

// Funktion zum Laden der Tickets
async function loadTickets(filterType, filterValue) {
    try {
        const response = await fetch('api/get_tickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ filterType, filterValue })
        });
        
        if (!response.ok) throw new Error('Netzwerk-Antwort war nicht ok');
        
        const tickets = await response.json();
        
        // Tabelle leeren und neu befüllen
        ticketTable.clear();
        tickets.forEach(ticket => {
            ticketTable.row.add([
                `<a href="ticket_view.php?id=${ticket.id}">#${ticket.id}</a>`,
                ticket.title,
                ticket.status,
                ticket.assignee || 'Nicht zugewiesen',
                new Date(ticket.created_at).toLocaleDateString('de-DE'),
                ticket.affected_neighbors || '0'
            ]);
        });
        ticketTable.draw();
        
        // Modal öffnen
        const modal = new bootstrap.Modal(document.getElementById('ticketListModal'));
        modal.show();
    } catch (error) {
        console.error('Fehler:', error);
        alert('Fehler beim Laden der Tickets');
    }
}

// Gemeinsame Optionen für alle Charts
const baseChartOptions = {
    maintainAspectRatio: true,
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    },
    plugins: {
        legend: {
            display: false
        }
    }
};

// Handler-Funktionen für die verschiedenen Chart-Typen
function handleStatusChartClick(chart, event, elements) {
    if (!elements || elements.length === 0) return;
    const index = elements[0].index;
    const label = chart.data.labels[index];
    loadTickets('status', label);
}

function handleAssigneeInProgressClick(chart, event, elements) {
    if (!elements || elements.length === 0) return;
    const index = elements[0].index;
    const label = chart.data.labels[index];
    loadTickets('assignee_in_progress', label);
}

function handleAssigneeCompletedClick(chart, event, elements) {
    if (!elements || elements.length === 0) return;
    const index = elements[0].index;
    const label = chart.data.labels[index];
    loadTickets('assignee_completed', label);
}

// Status-Chart erstellen
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Anzahl Tickets',
            data: <?= json_encode($data) ?>,
            backgroundColor: <?= json_encode($colors) ?>,
            borderColor: <?= json_encode($borderColors) ?>,
            borderWidth: 1
        }]
    },
    options: {
        ...baseChartOptions,
        onClick: function(event, elements) {
            handleStatusChartClick(this, event, elements);
        }
    }
});

// Zuständigen-Chart (in Bearbeitung) erstellen
const ctxAssigneeInProgress = document.getElementById('assigneeChartInProgress').getContext('2d');
new Chart(ctxAssigneeInProgress, {
    type: 'bar',
    data: {
        labels: <?= json_encode($assigneeLabelsInProgress) ?>,
        datasets: [{
            label: 'Anzahl Tickets',
            data: <?= json_encode($assigneeDataInProgress) ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.5)',   // Orange
            borderColor: 'rgba(255, 159, 64, 1)',
            borderWidth: 1
        }]
    },
    options: {
        ...baseChartOptions,
        onClick: function(event, elements) {
            handleAssigneeInProgressClick(this, event, elements);
        }
    }
});

// Zuständigen-Chart (abgeschlossen) erstellen
const ctxAssigneeCompleted = document.getElementById('assigneeChartCompleted').getContext('2d');
new Chart(ctxAssigneeCompleted, {
    type: 'bar',
    data: {
        labels: <?= json_encode($assigneeLabelsCompleted) ?>,
        datasets: [{
            label: 'Anzahl Tickets',
            data: <?= json_encode($assigneeDataCompleted) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',   // Türkis
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        ...baseChartOptions,
        onClick: function(event, elements) {
            handleAssigneeCompletedClick(this, event, elements);
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
