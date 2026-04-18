# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Quick Start (Docker)
```bash
# Einmalig: .env am Repo-Root anlegen und einen APP_KEY erzeugen
cp .env.example .env
php -r "echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;" >> .env
# falls lokal kein PHP: openssl rand -base64 32 und manuell mit "base64:" Prefix eintragen

docker-compose up -d
# Admin:  http://localhost:8000/saus/?master_code=test_master_2025
# Public: http://localhost:8000/public_information_saus/
```

### Quick Start (Laravel Herd / local PHP)
```bash
docker compose up -d db            # MariaDB starten (Port 3307)
cd Source
composer install
cp .env.example .env
php artisan key:generate
# .env: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3307, DB_DATABASE=saus_es
php artisan migrate --seed
php artisan serve
# Admin:  http://localhost:8000/saus/?master_code=test_master_2025
# Public: http://localhost:8000/public_information_saus/
```

### Tests
```bash
cd Source
php artisan test                          # All Pest tests
php artisan test --filter=TicketTest      # Specific test file
php artisan dusk                          # E2E browser tests
```

### Database
```bash
cd Source
php artisan migrate              # Run pending migrations
php artisan migrate:fresh --seed # Reset DB with test data
php artisan db:seed              # Seed data only
```

### Maintenance
```bash
php artisan route:list           # Show all routes
php artisan config:clear         # Clear config cache
mysqldump -u saus_user -p saus_es > backup_$(date +%Y%m%d).sql
```

## Architecture

### Overview
SAUS-ES is a ticket management system for German housing cooperatives ("Wohnungsbaugenossenschaften"), built with **Laravel 13 + Blade + Tailwind CSS**.

Two main interfaces:
1. **Internal management** (`/`): Authenticated ticket management with full CRUD — requires master_link
2. **Public view** (`/public/`): Read-only public ticket and news display — no authentication

### Technology Stack
- **Backend**: Laravel 13.3, PHP 8.4
- **Frontend**: Tailwind CSS (CDN), Chart.js, Bootstrap Icons
- **Database**: MariaDB 10.6 — immer MariaDB verwenden, auch in Entwicklung und Tests (kein SQLite)
- **Testing**: Pest 4 (137 tests, 264 assertions)
- **Dev Environment**: Docker (PHP 8.4 + Apache + MariaDB) or Laravel Herd

### Key Design Patterns

#### Authentication (Custom — no Laravel Auth)
- **Master-link system**: No traditional login; uses secure tokens in `master_links` table
- **MasterLinkAuth middleware**: Validates `?master_code=` parameter or session value
- **EnsureUsername middleware**: Requires "Namenskuerzel" before accessing protected routes
- **Partner-links**: Limited access tokens for specific tickets
- **SecretString**: Public image viewing authentication (50-char random code per ticket)

#### Multi-Assignee Support
Tickets can have multiple assignees separated by comma or plus sign. `StatisticsController` handles parsing and attribution.

#### Comment System
- Comments with voting (up/down)
- Edit history tracking (is_edited flag)
- Visibility control (public/private toggle via master-link users)
- Markdown-like formatting via `App\Services\CommentFormatter`
- System comments auto-generated for status changes, follow-up dates, contact person links

#### File Handling
- Ticket attachments stored in `Source/uploads/tickets/{ticketId}/`
- News images stored in `Source/uploads/news/`
- Image thumbnails generated via GD library (200px width)
- External image viewing via `/public_information_saus/imageview/{code}` using SecretString

