---
id: R00016
titel: "Kommentar-Formatierungs-Vorschau"
typ: Feature
status: Offen
erstellt: 2026-04-03
---

# R00016: Kommentar-Formatierungs-Vorschau

## Zusammenfassung
Die alte Anwendung hat einen API-Endpunkt `format_comment.php` fuer die Vorschau der Kommentar-Formatierung. Dieser fehlt in der Laravel-Portierung.

## Anforderungen
- API-Endpunkt `POST /api/format-comment` der den CommentFormatter anwendet und formatierten HTML-Text zurueckgibt
- Optional: Live-Vorschau im Kommentar-Formular waehrend der Eingabe

## Akzeptanzkriterien
- [ ] API-Endpunkt fuer Formatierungs-Vorschau
- [ ] Feature-Test fuer den Endpunkt
