# Implementierungsdetails

## Datenbankstruktur

### Tabellen

1. **tickets**
   - `id`: Primärschlüssel
   - `ticket_number`: Eindeutige Ticketnummer (YYYYMMDD-XXXX)
   - `title`: Tickettitel
   - `ki_summary`: KI-generierte Zusammenfassung
   - `ki_interim`: Optionale Zwischenzusammenfassung
   - `status_id`: Referenz auf ticket_status
   - `created_at`: Erstellungszeitpunkt
   - `updated_at`: Letzter Änderungszeitpunkt
   - `show_on_website`: Boolean-Wert für die Sichtbarkeit auf der Webseite
   - `public_comment`: Öffentlicher Kommentar für die Webseitenanzeige

2. **comments**
   - `id`: Primärschlüssel
   - `ticket_id`: Referenz auf tickets
   - `username`: Benutzername des Kommentators
   - `content`: Kommentarinhalt
   - `created_at`: Erstellungszeitpunkt

3. **comment_votes**
   - `id`: Primärschlüssel
   - `comment_id`: Referenz auf comments
   - `username`: Benutzername des Abstimmenden
   - `value`: 'up' oder 'down'
   - `created_at`: Abstimmungszeitpunkt

4. **ticket_status**
   - `id`: Primärschlüssel
   - `name`: Statusname (offen, geschlossen, archiviert)
   - `description`: Statusbeschreibung

5. **master_links**
   - `id`: Primärschlüssel
   - `link_code`: Eindeutiger Link-Code
   - `is_active`: Aktiv/Inaktiv-Status
   - `last_used_at`: Letzte Verwendung
   - `created_at`: Erstellungszeitpunkt

6. **partners**
   - `id`: Primärschlüssel
   - `ticket_id`: Referenz auf tickets
   - `partner_link`: Eindeutiger Partner-Link
   - `partner_name`: Name des Partners
   - `partner_list`: Komma-separierte Liste aller Partner
   - `created_at`: Erstellungszeitpunkt

### Views

1. **comment_statistics**
   - Aggregierte Statistiken pro Kommentar
   - Up/Down-Votes und Gesamtstimmen
   - Automatisch aktualisiert durch Trigger

### Funktionen

1. **get_ticket_partners(ticket_id)**
   - Gibt formatierte Partner-Liste zurück
   - Automatisch sortiert nach Erstellungsdatum

2. **has_sufficient_positive_votes(ticket_id, min_votes)**
   - Prüft ob genügend positive Stimmen vorhanden sind
   - Berücksichtigt nur Kommentare mit mehr Up- als Down-Votes

### Trigger

1. **update_partner_list_insert**
   - Aktualisiert Partner-Liste bei neuem Partner
   - Sortiert Partner chronologisch

2. **update_partner_list_update**
   - Aktualisiert Partner-Liste bei Änderungen
   - Behandelt Ticket-Wechsel

3. **update_partner_list_delete**
   - Aktualisiert Partner-Liste bei Löschung
   - Entfernt Partner aus der Liste

## Frontend-Technologien

### Mobile-First-Design

#### Viewport-Konfiguration
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#007bff">
```

#### CSS-Breakpoints
```scss
// Mobile First - Standard Breakpoints
$breakpoints: (
  'sm': '576px',   // Smartphones (landscape)
  'md': '768px',   // Tablets
  'lg': '992px',   // Desktops
  'xl': '1200px'   // Large Desktops
);

// Beispiel Media Query Mixin
@mixin respond-to($breakpoint) {
  @media (min-width: map-get($breakpoints, $breakpoint)) {
    @content;
  }
}
```

#### Touch-Optimierung
```scss
// Touch Targets
.btn, .nav-link, .form-control {
  min-height: 44px;  // iOS Standard
  min-width: 44px;   // iOS Standard
  padding: 12px 16px;
  margin: 8px 0;
}

