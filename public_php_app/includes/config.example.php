<?php
/**
 * Beispielkonfiguration für die öffentliche SAUS-Anwendung
 * 
 * Anleitung:
 * 1. Kopieren Sie diese Datei zu "config.php"
 * 2. Passen Sie die Werte entsprechend Ihrer Datenbankeinstellungen an
 */

return [
    // Datenbankhost (meist localhost oder eine IP-Adresse)
    'db_host' => 'localhost',

    // Name der Datenbank
    'db_name' => 'beispiel_db',

    // Datenbankbenutzer
    'db_user' => 'db_benutzer',

    // Datenbankpasswort
    'db_password' => 'geheimes_passwort',

    // Pfad zu den News-Bildern auf dem Dateisystem
    // Standard: relativer Pfad vom public_php_app Verzeichnis
    // Auf Webhost: absoluter Pfad anpassen, z.B. '/home/user/saus-es/uploads/news'
    'news_images_path' => __DIR__ . '/../../uploads/news',
];
