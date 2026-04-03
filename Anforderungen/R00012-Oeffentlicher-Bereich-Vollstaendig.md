---
id: R00012
titel: "Oeffentlicher Bereich vollstaendig abbilden"
typ: Feature
status: Teilweise umgesetzt
erstellt: 2026-04-03
---

# R00012: Oeffentlicher Bereich vollstaendig abbilden

## Zusammenfassung
Der oeffentliche Bereich (alt: `public_information_saus/`) ist bewusst vom Admin-Tool getrennt, damit Webcrawler, die die oeffentlichen Seiten finden, nicht den Weg zum Verwaltungstool entdecken.

## Sicherheitskonzept (bereits umgesetzt)
- **Konfigurierbarer Route-Prefix**: `SAUS_PUBLIC_ROUTE_PREFIX` in `.env` (Standard: `public-information`)
- **robots.txt**: Blockt alles (`Disallow: /`) ausser den oeffentlichen Bereich (`Allow: /public-information/`)
- **Kein Link zum Admin-Tool**: Oeffentliches Layout nennt weder "SAUS-i" noch verlinkt es zum Admin
- **Generischer Titel**: "Siedlungsausschuss" statt "SAUS-ES Ticket-System"
- **Separate Layouts**: `layouts/public.blade.php` hat keine Navigation zum Admin
- In der Produktion war der oeffentliche Bereich unter einem eigenen Verzeichnis (`public_information_saus/`) gehostet, um die Trennung auch auf Dateisystem-Ebene sicherzustellen

## Ist-Zustand (was noch fehlt)
Die oeffentliche Ticket-Seite zeigt aktuell nur eine einfache Tabelle. Die alte Anwendung hatte:
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
- [x] Route-Prefix konfigurierbar via .env
- [x] robots.txt blockt Admin-Bereich
- [x] Oeffentliches Layout ohne Admin-Links
- [ ] Intro-Bereich mit Willkommenstext und Teilnahme-Einladung
- [ ] Kontakt-Box mit konfigurierbaren Kontaktdaten
- [ ] Inhaltsverzeichnis am Seitenanfang mit Anker-Links
- [ ] Card-Layout pro Ticket mit Status-Badge, Titel, oeffentlicher Kommentar
- [ ] Hover-Effekte auf Karten
- [ ] E2E-Test fuer oeffentlichen Bereich
