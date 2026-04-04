# R00020: Bug-Behebung — "Alle anzeigen"-Filter verliert Wirkung nach Visibility-Toggle

## Typ
Bug-Behebung

## Fehlerbeschreibung
Auf der Ticket-Detailseite konnte man Kommentare per Auge-Icon unsichtbar schalten und per "Alle anzeigen"-Checkbox wieder einblenden. Wenn man jedoch bei aktivem "Alle anzeigen"-Filter einen unsichtbaren Kommentar per Auge-Icon wieder sichtbar schaltete, verschwanden alle anderen unsichtbaren Kommentare — obwohl der Filter noch aktiv war. Mehrere Kommentare nacheinander wiederherstellen war dadurch nicht möglich.

## Ursachenanalyse
### Root Cause
`toggleCommentVisibility()` in `show.blade.php` rief nach erfolgreichem API-Call `location.reload()` auf. Der vollständige Seiten-Reload setzte die "Alle anzeigen"-Checkbox auf ihren HTML-Default (unchecked) zurück, wodurch alle unsichtbaren Kommentare wieder per CSS ausgeblendet wurden.

### Betroffene Komponenten
- `Source/resources/views/tickets/show.blade.php` — JavaScript-Funktion `toggleCommentVisibility()`

### Warum nicht durch Tests gefunden
Die existierenden Dusk-Tests (T71, T72) testeten den Visibility-Toggle isoliert — ohne vorher den "Alle anzeigen"-Filter zu aktivieren. Die Kombination von aktivem Filter + Visibility-Toggle war nicht abgedeckt. Test T72 prüfte nur die Anzahl der `.comment-hidden`-Elemente, nicht deren tatsächliche Sichtbarkeit im Kontext des Filters.

## Durchgeführte Änderungen
- `Source/resources/views/tickets/show.blade.php` — `toggleCommentVisibility()` aktualisiert jetzt den DOM inline (CSS-Klasse, data-Attribut, Icon, Info-Text) statt `location.reload()` aufzurufen. Der "Alle anzeigen"-Filter-Zustand wird beim Ausblenden berücksichtigt.
- `Source/tests/Browser/TicketCommentTest.php` — Neuer Test T72b hinzugefügt.

## Test-Absicherung
- `test_t72b_show_all_filter_persists_after_visibility_toggle` (16 Assertions): Erstellt zwei Kommentare, blendet sie aus, aktiviert "Alle anzeigen", blendet einen wieder ein und prüft dass der Filter aktiv bleibt und die restlichen unsichtbaren Kommentare weiterhin angezeigt werden.

## Erkenntnisse für das Projekt
`location.reload()` nach AJAX-Operationen setzt jeglichen clientseitigen UI-State zurück (Checkboxen, Filter, Scroll-Position, geöffnete Modals). Das gleiche Muster existiert an 15 weiteren Stellen in der Codebase. Für Aktionen die häufig mit aktivem Filter-Zustand kombiniert werden, sollte DOM-Update statt Reload bevorzugt werden.

## Status
Abgeschlossen — 2026-04-04
