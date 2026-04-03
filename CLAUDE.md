# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Quick Start (Docker)
```bash
docker-compose up -d
# App available at http://localhost:8000
# Login with: http://localhost:8000/?master_code=test_master_2025
```

### Quick Start (Laravel Herd / local PHP)
```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env to point to your MariaDB
php artisan migrate --seed
php artisan serve
# App available at http://localhost:8000
```

### Tests
```bash
php artisan test                          # All tests (137 tests)
php artisan test --filter=TicketTest      # Specific test file
php artisan test --filter="kann erstellt" # By test name
```

### Database
```bash
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
- **Database**: MariaDB 10.6 / MySQL (SQLite for tests)
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
- Ticket attachments stored in `php/uploads/tickets/{ticketId}/` (compatible with legacy paths)
- News images stored in `php/uploads/news/`
- Image thumbnails generated via GD library (200px width)
- External image viewing via `/public/imageview/{code}` using SecretString

### Project Structure (Laravel)
```
app/
  Http/Controllers/       # 12 page controllers + 6 API controllers
  Http/Middleware/         # MasterLinkAuth, EnsureUsername, PartnerLinkAuth
  Models/                 # 10 Eloquent models (existing DB schema)
  Services/               # CommentFormatter, TicketNumberGenerator, ActivityHelper
  Providers/              # AppServiceProvider (rate limiting)
bootstrap/app.php         # Middleware aliases, exception handling
config/saus.php           # SAUS-specific config (upload paths, vote threshold)
database/
  migrations/             # Single migration matching existing DB schema
  seeders/                # TicketStatusSeeder, TestDataSeeder
resources/views/
  layouts/                # app.blade.php (internal), public.blade.php
  tickets/                # index, show, create, edit, email
  news/, statistics/, follow-up/, contact-persons/, saus-news/, website-view/
  public/                 # Public ticket/news views
  auth/                   # username, error, logout
routes/web.php            # 49 routes (protected + public + API)
tests/Feature/            # 8 test files (auth, tickets, comments, votes, news, etc.)
tests/Unit/               # 2 test files (CommentFormatter, TicketNumberGenerator)
```

### Database Compatibility
- **100% compatible** with existing MariaDB 10.6 database
- Eloquent models map to existing table names and columns exactly
- Existing triggers, functions, and views preserved
- File storage paths unchanged (`php/uploads/tickets/`, `php/uploads/news/`)

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

### Legacy Code
The original vanilla PHP code remains in `php/` and `public_php_app/` for reference. The Laravel app (`app/`, `resources/`, `routes/`) is the active codebase.

## Important Notes

- **German language**: UI, domain variables, and database columns are in German
- **Strict typing**: All custom PHP files use `declare(strict_types=1)`
- **Windows development**: File paths use backslashes on Windows (Herd)
- **Docker alternative**: `docker-compose up` for full environment
- **Test DB**: SQLite in-memory (phpunit.xml) — MySQL-specific features skipped in tests
- **Anforderungen**: Requirements documented in `Anforderungen/R00001-R00011`
