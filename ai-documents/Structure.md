# SAUS-ES Anwendungsstruktur

## Überblick

SAUS-ES ist ein webbasiertes Ticket-System mit Abstimmungs- und Kommentarfunktionen, entwickelt für kleine Gruppen mit hohem gegenseitigen Vertrauen. Die Anwendung folgt dem Mobile-First-Prinzip und bietet optimale Nutzererfahrung auf allen Geräten.

## Hauptverzeichnisstruktur

- **/.git**: Git-Repository-Dateien
- **/.htaccess**: Apache-Konfigurationsdatei für URL-Rewrites und Sicherheitseinstellungen
- **/ai-documents**: Dokumentationsverzeichnis (Anforderungen und Struktur)
- **/logs**: Verzeichnis für Anwendungs- und Fehler-Logs
- **/mysql**: SQL-Migrationsdateien für Datenbankstruktur und -updates
- **/php**: Hauptanwendungscode des Ticket-Systems
- **/public_php_app**: Separater öffentlicher Zugriffspunkt (u.a. für Bildergalerie)

## Detaillierte Verzeichnisstruktur

### /mysql

Enthält SQL-Migrationsdateien in chronologischer Reihenfolge:

- **01_tables.sql**: Initiale Tabellenerstellung
- **02_add_description.sql** & **02_initial_data.sql**: Beschreibungsfelder und Grunddaten
- **03-14_*.sql**: Inkrementelle Datenbankupdates, unter anderem:
  - Kommentarsichtbarkeit
  - Status-Farbcodierung
  - Filterkategorien
  - Zuständigenfelder
  - Abstimmungsfunktionen
  - Website-Felder
  - Nachbarschaftsinformationen
  - Anhänge
  - SecretString für externe Bildanzeige
- **15_add_follow_up_date.sql**: Hinzufügen eines Wiedervorlagedatums zu Tickets

Hinweis: Die Migrations werden ausgeführt, aber nicht in der Datenbank protokolliert. Es gibt keine Tabelle zur Verfolgung der angewendeten Migrationen.

### /php

Hauptanwendungsverzeichnis mit folgenden Unterverzeichnissen und Dateien:

#### /php/api

REST-API-Endpunkte für AJAX-Aufrufe:

- **add_comment.php**: Hinzufügen neuer Kommentare
- **create_partner.php**: Generierung von Partner-Links
- **edit_comment.php**: Bearbeitung bestehender Kommentare
- **get_tickets.php**: Abrufen von Ticket-Listen für Statistiken
- **get_votes.php**: Abrufen von Abstimmungsdaten
- **toggle_comment_visibility.php**: Ändern der Kommentarsichtbarkeit
- **update_ticket*.php**: Verschiedene Ticket-Update-Funktionen
- **upload_attachment.php** & **get_attachment.php**: Anhang-Verwaltung
- **vote.php** & **vote_ticket.php**: Abstimmungsfunktionen

#### /php/assets

Statische Ressourcen für die Anwendung:
- CSS-Dateien
- JavaScript-Dateien
- Bilder und Icons

#### /php/includes

Wiederverwendbare Funktionskomponenten und Hilfsdateien:

- **Database.php**: Datenbankverbindungs- und Abfragefunktionalität
- **api_auth_check.php** & **auth_check.php**: Authentifizierungsprüfungen
- **auth.php**: Authentifizierungslogik
- **attachment_functions.php**: Funktionen zur Anhangsverwaltung 
- **comment_functions.php** & **comment_formatter.php**: Kommentarlogik
- **error_logger.php**: Fehlerprotokollierung
- **functions.php**: Allgemeine Hilfsfunktionen
- **header.php** & **footer.php**: Gemeinsame Seitenelemente
- **ticket_functions.php**: Ticket-spezifische Funktionen
- **paths_config.example.php**: Konfigurationsvorlage für Pfade

#### /php/templates

Vorlagen für wiederverwendbare UI-Komponenten:
- Formularelemente
- Komponentenbausteine

#### /php/uploads

Verzeichnis für hochgeladene Dateien/Anhänge

#### Hauptanwendungsdateien im /php Verzeichnis

- **index.php**: Hauptseite mit Ticket-Liste und Filter
- **ticket_view.php**: Detailansicht eines Tickets mit Kommentaren
- **ticket_edit.php**: Formular zur Bearbeitung von Tickets
- **create_ticket.php**: Formular zur Erstellung neuer Tickets
- **statistics.php**: Statistik-Dashboard mit Chart.js-Visualisierungen
- **saus_news.php**: Neuigkeiten- oder Ankündigungsseite
- **ticket_email.php**: E-Mail-Ansicht mit externen Bild-Links
- **logout.php**: Abmelden von der Anwendung
- **error.php**: Fehlerseite
- **config.example.php**: Konfigurationsvorlage

### /public_php_app

Öffentlich zugängliche Anwendungsteile mit reduzierter Sicherheitsanforderung:

#### /public_php_app/imageview

Bildergalerie für externe Betrachter:
- **index.php**: Einstiegspunkt für die Bildergalerie
- Zugriff über SecretString-Parameter ohne weitere Authentifizierung

#### /public_php_app/includes

Hilfskomponenten für die öffentliche Anwendung

## Datenbankstruktur

Die Datenbank verwendet mehrere Tabellen:

- **tickets**: Speichert Ticket-Informationen (ID, Nummer, Titel, Status, Erstellungsdatum, etc.)
- **comments**: Kommentare zu Tickets mit Inhalt und Metadaten
- **comment_votes**: Abstimmungen zu Kommentaren (up/down)
- **ticket_status**: Verfügbare Ticket-Status-Optionen
- **master_links**: Authentifizierungs-Links für Zugriffskontrolle
- **partners**: Partner-Informationen und Links pro Ticket

Das System nutzt außerdem Views (z.B. comment_statistics), Funktionen (z.B. get_ticket_partners) und Trigger zur automatischen Datenpflege.

## Authentifizierung und Sicherheit

- Session-basierte Authentifizierung
- Master-Link-System für zentralen Zugriff
- Partner-Link-System für eingeschränkten Zugriff
- SecretString für Bildanzeige ohne volle Authentifizierung
- XSS-Schutz und SQL-Injection-Prävention
- CSRF-Schutz für Formulare

## Mobile-First Design

Die Anwendung nutzt:
- Responsive Design-Prinzipien mit Bootstrap 5
- Touch-optimierte Bedienelemente
- Performance-Optimierungen für mobile Geräte
- Viewport-Konfiguration für unterschiedliche Bildschirmgrößen

## Spezielle Anforderungen

### REQ0001: Externe Links für Bilder in Tickets

- SecretString-Implementierung für Tickets
- Bildergalerie in public_php_app/imageview
- E-Mail-Integration mit Bildlinks in ticket_email.php

### REQ0002: Wiedervorlagedatum

- Dateispezifikation vorhanden, aber noch keine Implementierung identifiziert

## Deployment und Setup

Die Anwendung wird mit einer detaillierten Setup-Anleitung (SETUP.md) ausgeliefert, die Folgendes umfasst:
- Systemvoraussetzungen (PHP 8.4+, MariaDB 10.5+)
- Datenbankeinrichtung
- Konfigurationsdateien
- Apache-Einstellungen
- Wartungsinformationen