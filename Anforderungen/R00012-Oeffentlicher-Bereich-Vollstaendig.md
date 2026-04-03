---
id: R00012
titel: "Oeffentlicher Bereich vollstaendig abbilden"
typ: Feature
status: Offen
erstellt: 2026-04-03
---

# R00012: Oeffentlicher Bereich vollstaendig abbilden

## Zusammenfassung
Der oeffentliche Bereich (public_information_saus/) der alten Anwendung hat eine reichhaltigere Struktur als die aktuelle Laravel-Portierung. Fehlende Elemente muessen ergaenzt werden.

## Ist-Zustand
Die oeffentliche Ticket-Seite (`/public`) zeigt nur eine einfache Tabelle mit Tickets. Die alte Anwendung hatte:
- Intro-Text mit Informationen ueber aktuelle Vorgaenge
- Einladung zur Teilnahme an der Sitzung
- Sitzungstermin (erster Montag im Monat, 19:30 Uhr)
- Kontakt-Box mit Messenger- und E-Mail-Informationen
- Hinweis auf Ticket-Nummern bei Anfragen
- Inhaltsverzeichnis (Table of Contents) mit Ankern zu einzelnen Tickets
- Card-basiertes Layout pro Ticket (statt Tabelle)
- Hover-Effekte auf Karten

## Anforderungen
- Intro-Bereich mit konfigurierbarem Text (ggf. als Blade-Partial oder Config)
- Kontakt-Box mit Messenger/E-Mail (konfigurierbar)
- Sitzungstermin-Anzeige
- Inhaltsverzeichnis mit Anker-Links zu Tickets
- Card-Layout statt Tabelle fuer Tickets
- Oeffentlicher Kommentar pro Ticket prominent angezeigt
- Auto-Hide fuer inaktive Tickets (>3 Monate) mit Toggle

## Akzeptanzkriterien
- [ ] Intro-Bereich mit Willkommenstext und Teilnahme-Einladung
- [ ] Kontakt-Box mit konfigurierbaren Kontaktdaten
- [ ] Inhaltsverzeichnis am Seitenanfang mit Anker-Links
- [ ] Card-Layout pro Ticket mit Status-Badge, Titel, oeffentlicher Kommentar
- [ ] Hover-Effekte auf Karten
- [ ] E2E-Test fuer oeffentlichen Bereich
