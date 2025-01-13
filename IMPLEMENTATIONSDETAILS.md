# Übersicht der benötigten PHP-Dateien und Datenbanktabellen

## PHP-Dateien (Beispiel-Struktur)

1. **index.php**  
   - Zeigt eine Übersicht aller Tickets (mit Ticketnummer, Titel, Status etc.).  
   - Verlinkt zur Erstellung neuer Tickets (z. B. `create_ticket.php`) und zur Detailansicht eines Tickets (z. B. `ticket_view.php`).  

2. **create_ticket.php**  
   - Formularseite zum Anlegen eines neuen Tickets (Titel, Beschreibung, KI-Zusammenfassung etc.).  
   - Speichert das neue Ticket in der Datenbank.

3. **ticket_view.php**  
   - Zeigt Details eines Tickets inkl. KI-Zusammenfassung, Zwischenzusammenfassung (KI), Kommentare, Abstimmung.  
   - Bietet Möglichkeiten zum:
     - Erstellen neuer Kommentare  
     - Abgeben von Bewertungen (Daumen hoch/runter)  
     - Einsehen/Erstellen von Diskussionspartner-Links  

4. **completed_tickets.php**  
   - Listet alle abgeschlossenen Tickets in absteigender Reihenfolge nach Datum.  
   - Dient nur zur Anzeige, kein weiteres Bearbeiten dieser Tickets.

5. **(Optional) partner_link.php**  
   - Wird aufgerufen, wenn ein Diskussionspartner-Link generiert oder abgerufen wird.  
   - Stellt sicher, dass das Kürzel des Diskussionspartners im Link hinterlegt ist und nur eingeschränkte Rechte (Kommentare abgeben, Thumbs bewerten) vorhanden sind.

> Je nach Projektstruktur können einige dieser Seiten zusammengelegt werden oder zusätzliche Verwaltungsseiten für Moderatoren entstehen. Das obige Beispiel dient als einfache Aufteilung, um die Hauptfunktionen abzudecken.

---

## Mögliche MySQL-Datenbanktabellen

### 1. Tabelle: `ticket_status`
Enthält die möglichen Status für Tickets.

| Feld          | Typ          | Beschreibung                                                |
|---------------|--------------|-------------------------------------------------------------|
| `id`          | INT (PK, AI) | Primärschlüssel, automatisch increment                     |
| `name`        | VARCHAR(50)  | Kurzname des Status (z.B. 'open', 'closed')                |
| `description` | TEXT         | Ausführliche Beschreibung des Status                        |
| `sort_order`  | INT         | Reihenfolge für die Anzeige/Verarbeitung                    |
| `is_active`   | BOOLEAN     | Gibt an, ob dieser Status aktiv/verfügbar ist               |
| `created_at`  | DATETIME    | Erstellungsdatum des Status                                 |

### 2. Tabelle: `tickets`
Enthält die Grundinformationen zu jedem Ticket.

| Feld               | Typ          | Beschreibung                                                      |
|--------------------|--------------|-------------------------------------------------------------------|
| `id`               | INT (PK, AI) | Primärschlüssel, automatisch increment                           |
| `ticket_number`    | VARCHAR(...) | Eindeutige Ticketnummer oder -kennung                             |
| `title`            | VARCHAR(...) | Kurze Beschreibung oder Titel des Tickets                         |
| `ki_summary`       | TEXT         | KI-generierte Einführung                                          |
| `ki_interim`       | TEXT         | KI-generierte Zwischenzusammenfassung                             |
| `status_id`        | INT          | Verweis auf `ticket_status.id` (Fremdschlüssel)                   |
| `created_at`       | DATETIME     | Erstellungsdatum                                                  |
| `closed_at`        | DATETIME     | Datum, an dem das Ticket abgeschlossen wurde (kann NULL sein)     |

### 3. Tabelle: `comments`
Speichert die Kommentare zum jeweiligen Ticket.

| Feld             | Typ          | Beschreibung                                       |
|------------------|--------------|----------------------------------------------------|
| `id`             | INT (PK, AI) | Primärschlüssel                                    |
| `ticket_id`      | INT          | Verweis auf `tickets.id` (Fremdschlüssel)          |
| `username`       | VARCHAR(...) | Namenskürzel des Kommentators                      |
| `content`        | TEXT         | Inhalt des Kommentars                              |
| `created_at`     | DATETIME     | Zeitstempel der Kommentar-Erstellung               |

### 4. Tabelle: `votes`
Erfasst die Abstimmungen (Daumen hoch/runter) pro Ticket.

| Feld          | Typ          | Beschreibung                                         |
|---------------|--------------|------------------------------------------------------|
| `id`          | INT (PK, AI) | Primärschlüssel                                      |
| `ticket_id`   | INT          | Verweis auf `tickets.id` (Fremdschlüssel)            |
| `username`    | VARCHAR(...) | Namenskürzel des Abstimmenden                        |
| `value`       | ENUM(...)    | z. B. `'up'` oder `'down'`                           |
| `created_at`  | DATETIME     | Zeitpunkt der Stimmabgabe                            |

### 5. Tabelle: `partners` 
Ermöglicht das Anlegen von Diskussionspartnern, die eingeschränkte Rechte haben.  
*(Diese Tabelle ist optional, falls man Diskussionspartner **ticket-spezifisch** verwaltet. Ansonsten könnte man nur einen generischen Link pro Kürzel generieren.)*

| Feld             | Typ          | Beschreibung                                                          |
|------------------|--------------|-----------------------------------------------------------------------|
| `id`             | INT (PK, AI) | Primärschlüssel                                                       |
| `ticket_id`      | INT          | Verweis auf `tickets.id` (falls die Partnerschaft nur ein Ticket betrifft) |
| `partner_name`   | VARCHAR(...) | Namenskürzel oder Anzeigename                                        |
| `partner_link`   | VARCHAR(...) | Eindeutiger Link/Token, der das Kürzel fix hinterlegt                |
| `created_at`     | DATETIME     | Zeitpunkt der Erstellung                                              |

> **Hinweis**: Bei Bedarf kann man noch eine Benutzer- oder Moderatorentabelle einführen, um Rollen und Berechtigungen differenziert zu handhaben. In sehr einfachen Szenarien genügt es jedoch, Namenskürzel und eventuelle Rollen in denselben Tabellen oder in einem zusätzlichen Feld zu pflegen.

---

## Entscheidungs-Logik & Abschluss
- Ein Ticket kann vom Moderator erst dann auf „abgeschlossen“ gesetzt werden, wenn mindestens 4 Personen abgestimmt haben.  
- Die DB-Felder `status` in `tickets` (von `open` auf `closed`) und `closed_at` stellen den Abschluss in der Datenbank dar.  
- In der PHP-Anwendung wird beim Ändern des Status auf „closed“ geprüft, ob tatsächlich mindestens 4 gültige Abstimmungen vorhanden sind.

---

### Zusammenfassung
Mit diesen **5 PHP-Dateien** (bzw. 4 + optionaler `partner_link.php`) deckst du eine minimale Interaktionsbasis ab (Übersicht, Erstellung, Detailansicht mit Kommentaren und Votes, abgeschlossene Tickets, Diskussionspartner-Verwaltung). Die **5 Tabellen** (`ticket_status`, `tickets`, `comments`, `votes`, `partners`) bilden ein schlankes Grundgerüst für die Datenhaltung und erlauben, die geforderten Features (z. B. Zwischenzusammenfassung, Abstimmungs-Logik, Diskussionspartner) umzusetzen.