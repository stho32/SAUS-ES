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
    <title>SAUS-ES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $basePath ?>/">SAUS-ES</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/index.php">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $basePath ?>/ticket_create.php">Neues Ticket</a>
                    </li>
                </ul>
                <?php if (getCurrentUsername()): ?>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                        Angemeldet als: <?= htmlspecialchars(getCurrentUsername()) ?>
                    </span>
                    <a href="<?= $basePath ?>/logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Abmelden
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
