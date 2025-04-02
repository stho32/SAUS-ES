<?php
/**
 * Konfigurationsdatei für Pfade zwischen verschiedenen Anwendungsteilen
 * Diese Einstellungen können je nach Server-Konfiguration angepasst werden
 */

return [
    // Pfad zum Hauptordner der Anwendung relativ zum Webroot
    'base_path' => __DIR__ . '/../../',
    
    // Pfad zu den Ticket-Uploads relativ zum Wurzelverzeichnis der Anwendung
    'uploads_path' => 'php/uploads/tickets/'
];
