<?php
/**
 * Konfigurationsdatei für die Bildergalerie-Ansicht
 * 
 * Diese Konfiguration verwendet die gleichen Datenbankdaten wie die öffentliche App.
 * Die Datei muss nur angepasst werden, wenn Sie unterschiedliche Datenbankzugangsdaten verwenden möchten.
 */

// Lade die Konfigurationsdatei aus dem übergeordneten Verzeichnis
$configPath = __DIR__ . '/../includes/config.php';

// Versuche die Konfiguration zu laden oder verwende die Beispielkonfiguration
if (file_exists($configPath)) {
    return require $configPath;
} else {
    return require __DIR__ . '/../includes/config.example.php';
}
