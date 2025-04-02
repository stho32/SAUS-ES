<?php
/**
 * Konfigurationsdatei für Pfade zwischen verschiedenen Anwendungsteilen
 * Diese Einstellungen können je nach Server-Konfiguration angepasst werden
 */

return [
    // Basis-URL zur öffentlichen Anwendung (public_php_app)
    'public_app_url' => '../public_php_app',
    
    // URL zur Bildergalerie-Ansicht
    'image_gallery_url' => '../public_php_app/imageview',
    
    // Lokaler Dateipfad zum Wurzelverzeichnis der Anwendung
    // (Wird für den Zugriff auf Dateien auf dem Server verwendet)
    'root_path' => __DIR__ . '/../..',
];
