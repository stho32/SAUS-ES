<?php
declare(strict_types=1);

// Aktiviere Fehleranzeige
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/comment_formatter.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get sort parameter and search query from GET
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'last_activity';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '1';
    
    // Define valid sort options
    $validSortOptions = [
        'last_activity' => 'Letzte Aktivität',
        'title' => 'Titel',
        'created_at' => 'Erstelldatum',
        'status_name' => 'Status'
    ];
    
    // Ensure sort parameter is valid
    if (!array_key_exists($sort, $validSortOptions)) {
        $sort = 'last_activity';
    }
    
    // Configure SQL based on sort option
    $orderBy = match($sort) {
        'title' => 't.title ASC',
        'created_at' => 't.created_at DESC',
        'status_name' => 'ts.name ASC',
        default => 'last_activity DESC'
    };
    
    // Build query to get last activity date for each ticket
    $sql = "
        SELECT 
            t.id, 
            t.title, 
            t.public_comment, 
            t.assignee, 
            t.created_at, 
            GREATEST(
                t.created_at,
                COALESCE((SELECT MAX(c2.created_at) FROM comments c2 WHERE c2.ticket_id = t.id), t.created_at)
            ) as last_activity,
            ts.name as status_name, 
            ts.background_color as status_color
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        WHERE t.show_on_website = TRUE
    ";
    
    // Add search condition if search term is provided
    if (!empty($search)) {
        $sql .= " AND (t.title LIKE :search OR t.public_comment LIKE :search OR t.assignee LIKE :search)";
    }
    
    // Hide inactive tickets (more than 3 months) unless specifically requested
    if (!$showInactive) {
        $sql .= " AND (
            GREATEST(
                t.created_at,
                COALESCE((SELECT MAX(c3.created_at) FROM comments c3 WHERE c3.ticket_id = t.id), t.created_at)
            ) > DATE_SUB(NOW(), INTERVAL 3 MONTH)
        )";
    }
    
    // Complete the query with proper ordering
    $sql .= " ORDER BY " . $orderBy;
    
    $stmt = $db->prepare($sql);
    
    // Bind search parameter if needed
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $tickets = $stmt->fetchAll();

} catch (Exception $e) {
    die('Fehler beim Laden der Vorgänge: ' . $e->getMessage());
}

