<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// Bestimme das Basis-URL-Verzeichnis
$basePath = dirname($_SERVER['PHP_SELF']);

// Führe Logout durch
logout();

// Weiterleitung zur Startseite
header('Location: ' . $basePath . '/index.php');
exit;