// Remove Hover on Touch Devices
@media (hover: none) {
  .btn:hover {
    background-color: inherit;
  }
}
```

#### Performance-Optimierung
```apache
# Apache Konfiguration für Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
</IfModule>
```

### Framework
   - Bootstrap 5.3
   - Responsive Design
   - Mobile-First Ansatz

### JavaScript
   - Asynchrone API-Calls
   - Dynamische UI-Updates
   - Modal-Dialoge

### CSS
   - Custom Styling
   - Dark/Light Mode Support
   - Barrierefreiheit

## API-Endpunkte

### Ticket-Management
- `GET /ticket_view.php`: Ticket-Details mit Kommentaren
- `POST /create_ticket.php`: Neues Ticket erstellen

### Kommentar-System
- `POST /api/add_comment.php`: Kommentar hinzufügen
- `POST /api/vote.php`: Für Kommentar abstimmen
- `GET /api/get_votes.php`: Abstimmungszahlen abrufen

### Partner-Management
- `POST /api/create_partner.php`: Partner-Link erstellen

## Sicherheitsmaßnahmen

1. **Authentifizierung**
   - Session-basierte Authentifizierung
   - Master-Link-Validierung
   - Partner-Link-Überprüfung

2. **Datenbankzugriff**
   - Prepared Statements
   - Transaktionssicherheit
   - Strikte Typisierung

3. **Eingabevalidierung**
   - Server-seitige Validierung
   - XSS-Schutz
   - SQL-Injection-Prävention

4. **Zugriffskontrolle**
   - Rollenbasierte Berechtigungen
   - Partner-spezifische Einschränkungen
   - Aktions-Logging

## Performance-Optimierungen

1. **Datenbank**
   - Optimierte Indizes
   - Materialisierte Views
   - Effiziente Trigger

2. **Caching**
   - Server-seitiges Caching
   - Browser-Caching
   - API-Response-Caching

3. **Code**
   - Strict Types
   - Optimierte Queries
   - Lazy Loading

## PHP-Codebasis-Organisation

### Hauptseiten
- `index.php` - Haupteinstiegspunkt
- `ticket_view.php` - Ticket-Ansichtsseite
- `ticket_edit.php` - Ticket-Bearbeitungsseite
- `create_ticket.php` - Ticket-Erstellungsseite
- `error.php` - Fehlerbehandlungsseite
- `logout.php` - Abmeldefunktionalität

### API-Endpunkte (`api/`)
1. **Ticket-Operationen**
   - `update_ticket.php` - Ticket aktualisieren
   - `update_ticket_status.php` - Ticketstatus ändern
   - `update_ticket_assignee.php` - Ticketzuweisung ändern
   - `vote_ticket.php` - Für Ticket abstimmen

2. **Kommentar-Operationen**
   - `add_comment.php` - Kommentar hinzufügen
   - `edit_comment.php` - Kommentar bearbeiten
   - `format_comment.php` - Kommentarformatierung
   - `toggle_comment_visibility.php` - Kommentarsichtbarkeit umschalten

3. **Abstimmungssystem**
   - `vote.php` - Abstimmungsfunktionalität
   - `get_votes.php` - Abstimmungen abrufen

4. **Partner-Verwaltung**
   - `create_partner.php` - Partner erstellen

### Includes (`includes/`)
1. **Kernfunktionalität**
   - `Database.php` - Datenbankverbindung und -operationen
   - `functions.php` - Allgemeine Hilfsfunktionen

2. **Authentifizierung**
   - `auth.php` - Authentifizierungslogik
   - `auth_check.php` - Authentifizierungsprüfung
   - `api_auth_check.php` - API-Authentifizierungsprüfung

3. **Layout-Komponenten**
   - `header.php` - Kopfzeilenkomponente
   - `footer.php` - Fußzeilenkomponente

4. **Dienstprogramme**
   - `comment_formatter.php` - Kommentarformatierung
   - `error_logger.php` - Fehlerprotokollierung

### Templates (`templates/`)
- `header.php` - Header-Template
- `footer.php` - Footer-Template

### Konfiguration
- `config.example.php` - Beispielkonfigurationsdatei