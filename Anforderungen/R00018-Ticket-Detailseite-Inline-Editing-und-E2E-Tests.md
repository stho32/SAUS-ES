# R00018: Ticket-Detailseite — Inline-Editing und umfassende E2E-Tests

## Beschreibung

Die Ticket-Detailseite (`/saus/tickets/{id}`) wird überarbeitet: Der separate Bearbeiten-Modus (eigene Seite über den blauen "Bearbeiten"-Button) wird aufgelöst und alle Bearbeitungsfunktionen werden direkt inline auf der Detailseite verfügbar gemacht — wie es bei den Modals für Zuständigkeit, Status und Wiedervorlagedatum bereits umgesetzt ist. Zusätzlich werden umfassende E2E-Tests (Dusk) für **jedes einzelne Feature** der Seite erstellt, die auch die aktuell bestehenden Bugs aufdecken und nach deren Behebung grün laufen.

## Motivation

- Der blaue "Bearbeiten"-Button führt zu einer komplett separaten Seite (`/saus/tickets/{id}/edit`), was den Workflow unterbricht und irritiert
- Die Detailseite hat bereits Inline-Editing via Modals (Zuständigkeit, Status, Wiedervorlagedatum) — die restlichen Felder sollten diesem Muster folgen
- Aktuell gibt es **keine E2E-Tests** für die meisten Interaktionen auf der Detailseite (Uploads, Ansprechpartner, Kommentar-Sichtbarkeit, Kommentar-Bearbeitung, etc.)
- Bekannte Bugs (z.B. Feldname-Mismatch bei Ansprechpartnern) werden erst durch echte E2E-Tests sichtbar

## Bekannte Bugs (durch Analyse identifiziert)

### Bug 1: Ansprechpartner — Feldname-Mismatch (KRITISCH)
- **Datei**: `app/Http/Controllers/Api/ContactPersonApiController.php:18`
- **Problem**: Controller validiert `contactPersonId`, JavaScript sendet `contact_person_id`
- **Auswirkung**: Ansprechpartner können nicht verknüpft werden — Validierungsfehler

### Bug 2: Username-Middleware greift nicht bei API-Aufrufen
- **Datei**: `app/Http/Middleware/EnsureUsername.php:14`
- **Problem**: `api/*` ist von der Username-Prüfung ausgenommen
- **Auswirkung**: API-Aufrufe können ohne gesetzten Benutzernamen erfolgen, Kommentare als "Unbekannt" erstellt

### Bug 3: Kommentar-Erstellung ohne Username-Validierung
- **Datei**: `app/Http/Controllers/Api/CommentApiController.php:25`
- **Problem**: Fallback auf `'Unbekannt'` statt Fehler bei fehlendem Username
- **Auswirkung**: Kommentare ohne Absender-Zuordnung möglich

### Bug 4: Geschlossene Tickets können bearbeitet werden
- **Datei**: `app/Http/Controllers/Api/TicketApiController.php`
- **Problem**: Nur `toggleVisibility()` prüft auf geschlossene/archivierte Tickets, alle anderen Endpunkte nicht
- **Auswirkung**: Status, Zuständigkeit, Wiedervorlagedatum können bei geschlossenen Tickets geändert werden

## Akzeptanzkriterien

### Inline-Editing (Edit-Seite auflösen)

