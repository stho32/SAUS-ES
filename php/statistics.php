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
    SELECT ts.name, ts.filter_category, COUNT(t.id) as ticket_count
    FROM ticket_status ts
    LEFT JOIN tickets t ON t.status_id = ts.id
    WHERE ts.is_active = 1 
    AND ts.filter_category != 'archiviert'
    GROUP BY ts.id
    ORDER BY ts.id
");
$statusStats = $stmt->fetchAll();

// Berechne die Nachbarschafts-Statistiken (ohne archivierte)
$stmt = $db->query("
    SELECT 
        COUNT(t.id) as total_tickets,
        COALESCE(SUM(t.affected_neighbors), 0) as total_neighbors,
        COALESCE(AVG(NULLIF(t.affected_neighbors, 0)), 0) as avg_neighbors
    FROM tickets t
    JOIN ticket_status ts ON t.status_id = ts.id
    WHERE ts.filter_category != 'archiviert'
    AND t.affected_neighbors IS NOT NULL
");
$neighborStats = $stmt->fetch();
$totalNeighbors = (int)$neighborStats['total_neighbors'];
$avgNeighbors = round((float)$neighborStats['avg_neighbors'], 1);

// Bereite Daten für das Chart vor
$labels = [];
$data = [];
foreach ($statusStats as $stat) {
    $labels[] = $stat['name'];
    $data[] = (int)$stat['ticket_count'];
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col">
            <h1>SAUS-ES Statistiken</h1>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tickets nach Status</h5>
                    <div style="height: 400px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Nachbarschaftshilfe</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3 class="card-title"><?= $totalNeighbors ?></h3>
                                    <p class="card-text text-muted">
                                        <i class="bi bi-people-fill"></i><br>
                                        Nachbarn insgesamt geholfen
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h3 class="card-title"><?= $avgNeighbors ?></h3>
                                    <p class="card-text text-muted">
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

<!-- Chart.js einbinden -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart erstellen
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Anzahl Tickets',
            data: <?= json_encode($data) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
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
