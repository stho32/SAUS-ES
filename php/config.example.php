<?php
return [
    // Datenbankverbindung
    'db' => [
        'host' => 'localhost',
        'name' => 'saus_es',
        'user' => 'saus_user',
        'pass' => 'IhrPasswort',
        'charset' => 'utf8mb4'
    ],
    
    // Anwendungseinstellungen
    'app' => [
        'name' => 'SAUS-ES',
        'debug' => false,
        'min_votes_required' => 4,
    ],
    
    // Sicherheit
    'security' => [
        'session_lifetime' => 3600, // 1 Stunde
        'cookie_secure' => true,
        'cookie_httponly' => true
    ]
];
