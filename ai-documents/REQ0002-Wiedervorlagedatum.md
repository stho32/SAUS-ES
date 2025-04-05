# REQ0002 - Wiedervorlagedatum

## Einleitung

Diese Anforderung beschreibt die Implementierung einer neuen Funktion, die es ermöglicht, Tickets mit einem Wiedervorlagedatum zu versehen. Dies soll sicherstellen, dass wichtige Angelegenheiten nicht vergessen werden.

## Anforderungen

### Datenbank

* In der MySQL-Datenbank sollen Tickets eine neue Spalte "Wiedervorlagedatum" erhalten. Diese Spalte ist vom Typ DATE und kann NULL sein.
* Für bestehende Tickets bleibt der Wert zunächst NULL.
* Es soll auch ein Flag geben, dass ich beim Bearbeiten des Tickets setzen kann "nicht verfolgen". 

### Benutzeroberfläche

* In der Ticket-Bearbeitungsansicht soll ein Datumswähler hinzugefügt werden, mit dem ein Wiedervorlagedatum gesetzt werden kann.
* Der Datumswähler soll in der bestehenden UI harmonisch integriert werden.
* Es soll einen Button/Link geben, um das Wiedervorlagedatum zu löschen.

### Gesamtübersicht "Dran bleiben"

* Es soll eine neue Gesamtübersicht "Dran bleiben" erstellt werden, die folgende Eigenschaften hat:
  * Grundsätzlich ist die Ansicht ähnlich wie die bestehende Gesamtübersicht aufgebaut
  * Es werden nur Tickets angezeigt, die entweder:
    * Kein Wiedervorlagedatum haben ODER
    * Ein Wiedervorlagedatum haben, das heute ist oder bereits abgelaufen ist
  * Tickets ohne Wiedervorlagedatum, die heute geändert wurden, werden NICHT angezeigt
  * Die Sortierung und Einfärbung erfolgt nach folgenden Kriterien:
    * Tickets mit abgelaufenem Wiedervorlagedatum sind ganz oben und mit einem Symbol markiert
    * Tickets mit heutigem Wiedervorlagedatum sind ebenfalls oben und mit einem Symbol markiert
    * Tickets ohne Wiedervorlagedatum werden nach letzter Aktivität sortiert, wobei lange nicht bearbeitete Tickets weiter oben erscheinen
  * Der Standard-Filter für "Dran bleiben" soll identisch zum Standard-Filter in der normalen Gesamtübersicht sein (standardmäßig wird nur "In Bearbeitung" ausgewählt)
  * Tickets mit dem Flag "nicht verfolgen" werden in "Dran bleiben" nicht angezeigt

ACHTUNG:
Man kann jetzt von 2 Orten aus Tickets in Detail öffnen: Die Übersichtsseite und "dran-bleiben". Die Bearbeiten-Seite wiederum enthält ein "Zurück". Der Zurück-Button sollte immer zu der Ansicht zurück führen, über die das Ticket geöffnet wurde.
Das gilt auch, wenn von der Detail-Seite z.B. ticket-edit geöffnet wurde. Die Information soll erhalten bleiben.

## Ziel der Funktion

Die neue Funktion ermöglicht es, Tickets mit einem Wiedervorlagedatum zu versehen, um sicherzustellen, dass wichtige Angelegenheiten nicht vergessen werden. Die "Dran bleiben"-Übersicht hilft dabei, den Überblick über anstehende Aufgaben zu behalten und priorisiert automatisch Tickets, die Aufmerksamkeit benötigen.

## Hinweise

* Bitte zerstöre oder verändere keine anderen bestehenden Funktionen!
* Die neue Funktion soll sich nahtlos in das bestehende System integrieren und die Benutzerfreundlichkeit verbessern.
* Falls für die Sortierung und Einfärbung zusätzliche Datenbankabfragen notwendig sind, achte auf Performanz-Optimierung bei größeren Ticketmengen.