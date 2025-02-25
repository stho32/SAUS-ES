# SAUS-ES

Ein webbasiertes Ticket-System mit Abstimmungs- und Kommentarfunktionen, optimiert für kleine Gruppen mit hohem gegenseitigen Vertrauen. Das System wurde nach dem Mobile-First-Prinzip entwickelt und bietet eine optimale Nutzererfahrung auf allen Geräten.

## Features

- **Ticket-Management**
  - Erstellen und Verwalten von Tickets
  - Status-Tracking mit farblicher Kennzeichnung
  - Mehrere Zuständige pro Ticket (komma- oder plus-getrennt)
  - Erfassung betroffener Nachbarn

- **Kommentar-System**
  - Mehrere Kommentare pro Ticket
  - Chronologische Anzeige
  - Abstimmungsmöglichkeit pro Kommentar (👍/👎)
  - Bearbeitungshistorie für Kommentare
  - Sichtbarkeitssteuerung für Kommentare

- **Statistik-Dashboard**
  - Interaktive Grafiken mit Chart.js
  - Ticket-Verteilung nach Status
  - Zuständigen-Übersicht für Tickets in Bearbeitung
  - Abgeschlossene Aufgaben pro Zuständiger
  - Klickbare Balken für detaillierte Ticket-Listen
  - Unterstützung für kombinierte Zuständige in der Auswertung

- **Partner-System**
  - Generierung von Partner-Links pro Ticket
  - Komma-separierte Liste aller Partner
  - Eingeschränkter Zugriff für Partner

- **Master-Link-System**
  - Zentrale Zugriffskontrolle
  - Tracking der letzten Nutzung

## Technische Anforderungen

- PHP 8.0 oder höher
- MySQL/MariaDB mit PDO-Unterstützung

- Bootstrap 5.x
- Chart.js 3.x für Statistik-Visualisierungen
- DataTables 1.11.5 für interaktive Tabellen
- Moderner Browser mit JavaScript-Unterstützung

## Installation

1. Klonen Sie das Repository
2. Kopieren Sie die Konfigurationsvorlage:
   ```bash
   cp php/config.example.php php/config.php
   ```
3. Passen Sie die Datenbankverbindung in `php/config.php` an
4. Führen Sie die SQL-Migrationen in numerischer Reihenfolge aus:
   ```bash
   mysql -u [user] -p [database] < mysql/01_tables.sql
   mysql -u [user] -p [database] < mysql/02_add_description.sql
   # Führen Sie alle weiteren Migrations-Dateien aus
   ```

## Nutzung

### Ticket erstellen
1. Öffnen Sie die Anwendung mit einem gültigen Master-Link
2. Klicken Sie auf "Neues Ticket"
3. Füllen Sie Titel und Beschreibung aus
4. Optional: Fügen Sie mehrere Zuständige hinzu (getrennt durch Komma oder Plus)
5. Geben Sie betroffene Nachbarn an

### Statistiken und Auswertungen
1. Öffnen Sie die Statistik-Seite über die Navigation
2. Sehen Sie die Verteilung der Tickets nach Status
3. Analysieren Sie die Zuständigen-Übersicht
4. Klicken Sie auf einen Balken für detaillierte Ticket-Listen
5. Filtern und sortieren Sie die Ticket-Listen nach Bedarf

### Kommentare und Abstimmungen
1. Öffnen Sie ein Ticket
2. Fügen Sie Kommentare hinzu
3. Steuern Sie die Sichtbarkeit der Kommentare
4. Stimmen Sie für Kommentare (👍/👎)

### Partner-Links
1. Öffnen Sie ein Ticket
2. Nutzen Sie das Partner-Formular
3. Geben Sie die Partner-Namen ein
4. Teilen Sie den generierten Link

## Design-Prinzipien

- **Progressive Enhancement**
  - Basis-Funktionalität auch ohne JavaScript
  - Erweiterte Features bei modernen Browsern
  - Interaktive Datenvisualisierungen mit Fallbacks

- **Responsive Design**
  - Optimiert für alle Bildschirmgrößen
  - Touch-freundliche Bedienelemente
  - Responsive Charts mit automatischer Größenanpassung
  - Optimierte Tabellen-Darstellung auf mobilen Geräten

- **Performance**
  - Minimierte CSS/JS-Dateien
  - Effiziente Datenabfragen für Statistiken
  - Asynchrone Aktualisierung von Diagrammen
  - Optimierte Datenbank-Indizes

## Sicherheit

- Strikte Typisierung in PHP (declare(strict_types=1))
- PDO mit vorbereiteten Statements
- XSS-Schutz durch HTML-Escaping
- CSRF-Schutz für Formulare
- Zugriffskontrolle über Master-Links
