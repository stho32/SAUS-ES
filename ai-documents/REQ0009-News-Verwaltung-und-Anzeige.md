# News-Verwaltung für SAUS-ES

## Zusammenfassung
Implementierung eines News-Bereichs mit Verwaltungsoberfläche in der internen Anwendung und öffentlicher Anzeigeseite, analog zur bestehenden Ticket-Darstellung.

## Geschäftlicher Nutzen
Informationsvermittlung über Veranstaltungen und wichtige Ereignisse an Genossenschaftsmitglieder mit klarer Trennung zwischen Erfassungs- und Veranstaltungsdatum.

## Funktionale Anforderungen
- CRUD-Operationen für News-Artikel in der internen Verwaltung (php/)
- Erfassung: Titel, HTML-Text, ein Bild, Erstelldatum (automatisch), Veranstaltungsdatum (nur Datum), Erfasser
- Bildupload mit Speicherung analog zu ticket_attachments
- Öffentliche Anzeigeseite (public_php_app/news.php) mit Pagination (10 Artikel/Seite)
- Suchfunktion auf public_php_app/news.php: Durchsucht Titel und Textinhalt
- Bilder als Thumbnails (200px Breite) mit Klick-Vergrößerung
- Sichere Bildauslieferung über PHP-Proxy (kein direkter Dateisystem-Zugriff)
- Layout: Titel oben, Bild rechts, Text links, chronologisch sortiert nach Veranstaltungsdatum
- Vor/Zurück-Navigation zwischen Seiten

## Nicht-funktionale Anforderungen
- Sicherheit: XSS-Schutz via htmlspecialchars(), File-Upload-Validierung, PDO Prepared Statements
- Sicherheit Bildauslieferung: Path-Traversal-Schutz, Whitelist-Validierung für Bildtypen, ID-basierter Zugriff
- Sicherheit: Verhinderung von Zugriff auf Dateien außerhalb von uploads/news/
- Benutzerfreundlichkeit: Responsive Design mit Bootstrap 5.3, Touch-Targets mind. 44px
- Performance: Pagination limitiert Datenbankabfragen auf 10 Datensätze
- Performance: Dynamische Thumbnail-Generierung mit GD-Library (200px Breite, proportional)

## Akzeptanzkriterien
- [ ] Datenbankschema erstellt: Tabelle `news` mit Feldern id, title, content (TEXT für HTML), image_filename, event_date (DATE), created_at (DATETIME), created_by (VARCHAR)
- [ ] Bildupload-Verzeichnis `uploads/news/` angelegt mit korrekten Berechtigungen
- [ ] Verwaltungsseite php/news.php zeigt alle News-Artikel in Tabelle mit Bearbeitungs-/Löschfunktionen
- [ ] Formular zum Erstellen/Bearbeiten mit Titel, HTML-Editor, Bild-Upload, Event-Date-Picker
- [ ] API-Endpunkte: create_news.php, update_news.php, delete_news.php mit JSON-Response
- [ ] Öffentliche Seite public_php_app/news.php zeigt genau 10 Artikel pro Seite
- [ ] Suchfeld auf public_php_app/news.php mit LIKE-Suche in title und content (analog zu index.php)
- [ ] Suche funktioniert mit URL-Parameter ?search=suchbegriff und bleibt über Pagination erhalten
- [ ] Pagination funktioniert mit URL-Parametern (?page=1, ?page=2, etc.)
- [ ] Layout entspricht Mockup: Titel (h2), Bild float:right, Text links, responsive
- [ ] Bild-Proxy public_php_app/api/get_news_image.php ausliefert Thumbnails und Vollbilder
- [ ] Thumbnail-Parameter: ?id=<news_id>&thumbnail=true erzeugt 200px-Bild
- [ ] Vollbild-Parameter: ?id=<news_id> liefert Originalbild
- [ ] Path-Traversal-Schutz: Nur Zugriff auf uploads/news/<id>/<filename> erlaubt
- [ ] Whitelist-Validierung: Nur image/jpeg, image/png, image/gif erlaubt
- [ ] Klick auf Thumbnail öffnet Vollbild in Modal oder neuem Tab
- [ ] Sortierung nach event_date DESC (neueste Events zuerst)
- [ ] Migration-SQL-Datei mysql/17_add_news.sql erstellt