- [ ] Der blaue "Bearbeiten"-Button und die Route `/tickets/{id}/edit` werden entfernt
- [ ] **Titel**: Klick auf den Titel öffnet ein Inline-Eingabefeld (oder Modal) zur Bearbeitung
- [ ] **Beschreibung**: Klick auf die Beschreibung öffnet ein Textarea-Eingabefeld (oder Modal)
- [ ] **Betroffene Nachbarn**: Klick auf die Karte öffnet ein Eingabefeld (analog zu Zuständigkeit/Status)
- [ ] **Nicht verfolgen**: Klick auf die Karte toggelt den Wert (analog zu bestehenden Karten)
- [ ] **Auf Website anzeigen**: Toggle direkt auf der Detailseite, öffnet bei Aktivierung das Public-Comment-Feld
- [ ] **Öffentlicher Kommentar**: Textarea erscheint nur wenn "Auf Website anzeigen" aktiv ist
- [ ] **KI-Zusammenfassung / KI-Zwischenstand**: Bleiben read-only (keine Änderung)
- [ ] Alle Inline-Edits speichern via API ohne Seitenreload
- [ ] Visuelles Feedback bei erfolgreichem Speichern (kurze Bestätigung)
- [ ] Abbrechen-Option bei jedem Inline-Edit (Escape-Taste oder Abbrechen-Button)

### E2E-Tests: Header & Navigation

- [ ] **T01**: Ticket-Detailseite lädt korrekt mit allen Sektionen
- [ ] **T02**: Zurück-Button führt zur Ticket-Liste
- [ ] **T03**: E-Mail-Ansicht-Button führt zur E-Mail-Seite

### E2E-Tests: Ticket-Voting

- [ ] **T04**: Up-Vote-Button ist klickbar und erhöht den Zähler
- [ ] **T05**: Down-Vote-Button ist klickbar und erhöht den Zähler
- [ ] **T06**: Erneutes Klicken auf denselben Vote-Button entfernt den Vote (Toggle)
- [ ] **T07**: Wechsel von Up- zu Down-Vote aktualisiert beide Zähler

### E2E-Tests: Zuständigkeit (Inline-Modal)

- [ ] **T08**: Klick auf Zuständigkeits-Karte öffnet das Modal
- [ ] **T09**: Zuständigkeit ändern und speichern aktualisiert die Anzeige
- [ ] **T10**: Mehrere Zuständige (kommagetrennt) werden korrekt gespeichert
- [ ] **T11**: Zuständigkeit leeren setzt auf "Nicht zugewiesen"
- [ ] **T12**: Abbrechen im Modal verwirft Änderungen

### E2E-Tests: Status (Inline-Modal)

- [ ] **T13**: Klick auf Status-Karte öffnet das Modal
- [ ] **T14**: Status ändern erstellt einen System-Kommentar
- [ ] **T15**: Statusfarbe aktualisiert sich nach Änderung
- [ ] **T16**: Abbrechen im Modal verwirft Änderungen

### E2E-Tests: Wiedervorlagedatum (Inline-Modal)

- [ ] **T17**: Klick auf Wiedervorlage-Karte öffnet das Modal
- [ ] **T18**: Datum setzen und speichern aktualisiert die Anzeige
- [ ] **T19**: Datum ändern erstellt einen System-Kommentar
- [ ] **T20**: Abbrechen im Modal verwirft Änderungen

### E2E-Tests: Titel bearbeiten (NEU — Inline-Edit)

- [ ] **T21**: Klick auf Titel aktiviert Bearbeitungsmodus
- [ ] **T22**: Titel ändern und speichern aktualisiert die Anzeige ohne Reload
- [ ] **T23**: Leerer Titel wird abgelehnt (Validierung)
- [ ] **T24**: Escape/Abbrechen verwirft Änderungen

### E2E-Tests: Beschreibung bearbeiten (NEU — Inline-Edit)

- [ ] **T25**: Klick auf Beschreibung aktiviert Bearbeitungsmodus
- [ ] **T26**: Beschreibung ändern und speichern aktualisiert die Anzeige
- [ ] **T27**: Leere Beschreibung wird abgelehnt (Validierung)
- [ ] **T28**: Escape/Abbrechen verwirft Änderungen

### E2E-Tests: Betroffene Nachbarn (NEU — Inline-Edit)

- [ ] **T29**: Klick auf Betroffene-Nachbarn-Karte öffnet Eingabefeld/Modal
- [ ] **T30**: Zahl eingeben und speichern aktualisiert die Anzeige
- [ ] **T31**: Feld leeren setzt auf "Unbekannt"

