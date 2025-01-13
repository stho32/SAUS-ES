<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// Bestimme das Basis-URL-Verzeichnis
$basePath = dirname($_SERVER['PHP_SELF']);

// Führe Logout durch
logout();

// Zeige Abschiedsseite
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 text-center">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    <i class="bi bi-hand-wave text-primary"></i> 
                    Auf Wiedersehen!
                </h2>
                <p class="card-text mb-4">
                    Du wurdest erfolgreich abgemeldet.<br>
                    Danke, dass du da warst!
                </p>
                <a href="<?= $basePath ?>/index.php" class="btn btn-primary">
                    <i class="bi bi-house-door"></i> 
                    Zurück zur Startseite
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