## Betroffene Verzeichnisstruktur
- Interne Verwaltung: `php/news.php` (Hauptseite), `php/news_edit.php` (Formular)
- API: `php/api/create_news.php`, `php/api/update_news.php`, `php/api/delete_news.php`, `php/api/upload_news_image.php`
- Öffentliche Seite: `public_php_app/news.php`
- Bild-Proxy: `public_php_app/api/get_news_image.php` (sichere Bildauslieferung mit Thumbnail-Support)
- Datenbankschema: `mysql/17_add_news.sql`
- Bild-Uploads: `uploads/news/<news_id>/` (neu anzulegen, analog zu tickets)
- Shared Code: `php/includes/news_functions.php` (Helper-Funktionen für News)

## Technische Überlegungen
- Neue Tabelle `news` in mysql/17_add_news.sql mit InnoDB, utf8mb4_unicode_ci
- Bildupload-Logik analog zu `attachment_functions.php` (Sanitization, Größen-/Typ-Validierung)
- Bild-Proxy analog zu `php/api/get_attachment.php` mit folgenden Sicherheitsmaßnahmen:
  - DB-Lookup der News-ID zur Validierung der Existenz
  - Konstruktion des Dateipfads: uploads/news/{news_id}/{filename_from_db}
  - Prüfung file_exists() nur für konstruierten Pfad
  - Whitelist für MIME-Types: image/jpeg, image/png, image/gif
  - EXIF-Orientierung korrigieren (wie in get_attachment.php:74-94)
- Thumbnail-Generierung mit GD:
  - imagecopyresampled() für hohe Qualität
  - Transparenz-Handling für PNG/GIF (get_attachment.php:104-109)
  - 200px Breite, proportionale Höhe
  - JPEG-Qualität 85, PNG-Kompression 6
- HTML-Content wird in Textarea mit TinyMCE oder ähnlichem Editor erfasst
- Pagination-Logik: LIMIT 10 OFFSET (page-1)*10, Link-Generierung für Vor/Zurück
- Suchlogik: SQL WHERE (title LIKE :search OR content LIKE :search) analog zu index.php:62
- Suchbegriff wird in Pagination-Links mitgeführt (?page=2&search=...)
- Layout public_php_app/news.php nutzt Bootstrap-Grid und ähnliche Styles wie index.php
- Authentifizierung: Interne Seiten nutzen auth_check.php, öffentliche Seite KEINE Auth (News sind öffentlich)
- created_by wird aus $_SESSION['username'] übernommen (analog zu Kommentaren)

## Abhängigkeiten
- Bootstrap 5.3 (bereits vorhanden)
- PDO/Database Singleton (php/includes/Database.php)
- Authentifizierungssystem (php/includes/auth_check.php)
- Bestehende Upload-Infrastruktur

## Offene Fragen
- Soll es eine maximale Bildgröße geben (z.B. 2MB wie bei Tickets)?
- Welche Bildformate sind erlaubt (JPG, PNG, WebP)?
- Soll der HTML-Editor ein WYSIWYG-Editor sein oder Markdown?
- Dürfen alle authentifizierten User News erstellen oder nur bestimmte Rollen?
- Sollen News archivierbar/löschbar sein oder nur deaktivierbar?
- Wie sollen sehr alte News behandelt werden (automatisches Archivieren nach X Monaten)?

## Manuelle Vorbereitungstätigkeiten
- Verzeichnis `uploads/news/` mit Schreibrechten für Webserver anlegen
- Entscheidung über HTML-Editor (TinyMCE, CKEditor, oder einfaches textarea)
- Klärung der Berechtigungen (wer darf News erstellen/bearbeiten)

## Manuelle Nachbereitungstätigkeiten
- Migration mysql/17_add_news.sql in Produktivdatenbank einspielen
- Erste Test-News-Artikel anlegen
- Navigation/Link zur news.php in public_php_app einbauen (z.B. in Header/Menü)
- Dokumentation für Redakteure erstellen (wie News anlegen)

## Missing-Docs
- Keine spezifische Dokumentation zum bestehenden Berechtigungssystem (Rollen/Rechte) vorhanden
- Unklar ob es Standards für HTML-Editoren im Projekt gibt
- Keine Architektur-Dokumentation zur Pagination-Strategie (könnte wiederverwendbar sein)
- File-Upload-Limits und -Validierung sollten zentral dokumentiert werden
