# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Database Setup
```bash
# Create database and user
mysql -u root -p -e "CREATE DATABASE saus_es CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'saus_user'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON saus_es.* TO 'saus_user'@'localhost'; FLUSH PRIVILEGES;"

# Run all migrations in order (01 through 16)
for file in mysql/*.sql; do mysql -u saus_user -p saus_es < "$file"; done
```

### Configuration
```bash
# Copy all configuration templates
cp php/config.example.php php/config.php
cp php/includes/paths_config.example.php php/includes/paths_config.php
cp public_php_app/includes/config.example.php public_php_app/includes/config.php
cp public_php_app/imageview/paths_config.example.php public_php_app/imageview/paths_config.php
```

### Development
- **Run locally**: Use Apache with PHP 8.0+ and mod_rewrite enabled
- **No build process**: Static assets served directly from php/assets/
- **Database migrations**: Execute mysql/*.sql files in numerical order (no migration tracking table)
- **Error logs**: Check logs/error.log for application errors
- **Maintenance**: Backup database with `mysqldump -u saus_user -p saus_es > backup_$(date +%Y%m%d).sql`

## Architecture

### Overview
SAUS-ES is a ticket management system for German housing cooperatives with two main interfaces:
1. **Internal management** (`/php/`): Authenticated ticket management with full CRUD operations
2. **Public view** (`/public_php_app/`): Read-only public ticket display

### Key Design Patterns

#### Authentication
- **Master-link system**: No traditional login; uses secure tokens in `master_links` table
- **Partner-links**: Limited access tokens for specific tickets
- **SecretString**: Public image viewing authentication

#### Multi-Assignee Support
Tickets can have multiple assignees separated by comma or plus sign. Functions in `includes/ticket_functions.php` handle parsing and statistics attribution.

#### Comment System
- Threaded comments with voting (👍/👎)
- Edit history tracking in `comment_edits` table
- Visibility control (public/private toggle)
- Markdown-like formatting via `comment_formatter.php`

#### File Handling
- Attachments stored in `uploads/tickets/` with metadata in `ticket_attachments` table
- External image viewing via `public_php_app/imageview/` using SecretString authentication
- Maximum file size and allowed types configured in `config.php`

### Database Considerations
- All tables use InnoDB for transaction support
- UTF8MB4 encoding for full Unicode support (including emojis)
- Prepared statements via PDO for all queries
- Views for complex statistics queries

### Frontend
- Bootstrap 5.3 for responsive design
- Chart.js for statistics visualization
- DataTables for sortable/searchable tables
- Mobile-first with 44px minimum touch targets
- No JavaScript build process; all assets served statically

### Security
- XSS protection via htmlspecialchars() on all output
- CSRF protection via session tokens
- SQL injection prevention via PDO prepared statements
- File upload validation and sanitization
- Secure random token generation for authentication links

## Core Components

### Database Layer
- **Singleton pattern**: `Database::getInstance()` provides centralized connection management
- **PDO configuration**: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES => false`
- **Error handling**: All database errors logged via ErrorLogger singleton

### Authentication Flow
1. User visits with `?master_code=<code>` parameter
2. `auth_check.php` validates code against `master_links` table
3. Valid code stored in `$_SESSION['master_code']`
4. First-time users prompted for "Namenskürzel" (stored in `$_SESSION['username']`)
5. Partner links follow similar pattern but with restricted access

### Key Database Features
- **Triggers**: Auto-update `partners.partner_list` on insert/update/delete
- **Functions**: `get_ticket_partners()`, `has_sufficient_positive_votes()`, `generate_random_string()`
- **Views**: `comment_statistics`, `ticket_statistics` for aggregated vote counts
- **SecretString**: Auto-generated 50-char code for public image viewing (created by trigger on ticket insert)

### Ticket Number Format
Generated as `YYYYMMDD-XXXX` where XXXX is a sequential number for that day.

### API Endpoints
All API files in `php/api/` return JSON and use `api_auth_check.php` for validation. Common pattern:
```php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/api_auth_check.php';
// Process request
echo json_encode(['success' => true, 'data' => $result]);
```

### Comment Formatting
`comment_formatter.php` provides Markdown-like features:
- Bold: `**text**`
- Italic: `*text*`
- Links: Auto-detected and converted to clickable links
- Line breaks preserved

### File Uploads
- Stored in `uploads/tickets/` with sanitized filenames
- Metadata in `ticket_attachments` table
- Validation for file type and size in `attachment_functions.php`

## Important Notes

- **No package managers**: This project has no npm, composer, or build tools
- **Strict typing**: All PHP files use `declare(strict_types=1)`
- **German language**: UI, comments, and variable names are primarily in German
- **No automated tests**: Testing is manual via browser
- **Windows development**: File paths use backslashes on Windows systems