$pageTitle = "Aktuelle Vorgänge des Siedlungsausschusses";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: transparent;
            padding: 0;
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .container {
            padding: 15px;
            max-width: 100%;
        }
        .card {
            margin-bottom: 1.5rem;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .card-header {
            background: none;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem 1.5rem 1rem;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.5em 1em;
            color: #000 !important;
            opacity: 0.9;
            font-weight: 500;
        }
        .ticket-number {
            color: #0d6efd;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
        }
        .ticket-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0.5rem 0 1rem;
            padding: 0;
            color: #2c3e50;
            line-height: 1.3;
        }
        .assignee {
            font-size: 0.95rem;
            color: #0d6efd;
            margin-top: 0.5rem;
            padding: 0.5rem 0;
            border-top: 1px dashed rgba(0,0,0,0.1);
        }
        .assignee i {
            color: #0d6efd;
            margin-right: 0.5rem;
        }
        .assignee strong {
            font-weight: 600;
        }
        .ticket-header {
            display: flex;
            flex-direction: column;
        }
        .card-body {
            padding: 1.5rem;
            background: linear-gradient(to bottom, #fff, #f8f9fa);
        }
        .public-comment {
            color: #495057;
            font-size: 1rem;
            line-height: 1.6;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            padding: 0.4em 1em;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #000;
            opacity: 0.9;
        }
        .ticket-meta {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.08);
        }
        .ticket-meta i {
            margin-right: 0.3rem;
        }
        .ticket-meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
        }
        .intro-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 1.5rem;
        }
        .intro-list li {
            display: flex;
            align-items: baseline;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .intro-list i {
            margin-right: 0.75rem;
            color: #0d6efd;
        }
        .contact-box {
            background: #e9ecef;
            border-radius: 8px;
            padding: 1.25rem;
            margin: 1rem 0;
            border-left: 4px solid #0d6efd;
        }
        .contact-box h5 {
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem 0;
        }
        .contact-list li {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .contact-list i {
            width: 1.5rem;
            margin-right: 0.5rem;
            color: #0d6efd;
        }
        .contact-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 0.75rem;
            margin-top: 0.75rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            color: #dc3545;
            font-size: 0.9rem;
        }
        .toc-list {
            list-style: disc;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        .toc-list li {
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .toc-list small.text-muted {
            font-style: italic;
            margin-left: 0.3rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <ul class="intro-list">
                    <li>
                        <i class="bi bi-info-circle"></i>
                        Hier sehen Sie die aktuellen Vorgänge, an denen Ihr Siedlungsausschuss für Sie arbeitet
                    </li>
                    <li>
                        <i class="bi bi-people"></i>
                        Sie sind herzlich eingeladen mitzuwirken!
                    </li>
                    <li>
                        <i class="bi bi-calendar-event"></i>
                        Sitzungen finden jeden ersten Nicht-Feiertag-Montag im Monat um 19:30 Uhr im Siedlungsausschuss/Gemeinschaftsraum statt
                    </li>
                </ul>

                <div class="contact-box">
                    <h5><i class="bi bi-chat-dots"></i> Kontaktmöglichkeiten</h5>
                    <ul class="contact-list">
                        <li>
                            <i class="bi bi-chat-dots"></i>
                            <span>Messenger-Gruppen (WhatsApp oder Signal) für schnellen Austausch</span>
                        </li>
                        <li>
                            <i class="bi bi-envelope"></i>
                            <span>E-Mail-Adresse (siehe Aushänge in Ihrem Aufgang)</span>
                        </li>
                    </ul>
                    <div class="contact-note">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>Wichtig:</strong> Bitte geben Sie bei jeder Kontaktaufnahme die Vorgangs-Nr. an!
                    </div>
                </div>

                <h2 class="mt-4">Inhaltsverzeichnis</h2>
                
                <!-- Filters and Search -->
                <div class="card mb-4 p-3" id="filter-section">
                    <form method="get" class="row g-3" id="filter-form">
                        <!-- Sort Dropdown -->
                        <div class="col-md-4">
                            <label for="sort-select" class="form-label"><i class="bi bi-sort-down"></i> Sortierung</label>
                            <select id="sort-select" name="sort" class="form-select">
                                <?php foreach ($validSortOptions as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Search Box -->
                        <div class="col-md-5">
                            <label for="search-box" class="form-label"><i class="bi bi-search"></i> Suche</label>
                            <input type="text" id="search-box" name="search" value="<?= htmlspecialchars($search) ?>" 
                                   class="form-control" placeholder="Suchbegriff eingeben...">
                        </div>
                        
                        <!-- Inactive Toggle -->
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="show-inactive" name="show_inactive" value="1" 
                                       <?= $showInactive ? 'checked' : '' ?>>
                                <label class="form-check-label" for="show-inactive">
                                    Inaktive Vorgänge anzeigen
                                </label>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Anwenden
                            </button>
                            <?php if (!empty($search) || $sort !== 'last_activity' || $showInactive): ?>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Zurücksetzen
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($search)): ?>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> 
                        Suchergebnisse für: <strong><?= htmlspecialchars($search) ?></strong>
                        <a href="?sort=<?= $sort ?><?= $showInactive ? '&show_inactive=1' : '' ?>" 
                           class="btn btn-sm btn-outline-secondary ms-2">Zurücksetzen</a>
                    </div>
                <?php endif; ?>
                
                <ul class="toc-list">
                    <?php foreach ($tickets as $ticket): ?>
                        <li>
                            <a href="#ticket-<?= $ticket['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($ticket['title']) ?> (Vorgang #<?= (string)$ticket['id'] ?>)
                                <?php if (!empty($ticket['assignee'])): ?>
                                    <small class="text-muted">| Zuständig: <?= htmlspecialchars($ticket['assignee']) ?></small>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <hr/>
            
            </div>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aktuell sind keine öffentlichen Vorgänge verfügbar.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="col-12">
                        <div class="card mb-4" id="ticket-<?= $ticket['id'] ?>">
                            <div class="card-header">
                                <div class="ticket-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="ticket-number">Vorgang #<?= $ticket['id'] ?></span>
                                        <span class="status-badge" style="background-color: <?= htmlspecialchars($ticket['status_color']) ?>">
                                            <?= htmlspecialchars($ticket['status_name']) ?>
                                        </span>
                                    </div>
                                    <h3 class="ticket-title"><?= htmlspecialchars($ticket['title']) ?></h3>
                                    <?php if (!empty($ticket['assignee'])): ?>
                                        <div class="assignee"><i class="bi bi-person-badge"></i> <strong>Zuständig:</strong> <?= htmlspecialchars($ticket['assignee']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($ticket['public_comment'])): ?>
                            <div class="card-body">
                                <div class="public-comment"><?= formatComment($ticket['public_comment']) ?></div>
                                <div class="ticket-meta">
                                    <div class="ticket-meta-item">
                                        <i class="bi bi-calendar-plus"></i>
                                        Vorgang erstellt am <?= date('d.m.Y \u\m H:i', strtotime($ticket['created_at'])) ?> Uhr
                                    </div>
                                    <div class="ticket-meta-item">
                                        <i class="bi bi-clock-history"></i>
                                        Letzte Aktivität im Informationssystem: <?= date('d.m.Y \u\m H:i', strtotime($ticket['last_activity'])) ?> Uhr
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-submit form when select or checkbox changes
        document.getElementById('sort-select').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
        
        document.getElementById('show-inactive').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
        
        // Auto-scroll to filter section if filter parameters are present
        document.addEventListener('DOMContentLoaded', function() {
            // Check if any filter parameters are in the URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('sort') || urlParams.has('search') || urlParams.has('show_inactive')) {
                // Scroll to filter section
                const filterSection = document.getElementById('filter-section');
                if (filterSection) {
                    // Use setTimeout to ensure everything is loaded and rendered
                    setTimeout(function() {
                        // Scroll the parent frame (if in iframe) or the window
                        if (window.parent !== window) {
                            // We're in an iframe
                            filterSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            // Also try to notify parent frame to scroll
                            try {
                                window.parent.postMessage({
                                    action: 'scrollToChild',
                                    childPosition: filterSection.getBoundingClientRect().top + window.scrollY
                                }, '*');
                            } catch (e) {
                                // Silent fail if parent communication fails
                            }
                        } else {
                            // Direct window
                            filterSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 200);
                }
            }
        });
    </script>
</body>
</html>
