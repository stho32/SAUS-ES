<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

// Bestimme das Basis-URL-Verzeichnis
$basePath = dirname($_SERVER['PHP_SELF']);
if (substr($basePath, -8) === '/includes') {
    $basePath = dirname($basePath);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#007bff">
    <title>SAUS-i</title>
    <link href="<?= $basePath ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>/assets/css/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $basePath ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .activity-0 { background-color: #e6ffe6 !important; }  /* Hellgrün */
        .activity-1 { background-color: #e8ffe3 !important; }
        .activity-2 { background-color: #ebffe0 !important; }
        .activity-3 { background-color: #edffdd !important; }
        .activity-4 { background-color: #f0ffda !important; }
        .activity-5 { background-color: #f2ffd7 !important; }
        .activity-6 { background-color: #f5ffd4 !important; }
        .activity-7 { background-color: #f7ffd1 !important; }
        .activity-8 { background-color: #fafcce !important; }
        .activity-9 { background-color: #fcf9cb !important; }
        .activity-10 { background-color: #fff6c8 !important; }
        .activity-11 { background-color: #fff3c5 !important; }
        .activity-12 { background-color: #fff0c2 !important; }
        .activity-13 { background-color: #ffedbf !important; }
        .activity-14 { background-color: #ffeabc !important; }
        .activity-old { background-color: #ffe6e6 !important; }  /* Hellrot */
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="./">SAUS-i</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="./index.php">
                            <i class="bi bi-list-ul"></i> Übersicht
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./create_ticket.php">
                            <i class="bi bi-plus-lg"></i> Neues Ticket
                        </a>
                    </li>
                </ul>
                <?php if (getCurrentUsername()): ?>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                        <i class="bi bi-person"></i> <?= htmlspecialchars(getCurrentUsername()) ?>
                    </span>
                    <a href="./logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Abmelden
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="mt-4">
