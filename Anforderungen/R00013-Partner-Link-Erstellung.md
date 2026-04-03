---
id: R00013
titel: "Partner-Link-Erstellung im Ticket"
typ: Feature
status: Offen
erstellt: 2026-04-03
---

# R00013: Partner-Link-Erstellung im Ticket

## Zusammenfassung
In der alten Anwendung koennen Partner-Links fuer Tickets erstellt werden, damit externe Stakeholder (z.B. Genossenschaftsvertreter) eingeschraenkten Zugriff auf ein bestimmtes Ticket erhalten. Diese Funktionalitaet fehlt in der Laravel-Portierung.

## Ist-Zustand
- Partner-Model und DB-Tabelle existieren
- PartnerLinkAuth-Middleware existiert
- Es gibt keinen API-Endpunkt zum Erstellen von Partner-Links
- Es gibt keine UI im Ticket-Detail-View zum Anlegen/Kopieren von Partner-Links

## Anforderungen
- API-Endpunkt `POST /api/tickets/{ticket}/partners` zum Erstellen eines Partner-Links
- Partner-Bereich in der Ticket-Detail-Ansicht mit:
  - Liste bestehender Partner-Links
  - "Partner hinzufuegen"-Formular (Name eingeben)
  - Kopierfunktion fuer den generierten Link (Clipboard API)
  - "Kopiert!"-Feedback nach dem Kopieren
- Partner-Link-Zugang: Zeigt nur das verknuepfte Ticket (read-only)

## Akzeptanzkriterien
- [ ] API-Endpunkt zum Erstellen von Partner-Links
- [ ] Partner-Bereich im Ticket-Detail-View
- [ ] Link wird als 32-Zeichen-Hex-Code generiert
- [ ] Kopierfunktion mit visuelem Feedback
- [ ] Partner-Link zeigt nur das verknuepfte Ticket
- [ ] Feature-Test und E2E-Test
