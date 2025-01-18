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
    
    // Hole alle öffentlichen Tickets mit ihren Status
    $stmt = $db->prepare("
        SELECT t.id, t.title, t.public_comment, t.created_at, 
               GREATEST(
                   t.created_at,
                   COALESCE(MAX(c.created_at), t.created_at)
               ) as last_activity,
               ts.name as status_name, ts.background_color as status_color
        FROM tickets t
        JOIN ticket_status ts ON t.status_id = ts.id
        LEFT JOIN comments c ON t.id = c.ticket_id
        WHERE t.show_on_website = TRUE
        GROUP BY t.id
        ORDER BY t.id DESC
    ");
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
                        <div class="card">
                            <div class="card-header">
                                <div class="ticket-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="ticket-number">Vorgang #<?= $ticket['id'] ?></span>
                                        <span class="status-badge" style="background-color: <?= htmlspecialchars($ticket['status_color']) ?>">
                                            <?= htmlspecialchars($ticket['status_name']) ?>
                                        </span>
                                    </div>
                                    <h3 class="ticket-title"><?= htmlspecialchars($ticket['title']) ?></h3>
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
</body>
</html>
