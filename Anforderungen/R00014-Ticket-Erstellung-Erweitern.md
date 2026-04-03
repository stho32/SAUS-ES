---
id: R00014
titel: "Ticket-Erstellung um fehlende Felder erweitern"
typ: Feature
status: Offen
erstellt: 2026-04-03
---

# R00014: Ticket-Erstellung um fehlende Felder erweitern

## Zusammenfassung
Die alte Anwendung bietet bei der Ticket-Erstellung mehr Felder als die aktuelle Laravel-Portierung.

## Ist-Zustand
Das Create-Formular hat: Titel, Beschreibung, Status, Zustaendiger.

## Fehlende Felder
- show_on_website Toggle (Auf Webseite anzeigen)
- public_comment Textarea (Oeffentlicher Kommentar)
- affected_neighbors (Betroffene Nachbarn)

## Akzeptanzkriterien
- [ ] show_on_website Checkbox im Create-Formular
- [ ] public_comment Textarea im Create-Formular
- [ ] affected_neighbors Zahlenfeld im Create-Formular
- [ ] Feature-Test fuer erweiterte Ticket-Erstellung
