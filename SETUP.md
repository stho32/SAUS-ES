# SAUS-ES Setup Anleitung

## Voraussetzungen

- PHP 8.1 oder höher
- MariaDB 10.5 oder höher
- Webserver (z.B. Apache) mit PHP-Unterstützung
- mod_rewrite aktiviert (für saubere URLs)

## Installation

### 1. Datenbank Setup

1. Erstellen Sie eine neue Datenbank für SAUS-ES:
```sql
CREATE DATABASE saus_es CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Erstellen Sie einen Datenbankbenutzer:
```sql
CREATE USER 'saus_user'@'localhost' IDENTIFIED BY 'IhrPasswort';
GRANT ALL PRIVILEGES ON saus_es.* TO 'saus_user'@'localhost';
FLUSH PRIVILEGES;
```

3. Importieren Sie die SQL-Dateien in folgender Reihenfolge:
```bash
mysql -u saus_user -p saus_es < mysql/01_tables.sql
mysql -u saus_user -p saus_es < mysql/02_initial_data.sql
```

### 2. PHP-Dateien Setup

1. Kopieren Sie den gesamten Inhalt des `php`-Ordners in Ihr Webverzeichnis
2. Kopieren Sie die `config.example.php` zu `config.php`:
```bash
cp config.example.php config.php
```

3. Bearbeiten Sie die `config.php` und passen Sie die Datenbankverbindungsdaten an

### 3. Webserver Konfiguration

Beispiel Apache VirtualHost Konfiguration:
```apache
<VirtualHost *:80>
    ServerName saus-es.local
    DocumentRoot /pfad/zu/ihrer/installation
    
    <Directory /pfad/zu/ihrer/installation>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Master-Link Setup

Nach der Installation wird automatisch ein initialer Master-Link erstellt:
- Link-Code: `initial_master_2024`
- Zugriff über: `https://ihre-domain.de/index.php?master_code=initial_master_2024`

**Wichtig**: Ändern Sie den Master-Link nach der Installation:

1. Verbinden Sie sich mit der Datenbank:
```sql
USE saus_es;
-- Deaktivieren Sie den alten Master-Link
UPDATE master_links SET is_active = FALSE WHERE link_code = 'initial_master_2024';
-- Erstellen Sie einen neuen Master-Link
INSERT INTO master_links (link_code, description, is_active) 
VALUES ('ihr_sicherer_code', 'Produktiv Master-Link', TRUE);
```

## Erste Schritte

1. Öffnen Sie die Anwendung mit dem Master-Link in Ihrem Browser
2. Geben Sie Ihr Namenskürzel ein
3. Sie können nun:
   - Tickets erstellen und verwalten
   - Diskussionspartner-Links generieren
   - Abstimmungen durchführen

## Fehlerbehebung

### Häufige Probleme

1. **Datenbank-Verbindungsfehler**
   - Überprüfen Sie die Zugangsdaten in der `config.php`
   - Stellen Sie sicher, dass der Datenbankbenutzer die korrekten Rechte hat

2. **Seite nicht gefunden (404)**
   - Überprüfen Sie, ob mod_rewrite aktiviert ist
   - Stellen Sie sicher, dass die .htaccess-Datei korrekt kopiert wurde

3. **Keine Berechtigungen**
   - Überprüfen Sie die Dateiberechtigungen im Webverzeichnis
   - Standard: 755 für Ordner, 644 für Dateien

4. **Master-Link funktioniert nicht**
   - Überprüfen Sie, ob der Link-Code in der Datenbank aktiv ist
   - Stellen Sie sicher, dass der Link-Code korrekt übermittelt wird
