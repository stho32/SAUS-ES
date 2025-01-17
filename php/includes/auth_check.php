<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Database.php';

// Liste der Seiten, die ohne Auth zugänglich sein sollen
$publicPages = [
    'error.php',
    'logout.php'
];

// Aktuelle Seite ermitteln
$currentScript = basename($_SERVER['SCRIPT_NAME']);

// Wenn die Seite nicht public ist, Auth prüfen
if (!in_array($currentScript, $publicPages)) {
    requireMasterLink();
}
