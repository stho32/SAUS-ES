# SAUS-ES (Support- und Abstimmungssystem für Entwicklungs-Systeme)

Ein webbasiertes Ticket-System mit Abstimmungs- und Kommentarfunktionen, optimiert für Entwicklungsteams. Das System wurde nach dem Mobile-First-Prinzip entwickelt und bietet eine optimale Nutzererfahrung auf allen Geräten.

## Features

- **Responsives Mobile-First-Design**
  - Optimiert für Smartphones und Tablets
  - Adaptive Layouts für alle Bildschirmgrößen
  - Touch-freundliche Bedienelemente
  - Schnelle Ladezeiten durch optimierte Assets

- **Ticket-Management**
  - Erstellen und Verwalten von Tickets
  - KI-gestützte Zusammenfassungen
  - Status-Tracking (offen, geschlossen, archiviert)

- **Kommentar-System**
  - Mehrere Kommentare pro Ticket
  - Chronologische Anzeige
  - Abstimmungsmöglichkeit pro Kommentar (👍/👎)
  - Echtzeit-Aktualisierung der Abstimmungen

- **Partner-System**
  - Generierung von Partner-Links pro Ticket
  - Komma-separierte Liste aller Partner
  - Eingeschränkter Zugriff für Partner
  - Automatische Partner-Listen-Aktualisierung

- **Master-Link-System**
  - Zentrale Zugriffskontrolle
  - Tracking der letzten Nutzung
  - Flexible Berechtigungsverwaltung

## Technische Anforderungen

- PHP 8.4 oder höher
- MariaDB 10.5 oder höher
- Apache mit mod_rewrite
- Bootstrap 5.3
- Moderner Browser mit JavaScript-Unterstützung

## Installation

1. Klonen Sie das Repository
2. Führen Sie die SQL-Migrationen aus:
   ```bash
   mysql -u [user] -p [database] < mysql/01_tables.sql
   mysql -u [user] -p [database] < mysql/02_initial_data.sql
   mysql -u [user] -p [database] < mysql/03_add_comment_votes.sql
   mysql -u [user] -p [database] < mysql/04_view_and_functions.sql
   ```
3. Konfigurieren Sie die Datenbankverbindung in `php/config.php`
4. Erstellen Sie einen Master-Link (siehe SETUP.md)

## Nutzung

### Mobile Nutzung
1. Öffnen Sie die Anwendung auf Ihrem Smartphone oder Tablet
2. Nutzen Sie die Touch-optimierte Navigation
3. Erstellen und verwalten Sie Tickets mit natürlichen Touch-Gesten

### Ticket erstellen
1. Öffnen Sie die Anwendung mit einem gültigen Master-Link
2. Klicken/Tippen Sie auf "Neues Ticket"
3. Füllen Sie Titel und Beschreibung aus

### Kommentare und Abstimmungen
1. Öffnen Sie ein Ticket
2. Fügen Sie Kommentare hinzu
3. Stimmen Sie für einzelne Kommentare (👍/👎)
4. Sehen Sie die Abstimmungsstatistiken in Echtzeit

### Partner-Links
1. Öffnen Sie ein Ticket
2. Nutzen Sie das Partner-Formular
3. Geben Sie den Partner-Namen ein
4. Teilen Sie den generierten Link

## Mobile-First-Design-Prinzipien

- **Progressive Enhancement**
  - Basis-Funktionalität auch ohne JavaScript
  - Erweiterte Features bei modernen Browsern
  - Optimierte Performance auf allen Geräten

- **Touch-Optimierung**
  - Große, gut erreichbare Bedienelemente
  - Swipe-Gesten für Navigation
  - Optimierte Formulare für mobile Eingabe

- **Responsive Images**
  - Automatische Bildgrößenanpassung
  - Lazy Loading für bessere Performance
  - WebP-Support mit Fallbacks

- **Performance**
  - Minimierte CSS/JS-Dateien
  - Optimierte Asset-Delivery
  - Schnelle Ladezeiten auch bei 3G

## Sicherheit

- Alle Eingaben werden validiert und escaped
- Prepared Statements für alle Datenbankzugriffe
- Session-basierte Authentifizierung
- XSS-Schutz durch htmlspecialchars

## Lizenz

MIT License - Siehe LICENSE.md für Details
