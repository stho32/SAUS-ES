# R00019: Bug-Behebung — Kommentar-Vote-Toggle funktioniert nicht

## Typ
Bug-Behebung

## Fehlerbeschreibung
Beim Klicken auf einen Vote-Button (Thumb-Up/Down) bei einem Kommentar konnte der Vote nicht durch erneutes Klicken auf denselben Button entfernt werden. Man konnte nur zwischen Up und Down wechseln, aber seinen Vote nicht zurücknehmen. Zusätzlich fehlte das visuelle Feedback (farbige Hervorhebung) bei aktiven Kommentar-Votes, das bei Ticket-Votes bereits vorhanden war.

## Ursachenanalyse
### Root Cause
Die JavaScript-Funktion `voteComment()` hatte keine Toggle-Logik — sie sendete immer den geklickten Wert ('up'/'down') an die API, ohne den aktuellen Vote-Status des Users zu prüfen. Im Gegensatz dazu hatte `voteTicket()` diese Logik bereits korrekt mit einem `currentTicketVote`-State implementiert.

### Betroffene Komponenten
- `Source/resources/views/tickets/show.blade.php` — JS-Funktion `voteComment()` und Kommentar-Vote-Buttons
- `Source/app/Http/Controllers/TicketController.php` — fehlende Übergabe der User-Comment-Votes an die View

### Warum nicht durch Tests gefunden
Der Dusk-Test T76 (`test_t76_comment_vote_toggle`) hat `voteComment(id, 'none')` direkt aufgerufen, statt den gleichen Button zweimal zu klicken. Dadurch wurde die Backend-API getestet (die `'none'` korrekt verarbeitet), nicht das tatsächliche UI-Toggle-Verhalten. Die Feature-Tests (VoteTest.php) testen die API korrekt, können aber Frontend-Toggle-Logik prinzipbedingt nicht abdecken.

## Durchgeführte Änderungen
- `Source/app/Http/Controllers/TicketController.php`: User-Comment-Votes pro Kommentar laden und als `$userCommentVotes` an die View übergeben
- `Source/resources/views/tickets/show.blade.php`:
  - `currentCommentVotes`-Map aus Server-Daten initialisiert
  - Toggle-Logik in `voteComment()`: bei erneutem Klick auf denselben Button wird `'none'` gesendet
  - Visuelles Feedback: aktive Votes werden farbig hervorgehoben (grün für Up, rot für Down), analog zu Ticket-Votes
  - Buttons mit `id="comment-voting-{id}"` und Klasse `comment-vote-btn` für gezieltes Styling
- `Source/tests/Browser/TicketCommentTest.php`: Test T76 korrigiert — testet jetzt echtes Toggle (gleichen Button zweimal klicken), dynamische Ticket-ID, Cleanup vorheriger Testvotes

## Test-Absicherung
- `test_t76_comment_vote_toggle` (Dusk): Klickt den Upvote-Button zweimal und prüft, dass der Zähler zum Ausgangswert zurückkehrt

## Erkenntnisse für das Projekt
**Muster**: Dusk-Tests die JS-Funktionen direkt mit berechneten Werten aufrufen (z.B. `'none'`), testen die API-Schicht, nicht das tatsächliche UI-Verhalten. Toggle-Logik im Frontend kann nur korrekt getestet werden, wenn der Test die gleiche Aktion ausführt wie der User (gleichen Button zweimal klicken). Projektweite Suche ergab keine weiteren betroffenen Stellen.

## Status
Abgeschlossen — 2026-04-04
