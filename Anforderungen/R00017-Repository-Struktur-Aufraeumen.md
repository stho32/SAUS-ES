---
id: R00017
titel: "Repository-Struktur aufräumen: Source-Ordner einführen"
typ: Wartung
status: Offen
erstellt: 2026-04-04
---

# R00017: Repository-Struktur aufräumen — Source-Ordner einführen

## Beschreibung

Das Repository-Root enthält aktuell eine Mischung aus Laravel-App-Verzeichnissen, alten Legacy-Ordnern und Dokumentation. Die Struktur soll aufgeräumt werden: Die Laravel-App kommt in einen `Source/`-Ordner, Legacy-Code wird entfernt.

## Ist-Zustand (18 Verzeichnisse + 12 Dateien im Root)

### Verzeichnisse

| Verzeichnis | Typ | Aktion |
|-------------|-----|--------|
| `Anforderungen/` | Dokumentation | **Bleibt** im Root |
| `Dokumentation/` | Dokumentation | **Bleibt** im Root |
| `app/` | Laravel | → `Source/app/` |
| `bootstrap/` | Laravel | → `Source/bootstrap/` |
| `config/` | Laravel | → `Source/config/` |
| `database/` | Laravel | → `Source/database/` |
| `docker/` | Infrastruktur | → `Source/docker/` |
| `public/` | Laravel | → `Source/public/` |
| `resources/` | Laravel | → `Source/resources/` |
| `routes/` | Laravel | → `Source/routes/` |
| `storage/` | Laravel | → `Source/storage/` |
| `tests/` | Laravel | → `Source/tests/` |
| `vendor/` | Laravel (gitignored) | → `Source/vendor/` |
| `ai-documents/` | **Legacy** | **Löschen** (bereits nach `Anforderungen/` überführt) |
| `php/` | **Legacy** | **Löschen** (alte Vanilla-PHP-App, ersetzt durch Laravel) |
| `public_php_app/` | **Legacy** | **Löschen** (alter öffentlicher Bereich, ersetzt durch Laravel) |
| `mysql/` | **Legacy** | **Löschen** (SQL-Migrationen jetzt in `database/migrations/`) |
| `logs/` | **Legacy** | **Löschen** (Laravel nutzt `storage/logs/`) |

### Dateien im Root

| Datei | Typ | Aktion |
|-------|-----|--------|
| `CLAUDE.md` | Dokumentation | **Bleibt** im Root |
| `README.md` | Dokumentation | **Bleibt** im Root |
| `LICENSE` | Dokumentation | **Bleibt** im Root |
| `.gitignore` | Git | **Bleibt** im Root (Pfade anpassen) |
| `.editorconfig` | Tooling | **Bleibt** im Root |
| `docker-compose.yml` | Infrastruktur | **Bleibt** im Root (Pfade auf `Source/` anpassen) |
| `artisan` | Laravel | → `Source/artisan` |
| `composer.json` | Laravel | → `Source/composer.json` |
| `composer.lock` | Laravel | → `Source/composer.lock` |
| `package.json` | Laravel | → `Source/package.json` |
| `phpunit.xml` | Laravel | → `Source/phpunit.xml` |
| `vite.config.js` | Laravel | → `Source/vite.config.js` |
| `IMPLEMENTATIONSDETAILS.md` | **Legacy** | **Löschen** (ersetzt durch CLAUDE.md) |
| `SETUP.md` | **Legacy** | **Löschen** (ersetzt durch CLAUDE.md + Docker) |

### Hinweis: Upload-Verzeichnisse

Die Produktivdaten in `php/uploads/tickets/` und `php/uploads/news/` müssen in die neue Struktur überführt werden. Der Upload-Pfad in `config/saus.php` muss auf `uploads/tickets` bzw. `uploads/news` (relativ zu `Source/`) angepasst werden.

## Soll-Zustand

```
SAUS-ES/
├── Anforderungen/          (Anforderungsdokumente R00001-R00017)
├── Dokumentation/          (Style-Guide, Architektur-Docs)
├── Source/                 (Laravel 13 Anwendung)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── docker/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── tests/
│   ├── uploads/            (Ticket- und News-Uploads)
│   │   ├── tickets/
│   │   └── news/
│   ├── vendor/             (gitignored)
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   ├── package.json
│   ├── phpunit.xml
│   ├── vite.config.js
│   ├── .env
│   └── .env.example
├── .gitignore
├── .editorconfig
├── docker-compose.yml
├── CLAUDE.md
├── README.md
└── LICENSE
```

## Akzeptanzkriterien

### Struktur
- [ ] `Source/`-Ordner enthält die komplette Laravel-App
- [ ] Root enthält nur: `Anforderungen/`, `Dokumentation/`, `Source/`, Konfig-Dateien
- [ ] Legacy-Verzeichnisse entfernt: `ai-documents/`, `php/`, `public_php_app/`, `mysql/`, `logs/`
- [ ] Legacy-Dateien entfernt: `IMPLEMENTATIONSDETAILS.md`, `SETUP.md`

### Funktionalität
- [ ] `docker-compose.yml` verweist auf `Source/` als Build-Context
- [ ] `.gitignore` mit aktualisierten Pfaden
- [ ] Upload-Pfade in `config/saus.php` angepasst
- [ ] CLAUDE.md mit neuer Verzeichnisstruktur aktualisiert
- [ ] `php artisan serve` funktioniert aus `Source/`
- [ ] `docker-compose up` funktioniert vom Root

### Tests
- [ ] Alle 137 Pest-Tests bestehen (aus `Source/` ausgeführt)
- [ ] Alle 37 Dusk-E2E-Tests bestehen
- [ ] Upload/Download von Dateien funktioniert mit neuen Pfaden

## Abhängigkeiten

- Abhängig von: Keine
- Blockiert: Keine (aber alle zukünftigen Anforderungen müssen die neue Struktur berücksichtigen)

## Notizen

- Die Testbilder aus der Produktion (aktuell in `php/uploads/`) werden nach `Source/uploads/` verschoben
- Der `vendor/`-Ordner wird nicht mitverschoben — er wird via `composer install` in `Source/` neu erstellt
- `.env` und `.env.dusk.local` bleiben in `Source/` (gitignored)