### E2E-Tests: Website-Sichtbarkeit (NEU — Inline-Toggle)

- [ ] **T32**: Toggle "Auf Website anzeigen" aktiviert die Website-Anzeige
- [ ] **T33**: Bei Aktivierung erscheint das Public-Comment-Feld
- [ ] **T34**: Public Comment eingeben und speichern
- [ ] **T35**: Toggle deaktivieren blendet den Public-Comment-Bereich aus

### E2E-Tests: Nicht-Verfolgen (NEU — Inline-Toggle)

- [ ] **T36**: Toggle "Nicht verfolgen" ändert den Wert auf der Karte
- [ ] **T37**: Wert wird via API gespeichert ohne Reload

### E2E-Tests: Ansprechpartner

- [ ] **T38**: Ansprechpartner-Sektion wird angezeigt
- [ ] **T39**: "Hinzufügen"-Button öffnet das Modal mit Dropdown
- [ ] **T40**: Ansprechpartner auswählen und hinzufügen erstellt Verknüpfung und System-Kommentar
- [ ] **T41**: Hinzugefügter Ansprechpartner erscheint in der Liste mit Name, E-Mail, Telefon
- [ ] **T42**: Löschen-Button entfernt den Ansprechpartner und erstellt System-Kommentar
- [ ] **T43**: Bereits verknüpfte Ansprechpartner erscheinen nicht im Dropdown
- [ ] **T44**: Link "Ansprechpartner verwalten" führt zur Verwaltungsseite

### E2E-Tests: Datei-Uploads (Anhänge)

- [ ] **T45**: Upload-Formular ist sichtbar mit Datei-Input und Upload-Button
- [ ] **T46**: Bild hochladen (JPEG/PNG) zeigt Thumbnail in der Anhang-Liste
- [ ] **T47**: PDF hochladen zeigt Datei-Icon in der Anhang-Liste
- [ ] **T48**: Hochgeladene Datei herunterladen/anzeigen funktioniert (GET-Request)
- [ ] **T49**: Datei löschen mit Bestätigungsdialog entfernt den Anhang
- [ ] **T50**: Ungültiger Dateityp wird abgelehnt (z.B. .exe)
- [ ] **T51**: Zu große Datei wird abgelehnt (>10MB)
- [ ] **T52**: Upload-Zähler im Sektions-Header aktualisiert sich

### E2E-Tests: Kommentare — Anzeige

- [ ] **T53**: Kommentar-Sektion zeigt alle sichtbaren Kommentare
- [ ] **T54**: System-Kommentare haben grauen Hintergrund und keinen Vote-Bereich
- [ ] **T55**: Kommentar zeigt Benutzername und Zeitstempel
- [ ] **T56**: Bearbeitete Kommentare zeigen "(bearbeitet am ...)"
- [ ] **T57**: Kommentar-Formatierung: **fett**, *kursiv*, URLs als Links, Checkboxen

### E2E-Tests: Kommentare — Erstellen

- [ ] **T58**: Neuer-Kommentar-Formular ist sichtbar
- [ ] **T59**: Kommentar eingeben und speichern fügt neuen Kommentar hinzu
- [ ] **T60**: Leerer Kommentar wird abgelehnt (Validierung)
- [ ] **T61**: Formatierungshilfe wird angezeigt

### E2E-Tests: Kommentare — Bearbeiten

- [ ] **T62**: Eigene Kommentare haben einen Bearbeiten-Button (Stift-Icon)
- [ ] **T63**: Klick auf Bearbeiten wandelt den Kommentar in ein Textarea um
- [ ] **T64**: Bearbeiteten Kommentar speichern aktualisiert den Inhalt
- [ ] **T65**: Nach Bearbeitung erscheint "(bearbeitet am ...)"
- [ ] **T66**: Abbrechen bei Bearbeitung verwirft Änderungen
- [ ] **T67**: Fremde Kommentare haben keinen Bearbeiten-Button