### Repository Structure
```
SAUS-ES/                        # Repository root
├── Anforderungen/              # Requirements (R00001-R00017)
├── Dokumentation/              # Style-Guide, architecture docs
├── Source/                     # Laravel 13 application
│   ├── app/
│   │   ├── Http/Controllers/   # 12 page controllers + 6 API controllers
│   │   ├── Http/Middleware/    # MasterLinkAuth, EnsureUsername, PartnerLinkAuth
│   │   ├── Models/            # 10 Eloquent models (existing DB schema)
│   │   ├── Services/          # CommentFormatter, TicketNumberGenerator, ActivityHelper
│   │   └── Providers/         # AppServiceProvider (rate limiting)
│   ├── bootstrap/app.php      # Middleware aliases, exception handling
│   ├── config/saus.php        # SAUS-specific config (upload paths, vote threshold)
│   ├── database/
│   │   ├── migrations/        # Single migration matching existing DB schema
│   │   └── seeders/           # TicketStatusSeeder, TestDataSeeder
│   ├── docker/                # Dockerfile, apache.conf, entrypoint.sh
│   ├── public/                # Laravel web root (index.php, .htaccess)
│   ├── resources/views/
│   │   ├── layouts/           # app.blade.php (internal), public.blade.php
│   │   ├── tickets/           # index, show, create, edit, email
│   │   ├── news/, statistics/, follow-up/, contact-persons/, saus-news/
│   │   ├── public/            # Public ticket/news views
│   │   └── auth/              # username, error, logout
│   ├── routes/web.php         # All routes (admin + public + API)
│   ├── tests/
│   │   ├── Feature/           # 8 Pest test files
│   │   ├── Unit/              # 2 Pest test files
│   │   └── Browser/           # 7 Dusk E2E test files
│   ├── uploads/               # Ticket and news file uploads
│   ├── composer.json
│   └── phpunit.xml
├── docker-compose.yml         # Docker orchestration (references Source/)
├── CLAUDE.md
├── README.md
└── LICENSE
```

### Database Compatibility
- **100% compatible** with existing MariaDB 10.6 database
- Eloquent models map to existing table names and columns exactly
- Existing triggers, functions, and views preserved
- File storage paths unchanged (`php/uploads/tickets/`, `php/uploads/news/`)
- **Kein SQLite**: PHP-Anwendungen laufen in Produktion immer mit MariaDB — daher auch in Entwicklung und Tests ausschließlich MariaDB verwenden. SQLite hat subtile Verhaltensunterschiede (fehlende Trigger, andere Typisierung, kein ENUM), die erst in Produktion auffallen. Die MariaDB-Instanz wird per `docker compose up -d db` gestartet (Port 3307 lokal).

### Database Features
- **Triggers**: `tickets_before_insert` auto-generates secret_string
- **Functions**: `get_ticket_partners()`, `has_sufficient_positive_votes()`, `generate_random_string()`
- **Views**: `comment_statistics`, `ticket_statistics` for aggregated vote counts
- **Ticket Number**: `YYYYMMDD-XXXX` format via `TicketNumberGenerator` service

### Security
- **CSRF**: Laravel built-in (all forms use @csrf, API uses X-CSRF-TOKEN header)
- **XSS**: Blade auto-escaping {{ }} + CommentFormatter with htmlspecialchars()
- **SQL Injection**: Eloquent ORM with parameterized queries
- **Rate Limiting**: Configured in AppServiceProvider (60/min API, 10/min auth)
- **File Upload**: MIME validation, extension whitelist, safe filename generation
- **Session**: Secure, httponly cookies via Laravel session config
- **Headers**: X-Frame-Options, X-Content-Type-Options, HSTS via Apache config

### API Endpoints
All API endpoints under `/api/` require master_link session and return JSON:
```php
// Success: {"success": true, "data": {...}}
// Error:   {"success": false, "message": "..."}
```

## Important Notes

- **Working directory**: All `php artisan` and `composer` commands must be run from `Source/`
- **German language**: UI, domain variables, and database columns are in German
- **Umlaute in Views**: Alle nach außen sichtbaren Texte (Blade Views, Fehlermeldungen, Flash Messages, Tooltips) müssen korrekte deutsche Umlaute verwenden (ä, ö, ü, ß), nicht die Umschreibungen (ae, oe, ue, ss). Nur in technischen Kontexten (Dateinamen, URLs, PHP-Code, Config-Keys) werden ASCII-Umschreibungen verwendet.
- **Strict typing**: All custom PHP files use `declare(strict_types=1)`
- **Windows development**: File paths use backslashes on Windows (Herd)
- **Docker**: `docker-compose up` from repo root starts the full environment
- **Test DB**: MariaDB (via Docker, Port 3307) — kein SQLite, damit Tests identisch zur Produktion laufen
- **Anforderungen**: Requirements documented in `Anforderungen/R00001-R00017`
