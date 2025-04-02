# Setup-Anleitung für REQ0001: Externe Links für Bilder in Tickets

Diese Anleitung beschreibt die notwendigen Schritte zur Einrichtung der neuen Funktionalität für externe Bilderlinks.

## 1. SQL-Migration ausführen

Führen Sie die neue SQL-Migration für die `secret_string`-Spalte aus:

```bash
mysql -u [benutzer] -p [datenbank] < mysql/14_add_secret_string.sql
```

Die Migration fügt folgende Änderungen hinzu:
- Eine neue Spalte `secret_string` in der `tickets`-Tabelle
- Generiert automatisch Codes für bestehende Tickets
- Erstellt einen Trigger und die benötigte Funktion `generate_random_string`

### Hinweis zu SQL-Berechtigungen

Falls ein Fehler wie `FUNCTION generate_random_string does not exist` auftritt, stellen Sie sicher, dass:

1. Der Datenbankbenutzer Berechtigungen zum Erstellen von Funktionen hat
2. Die Migration vollständig abläuft und die Funktion `generate_random_string` nicht gelöscht wird
3. Bei wiederholter Migration wird die Funktion neu erstellt

## 2. Konfiguration prüfen und anpassen

### a) Konfiguration der öffentlichen Anwendung

Stellen Sie sicher, dass die Konfigurationsdatei für die öffentliche Anwendung existiert:

```bash
cp public_php_app/includes/config.example.php public_php_app/includes/config.php
```

Passen Sie die Datenbankverbindung in `public_php_app/includes/config.php` entsprechend an.

### b) Pfadkonfiguration erstellen

Kopieren Sie die Beispielkonfigurationen und passen Sie sie an Ihre Serverumgebung an:

1. Für die Hauptanwendung:
   ```bash
   cp php/includes/paths_config.example.php php/includes/paths_config.php
   ```

2. Für die Bildergalerie:
   ```bash
   cp public_php_app/imageview/paths_config.example.php public_php_app/imageview/paths_config.php
   ```

Je nach Serverumgebung müssen möglicherweise die Pfade in den Konfigurationsdateien angepasst werden:

1. In `php/includes/paths_config.php`:
   ```php
   return [
       'public_app_url' => '../public_php_app', // Anpassen an tatsächlichen Pfad
       'image_gallery_url' => '../public_php_app/imageview',
       'root_path' => __DIR__ . '/../..',
   ];
   ```

2. In `public_php_app/imageview/paths_config.php`:
   ```php
   return [
       'base_path' => __DIR__ . '/../../', // Anpassen an tatsächlichen Pfad
       'uploads_path' => 'php/uploads/tickets/', // Anpassen falls nötig
   ];
   ```

## 3. Berechtigungen prüfen

Stellen Sie sicher, dass der Webserver Leserechte für die Verzeichnisse hat:

1. Der Upload-Ordner muss für den Webserver lesbar sein:
   ```bash
   chmod -R 755 php/uploads/tickets
   ```

2. Die `imageview`-Datei sollte über HTTP erreichbar sein:
   ```
   https://ihre-domain.de/public_php_app/imageview/?code=[SECRET_CODE]
   ```

## 4. Datenbankverbindung beachten

Die Implementierung wurde so angepasst, dass sie mit unterschiedlichen Datenbankverbindungstypen funktioniert:

- Verwendet der Server ein `Database`-Objekt mit `getConnection()`-Methode
- ODER verwendet der Server direkt ein `PDO`-Objekt

Diese Anpassung behebt einen möglichen Fehler wie:
```
Fatal error: Uncaught Error: Call to undefined method PDO::getConnection()
```

Sollte dieser Fehler dennoch auftreten, prüfen Sie, ob die Dateien `attachment_functions.php` und `imageview/index.php` die neueste Version enthalten.

## 5. Funktionalität testen

1. Erstellen Sie ein Ticket mit Bildanhängen
2. Öffnen Sie das Ticket in der E-Mail-Ansicht (`ticket_email.php?id=XX`)
3. Es sollte ein Link zur Bildergalerie angezeigt werden
4. Folgen Sie dem Link, um zu prüfen, ob die Bilder korrekt angezeigt werden

## Häufige Probleme und Lösungen

1. **Link funktioniert nicht:**
   - Prüfen Sie die Pfadkonfigurationen in den `paths_config.php`-Dateien
   - Stellen Sie sicher, dass die öffentliche Anwendung über den Browser erreichbar ist

2. **Bilder werden nicht angezeigt:**
   - Prüfen Sie die Berechtigungen des Upload-Verzeichnisses
   - Prüfen Sie, ob die Pfade in `public_php_app/imageview/paths_config.php` korrekt sind
   
3. **Datenbank-Fehler:**
   - Stellen Sie sicher, dass die Migration korrekt ausgeführt wurde
   - Überprüfen Sie, ob die Datenbankkonfiguration in `public_php_app/includes/config.php` korrekt ist
   - Prüfen Sie, ob der Datenbankbenutzer Leserechte für die Tabellen hat