### E2E-Tests: Kommentare — Sichtbarkeit

- [ ] **T68**: Unsichtbare Kommentare sind standardmäßig ausgeblendet
- [ ] **T69**: Checkbox "Alle anzeigen" blendet unsichtbare Kommentare ein
- [ ] **T70**: Unsichtbare Kommentare zeigen "(Ausgeblendet von X am ...)"
- [ ] **T71**: Augen-Icon klicken schaltet Kommentar unsichtbar
- [ ] **T72**: Unsichtbaren Kommentar wieder sichtbar schalten
- [ ] **T73**: Bei geschlossenen/archivierten Tickets ist Sichtbarkeits-Toggle deaktiviert

### E2E-Tests: Kommentare — Voting

- [ ] **T74**: Up-Vote-Button bei Kommentar erhöht den Zähler
- [ ] **T75**: Down-Vote-Button bei Kommentar erhöht den Zähler
- [ ] **T76**: Erneuter Klick entfernt den Vote (Toggle)
- [ ] **T77**: System-Kommentare haben keine Vote-Buttons

### E2E-Tests: Bugfixes verifizieren

- [ ] **T78**: Ansprechpartner hinzufügen funktioniert (Bug 1: Feldname-Fix verifizieren)
- [ ] **T79**: Kommentare werden mit korrektem Benutzernamen erstellt, nicht "Unbekannt" (Bug 2+3)
- [ ] **T80**: Bei geschlossenen Tickets sind Status/Zuständigkeit/Wiedervorlage-Modals deaktiviert oder zeigen Hinweis (Bug 4)

## Status

- [ ] Offen

## Technische Details

### Phase 1: Bugfixes

| Bug | Datei | Änderung |
|-----|-------|----------|
| Feldname-Mismatch | `app/Http/Controllers/Api/ContactPersonApiController.php` | `contactPersonId` → `contact_person_id` |
| Username-Fallback | `app/Http/Controllers/Api/CommentApiController.php` | Fallback `'Unbekannt'` durch 401-Fehler ersetzen |
| Geschlossene Tickets | `app/Http/Controllers/Api/TicketApiController.php` | Prüfung auf `is_closed`/`is_archived` in `updateStatus()`, `updateAssignee()`, `updateFollowUp()` |

### Phase 2: Inline-Editing

| Datei | Änderung |
|-------|----------|
| `resources/views/tickets/show.blade.php` | Titel, Beschreibung, Betroffene Nachbarn, Website-Toggle, Nicht-Verfolgen als Inline-Edits ergänzen |
| `resources/views/tickets/edit.blade.php` | **Entfernen** — nicht mehr benötigt |
| `app/Http/Controllers/TicketController.php` | `edit()`-Methode entfernen |
| `routes/web.php` | Route `tickets.edit` entfernen |
| `resources/views/tickets/show.blade.php` | "Bearbeiten"-Button im Header entfernen |

### Neue Inline-Edit-Elemente auf der Show-Seite

| Element | Interaktion | API-Endpunkt |
|---------|-------------|--------------|
| Titel | Klick → Inline-Input | `PUT /api/tickets/{id}` (title) |
| Beschreibung | Klick → Inline-Textarea | `PUT /api/tickets/{id}` (description) |
| Betroffene Nachbarn | Klick → Modal mit Number-Input | `PUT /api/tickets/{id}` (affectedNeighbors) |
| Nicht verfolgen | Klick → Toggle | `PUT /api/tickets/{id}` (doNotTrack) |
| Auf Website anzeigen | Toggle + Conditional Textarea | `PUT /api/tickets/{id}` (showOnWebsite, publicComment) |

### Phase 3: E2E-Tests

