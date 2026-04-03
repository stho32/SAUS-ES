---
id: R00011
titel: "Framework-Migration auf Laravel 13"
typ: Feature
status: In Umsetzung
erstellt: 2026-04-03
---

# R00011: Framework-Migration auf Laravel 13

## Zusammenfassung
Migration der bestehenden Vanilla-PHP-Anwendung auf das Laravel 13 Framework mit Tailwind CSS, unter Beibehaltung der bestehenden MySQL-Datenbank und Dateiablage.

## Anforderungen
- 100% Kompatibilitaet mit bestehender MySQL-Datenbank (alle Tabellen, Views, Trigger, Funktionen)
- 100% Kompatibilitaet mit bestehender Dateiablage (uploads/tickets/, uploads/news/)
- Alle bestehenden Features muessen 1:1 abgebildet werden
- Architektur basierend auf globaler Vorlage `laravel-blade-tailwind`
- Tailwind CSS via CDN (kein Build-Prozess)
- Docker-basiertes Entwicklungssetup mit MariaDB 10.6
- Vollstaendige Test-Suite (Feature-Tests, Unit-Tests, E2E-Tests)
- Alle Sicherheitsverbesserungen aus R00010 integriert (CSRF, Rate-Limiting, CSP)

## Akzeptanzkriterien
- [ ] Laravel 13 Projekt mit Tailwind CSS laeuft in Docker
- [ ] Alle Eloquent Models bilden bestehende DB-Tabellen korrekt ab
- [ ] MasterLink-Authentifizierung als Custom Middleware implementiert
- [ ] Alle Ticket-CRUD-Operationen funktional
- [ ] Kommentar-System mit Voting funktional
- [ ] Datei-Anhaenge Upload/Download funktional (gleiche Verzeichnisse)
- [ ] News-Verwaltung und oeffentliche Anzeige funktional
- [ ] Kontaktpersonen-Verwaltung funktional
- [ ] Statistik-Dashboard funktional
- [ ] Wiedervorlage-Ansicht funktional
- [ ] Oeffentliche Ticket-Ansicht funktional
- [ ] Bild-Galerie mit SecretString funktional
- [ ] CSRF-Schutz aktiv (Laravel built-in)
- [ ] Rate-Limiting konfiguriert
- [ ] Feature-Tests fuer alle Endpoints
- [ ] E2E-Tests fuer kritische User-Flows
- [ ] docker-compose up startet komplette Umgebung
