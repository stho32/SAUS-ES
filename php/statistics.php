<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Database.php';

// Prüfe Master-Link
requireMasterLink();

// Header einbinden
require_once __DIR__ . '/includes/header.php';

// Hole die Statistiken
$db = Database::getInstance()->getConnection();

// Hole Status-Statistiken (ohne archiviert)
$stmt = $db->query("
    SELECT ts.name, ts.filter_category, ts.background_color, COUNT(t.id) as ticket_count
    FROM ticket_status ts
    LEFT JOIN tickets t ON t.status_id = ts.id
    WHERE ts.is_active = 1 
    AND ts.filter_category != 'archiviert'
    GROUP BY ts.id
    ORDER BY ts.sort_order
");
$statusStats = $stmt->fetchAll();

// Hole Zuständigen-Statistiken für Tickets in Bearbeitung
$stmt = $db->query("
    SELECT 
        COALESCE(t.assignee, 'Nicht zugewiesen') as assignee,
        COUNT(*) as ticket_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE ts.filter_category = 'in_bearbeitung'
    GROUP BY t.assignee
    HAVING ticket_count > 0
    ORDER BY ticket_count DESC
");
$assigneeStatsInProgress = $stmt->fetchAll();

// Hole Zuständigen-Statistiken für abgeschlossene Tickets
$stmt = $db->query("
    SELECT 
        COALESCE(t.assignee, 'Nicht zugewiesen') as assignee,
        COUNT(*) as ticket_count
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE ts.filter_category IN ('ready', 'geschlossen')
    GROUP BY t.assignee
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
?>

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
        </div>
    </div>
</div>

<!-- Chart.js einbinden -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
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
        responsive: true,
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
        responsive: true,
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
            backgroundColor: 'rgba(40, 167, 69, 0.5)',    // Grün
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
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
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
