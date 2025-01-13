# Setup-Anleitung

## Systemvoraussetzungen

- PHP 8.4 oder höher
- MariaDB 10.5 oder höher
- Apache mit mod_rewrite aktiviert
- Composer (für Abhängigkeiten)
- Moderner Browser mit Touch-Support
- Viewport-Unterstützung für responsive Darstellung

## Installation

### 1. Datenbank einrichten

```sql
-- Datenbank erstellen
CREATE DATABASE saus_es CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Benutzer erstellen und Rechte vergeben
CREATE USER 'saus_user'@'localhost' IDENTIFIED BY 'IhrPasswort';
GRANT ALL PRIVILEGES ON saus_es.* TO 'saus_user'@'localhost';
FLUSH PRIVILEGES;

-- Migrationen ausführen
mysql -u saus_user -p saus_es < mysql/01_tables.sql
mysql -u saus_user -p saus_es < mysql/02_initial_data.sql
mysql -u saus_user -p saus_es < mysql/03_add_comment_votes.sql
mysql -u saus_user -p saus_es < mysql/04_view_and_functions.sql
```

### 2. Konfigurationsdatei erstellen

Kopieren Sie `config.example.php` nach `config.php` und passen Sie die Werte an:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'saus_es',
        'user' => 'saus_user',
        'pass' => 'IhrPasswort',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'min_votes_required' => 4,
        'session_lifetime' => 3600,
        'debug' => false
    ]
];
```

### 3. Apache-Konfiguration

Aktivieren Sie mod_rewrite und erstellen Sie eine .htaccess-Datei:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Weiterleitung zu HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Verhindern Sie direkten Zugriff auf PHP-Dateien im includes-Verzeichnis
    RewriteRule ^includes/ - [F]
    
    # API-Routing
    RewriteRule ^api/ - [L]
</IfModule>

# Sicherheitseinstellungen
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    
    # Cache-Control für statische Assets
    <FilesMatch "\.(css|js|webp|png|jpg|jpeg|gif|ico)$">
        Header set Cache-Control "public, max-age=31536000"
    </FilesMatch>
</IfModule>

# Kompression aktivieren
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript application/json
</IfModule>
```

### 4. Mobile-First-Setup

1. **Viewport-Konfiguration**
   Fügen Sie in `includes/header.php` hinzu:
   ```php
   <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
   <meta name="theme-color" content="#007bff">
   ```

2. **Touch-Icons**
   Erstellen Sie Icons für verschiedene Plattformen:
   ```bash
   php setup/create_icons.php
   ```

3. **Performance-Optimierung**
   Komprimieren Sie Assets:
   ```bash
   php setup/optimize_assets.php
   ```

4. **PWA-Setup**
   Generieren Sie das Web Manifest:
   ```bash
   php setup/create_manifest.php
   ```

### 4. Master-Link erstellen

Führen Sie das Setup-Skript aus:

```bash
php setup/create_master_link.php
```

Oder fügen Sie manuell einen Master-Link hinzu:

```sql
INSERT INTO master_links (link_code, is_active, created_at)
VALUES (
    'ihr_geheimer_code',
    TRUE,
    CURRENT_TIMESTAMP
);
```

## Erste Schritte

1. **System testen**
   ```bash
   php setup/test_installation.php
   ```

2. **Master-Link verwenden**
   - Öffnen Sie die Anwendung im Browser
   - Verwenden Sie den generierten Master-Link
   - Sie sollten zur Übersichtsseite weitergeleitet werden

3. **Erstes Ticket erstellen**
   - Klicken Sie auf "Neues Ticket"
   - Füllen Sie alle Pflichtfelder aus
   - Das Ticket erscheint in der Übersicht

4. **Partner einladen**
   - Öffnen Sie ein Ticket
   - Nutzen Sie das Partner-Formular
   - Teilen Sie den generierten Link

## Wartung

### Datenbank-Backup

```bash
mysqldump -u saus_user -p saus_es > backup_$(date +%Y%m%d).sql
```

### Logs überprüfen

```bash
tail -f logs/error.log
tail -f logs/access.log
```

### Cache leeren

```bash
php setup/clear_cache.php
```

## Fehlerbehebung

### Häufige Probleme

1. **Datenbank-Verbindung fehlgeschlagen**
   - Überprüfen Sie die Zugangsdaten in `config.php`
   - Stellen Sie sicher, dass MariaDB läuft
   - Prüfen Sie die Benutzerrechte

2. **Master-Link funktioniert nicht**
   - Überprüfen Sie den Link-Code in der Datenbank
   - Stellen Sie sicher, dass der Link aktiv ist
   - Prüfen Sie die Session-Einstellungen

3. **Partner-Links**
   - Überprüfen Sie die Partner-Tabelle
   - Stellen Sie sicher, dass die Trigger aktiv sind
   - Prüfen Sie die automatische Listen-Aktualisierung

4. **Mobile Darstellungsprobleme**
   - Überprüfen Sie die Viewport-Einstellungen
   - Testen Sie verschiedene Bildschirmgrößen
   - Validieren Sie Touch-Events

### Support

Bei Problemen wenden Sie sich an:
- GitHub Issues
- Support-Forum
- E-Mail-Support
