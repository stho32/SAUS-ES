# R00021: Bug-Behebung — Hartkodierte Ticket-IDs in Dusk-Tests

## Typ
Bug-Behebung

## Fehlerbeschreibung
Alle 8 Dusk-Testdateien verwendeten hartkodierte Ticket-IDs (1-13), die in der aktuellen Datenbank nicht existierten (IDs starten bei 109). Dadurch schlugen 47+ von 108 Dusk-Tests fehl mit "element not found" oder "assertion failed"-Fehlern.

## Ursachenanalyse
### Root Cause
Die Tests wurden ursprünglich für eine Seeder-basierte Testdatenbank geschrieben, bei der Tickets mit IDs 1-13 existierten. Durch Neuaufsetzen der Datenbank oder Änderungen am Seeder stimmten die IDs nicht mehr überein.

### Betroffene Komponenten
- `Source/tests/DuskTestCase.php` (neu: Helper-Methoden)
- `Source/tests/Browser/TicketCommentTest.php` (25 Tests)
- `Source/tests/Browser/TicketInlineEditTest.php` (30 Tests)
- `Source/tests/Browser/CommentFunctionsTest.php` (6 Tests)
- `Source/tests/Browser/TicketAttachmentTest.php` (7 Tests)
- `Source/tests/Browser/TicketDetailPageTest.php` (7 Tests)
- `Source/tests/Browser/TicketContactPersonTest.php` (7 Tests)
- `Source/tests/Browser/TicketBugfixVerificationTest.php` (2 Tests)
- `Source/tests/Browser/TicketFlowTest.php` (3 Tests)

### Warum nicht durch Tests gefunden
Das Problem WAR die Testsuite selbst — die Tests waren nicht datenunabhängig. Kein Meta-Test prüfte, ob die vorausgesetzten Testdaten existierten.

## Durchgeführte Änderungen
- **DuskTestCase.php**: 4 Helper-Methoden hinzugefügt:
  - `createTestTicket()` — erstellt Ticket mit auto-generierter Nummer
  - `addTestComment()` — fügt Kommentar zu Ticket hinzu
  - `addTestAttachment()` — fügt Anhang-Record hinzu
  - `addTestContactPerson()` — erstellt und verknüpft Ansprechpartner
- **8 Testdateien**: Alle ~90 hartkodierten `visit('/saus/tickets/X')` durch dynamische `createTestTicket()` + `$ticket->id` ersetzt
- Zusätzliche Fixes: hartkodierte Status-IDs, Session-Username-Persistenz, System-Kommentar-Sichtbarkeit nach DOM-Updates

## Test-Absicherung
- 108/108 Dusk-Tests bestehen (vorher 47+ fehlgeschlagen)
- 169/169 Pest-Tests bestehen (unverändert)
- Jeder Test erstellt isolierte Testdaten — keine Abhängigkeiten zwischen Tests

## Erkenntnisse für das Projekt
- **Datenunabhängige Tests**: Tests dürfen nie von spezifischen DB-IDs abhängen. Jeder Test muss seine eigenen Daten erstellen.
- **Shared Helpers**: `DuskTestCase`-Basis mit `createTestTicket()` etc. als zentrales Pattern für alle zukünftigen Dusk-Tests.
- **Session-State in Dusk**: Bei Username-Wechsel innerhalb einer Test-Suite muss erst ausgeloggt werden, da die Browser-Session zwischen Tests persistiert.
- **DOM-Updates vs. System-Kommentare**: Nach Inline-DOM-Updates (ohne page reload) müssen Tests, die System-Kommentare prüfen, die Seite explizit neu laden.

## Status
Abgeschlossen — 2026-04-04