| Testdatei | Prüft | Anzahl Tests |
|-----------|-------|--------------|
| `tests/Browser/TicketDetailPageTest.php` | Header, Navigation, Ticket-Voting (T01–T07) | 7 |
| `tests/Browser/TicketInlineEditTest.php` | Zuständigkeit, Status, Wiedervorlage, Titel, Beschreibung, Nachbarn, Website, Nicht-Verfolgen (T08–T37) | 30 |
| `tests/Browser/TicketContactPersonTest.php` | Ansprechpartner CRUD (T38–T44) | 7 |
| `tests/Browser/TicketAttachmentTest.php` | Datei-Upload, Download, Löschen, Validierung (T45–T52) | 8 |
| `tests/Browser/TicketCommentTest.php` | Kommentar-Anzeige, Erstellen, Bearbeiten, Sichtbarkeit, Voting (T53–T77) | 25 |
| `tests/Browser/TicketBugfixVerificationTest.php` | Verifizierung der Bugfixes (T78–T80) | 3 |

### Zielverzeichnisse

| Verzeichnis | Zweck |
|------------|-------|
| `app/Http/Controllers/Api/` | Bugfixes in API-Controllern |
| `resources/views/tickets/` | Inline-Editing auf Show-Seite, Edit-Seite entfernen |
| `tests/Browser/` | Neue E2E-Testdateien |

### Zu ändernde Dateien

| Datei | Änderung |
|-------|----------|
| `app/Http/Controllers/Api/ContactPersonApiController.php` | Feldname-Fix |
| `app/Http/Controllers/Api/CommentApiController.php` | Username-Validierung |
| `app/Http/Controllers/Api/TicketApiController.php` | Closed-Ticket-Prüfung |
| `resources/views/tickets/show.blade.php` | Inline-Editing für alle Felder |
| `routes/web.php` | Edit-Route entfernen |
| `app/Http/Controllers/TicketController.php` | `edit()`-Methode entfernen |

### Zu löschende Dateien

| Datei | Grund |
|-------|-------|
| `resources/views/tickets/edit.blade.php` | Durch Inline-Editing auf Show-Seite ersetzt |

### Neue Dateien

| Datei | Beschreibung |
|-------|--------------|
| `tests/Browser/TicketDetailPageTest.php` | E2E: Header, Navigation, Voting |
| `tests/Browser/TicketInlineEditTest.php` | E2E: Alle Inline-Edit-Funktionen |
| `tests/Browser/TicketContactPersonTest.php` | E2E: Ansprechpartner CRUD |
| `tests/Browser/TicketAttachmentTest.php` | E2E: Datei-Uploads |
| `tests/Browser/TicketCommentTest.php` | E2E: Kommentare komplett |
| `tests/Browser/TicketBugfixVerificationTest.php` | E2E: Bugfix-Verifikation |

## Abhängigkeiten

- Abhängig von: R00011 (Framework-Migration — Laravel-Basis muss stehen)
- Blockiert: —

## Notizen

- Die bestehenden Dusk-Tests in `TicketFlowTest.php` und `CommentFunctionsTest.php` können nach Implementierung überarbeitet/konsolidiert werden
- Die E2E-Tests sollen mit dem bestehenden `TestDataSeeder` arbeiten, der 20 Tickets mit Kommentaren, Votes und Anhängen erstellt
- Die Inline-Edits folgen dem bestehenden Modal-Pattern (fetch + JSON + CSRF-Token), das bereits für Zuständigkeit/Status/Wiedervorlage funktioniert
- **Wichtig**: E2E-Tests sollten zuerst geschrieben werden (vor den Fixes), damit sie die bekannten Bugs aufdecken und nach der Behebung grün werden (Test-Driven-Bugfixing)
- Die `loginAs()`-Methode aus den bestehenden Dusk-Tests kann wiederverwendet werden
- Gesamtumfang: ~80 E2E-Testszenarien + Inline-Editing-Umbau + 3 Bugfixes
