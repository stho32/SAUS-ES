<?php
declare(strict_types=1);

$currentUsername = getCurrentUsername();
$pageTitle = $pageTitle ?? 'SAUS-ES';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUS-ES - <?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">SAUS-ES</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $_SERVER['SCRIPT_NAME'] === '/index.php' ? 'active' : '' ?>" 
                           href="index.php">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $_SERVER['SCRIPT_NAME'] === '/create_ticket.php' ? 'active' : '' ?>" 
                           href="create_ticket.php">Neues Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $_SERVER['SCRIPT_NAME'] === '/completed_tickets.php' ? 'active' : '' ?>" 
                           href="completed_tickets.php">Abgeschlossene Tickets</a>
                    </li>
                </ul>
                <?php if ($currentUsername): ?>
                <div class="ms-auto">
                    <span class="navbar-text">
                        Angemeldet als: <?= htmlspecialchars($currentUsername) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
