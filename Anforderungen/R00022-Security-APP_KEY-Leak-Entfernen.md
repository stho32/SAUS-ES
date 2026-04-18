---
id: R00022
titel: "Security: Hardkodierten APP_KEY aus docker-compose.yml entfernen"
typ: Wartung
status: Offen
erstellt: 2026-04-18
quelle: "GitHub Issue stho32/SAUS-ES#3"
---

# R00022: Hardkodierten APP_KEY aus docker-compose.yml entfernen

## Beschreibung

GitGuardian hat in Commit `92c5ec0` einen Laravel `APP_KEY` im öffentlichen Repository `stho32/SAUS-ES` entdeckt und per Mail-Alert gemeldet (Issue #3). Der Key wurde ursprünglich im Commit `b3639bc` (R00011, Laravel-13-Migration) als Teil der Datei `.env.dusk.local` hinzugefügt und in `92c5ec0` (R00017, Repo-Struktur) wieder entfernt — er steht also noch in der Git-History.

Zusätzlich wurde bei der Analyse festgestellt, dass derselbe Key bis heute als **hardkodierter Default-Fallback in `docker-compose.yml`** steht und damit aktiv im HEAD des Repositories liegt:

```yaml
- APP_KEY=${APP_KEY:-base64:REP7MlRSUjsyk6kNFPjcWKc5qVZEEs98TN892ZJO0tQ=}
```

Der Key war nur für die lokale Dusk-Test-Umgebung gedacht und nie auf Produktion eingesetzt. Dennoch ist ein öffentlich gelisteter Schlüssel — auch ein Dev-Key — unsauber und löst Security-Scanner aus.

## Ist-Zustand

- `docker-compose.yml:15` enthält den konkreten Base64-`APP_KEY` als Default-Wert hinter der Bash-Substitution `${APP_KEY:-…}`.
- Ohne gesetzte Umgebungsvariable verwendet der App-Container diesen hartkodierten Wert.
- Der identische Wert steht in zwei Commits der Git-History (`b3639bc`, `92c5ec0`).
- `Source/.env.example` enthält `APP_KEY=` (leer) — das ist korrekt.
- Entwickler-Workflow: lokal wird der Key mit `php artisan key:generate` in eine gitignorierte `Source/.env` geschrieben.

## Soll-Zustand

- `docker-compose.yml` enthält **keinen konkreten APP_KEY-Wert mehr**. Der Schlüssel wird entweder:
  - aus einer lokalen `.env`-Datei gelesen, die `docker-compose` ohnehin automatisch lädt (Root-Level `.env`, nicht `Source/.env`), oder
  - vor dem ersten Start via `php artisan key:generate` erzeugt und in die lokale `Source/.env` geschrieben, die der Container via Volume sieht.
- Bei fehlender Variable wird der Container-Start klar fehlschlagen (statt heimlich einen weltweit bekannten Key zu verwenden).
- README (oder CLAUDE.md) dokumentiert den Schritt "Key erzeugen" explizit, sodass neue Entwickler ihn nicht übersehen.
- Im Issue-Kommentar wird festgehalten: Der geleakte Key war ein lokaler Dusk-Test-Key, ist rotiert und nicht mehr aktiv.

### Explizit nicht im Scope

- **Git-History-Rewrite** (`git filter-repo` / BFG) wird nicht durchgeführt. Der Schlüssel war kein Produktions-Secret, und Force-Push hätte hohe Kollateralkosten (Hash-Änderung, Clones invalidieren, externe Verweise brechen).
- Rotation des Produktions-APP_KEY ist nicht betroffen (der Produktions-Server nutzt einen eigenständigen Key, der nie im Repo war).

## Akzeptanzkriterien

### Konfiguration
- [ ] `docker-compose.yml` enthält den Base64-String `REP7MlRSUjsyk6kNFPjcWKc5qVZEEs98TN892ZJO0tQ=` an keiner Stelle mehr
- [ ] `docker-compose.yml` bezieht `APP_KEY` aus der Umgebung / `.env`-Datei, ohne hardkodierten Fallback; fehlende Variable lässt Container sichtbar fehlschlagen
- [ ] DB-Credentials (`saus_user`/`saus_password`) bleiben als Dev-Defaults im docker-compose — sie sind keine Secrets im eigentlichen Sinn (reiner Dev-Stack) und liegen außerhalb des GitGuardian-Alerts
- [ ] `.env.example` am Repo-Root (neu, falls nicht vorhanden) dokumentiert die nötigen Variablen und dient als Vorlage
- [ ] `.gitignore` sichert, dass Root-`.env` nicht eingecheckt werden kann

### Tests
- [ ] Vorhandener Test stellt sicher, dass der konkrete geleakte Key-String in keiner getrackten Datei des HEAD mehr vorkommt (regressionssicher gegen versehentliches Zurückfügen)
- [ ] Alle 169 Pest-Tests bestehen weiterhin
- [ ] `docker-compose up -d` startet mit einer lokal erzeugten `.env` (beide Container healthy)

### Dokumentation
- [ ] `README.md` und/oder `CLAUDE.md` beschreiben klar: Vor dem ersten `docker-compose up` muss `.env` aus `.env.example` erzeugt und `APP_KEY` gesetzt werden
- [ ] Issue #3 wird mit dem Behebungs-Commit und einem Hinweis zum Rotations-Status kommentiert und anschließend geschlossen

## Technische Details

### Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `docker-compose.yml` | Hardkodierten `APP_KEY`-Fallback entfernen (Fail-Fast via `${APP_KEY:?…}`); DB-Credentials bleiben als Dev-Defaults |
| `.env.example` (neu, Root-Level) | Vorlage für die vom docker-compose erwarteten Variablen |
| `.gitignore` | Absichern, dass `/.env` (Root-Level) nicht eingecheckt werden kann |
| `README.md` | Setup-Schritte um "Key erzeugen" ergänzen |
| `Source/tests/Feature/SecurityConfigTest.php` (neu) | Regressionstest gegen den konkreten Leak-String |

### Teststrategie (Test-First)

1. **Regressionstest zuerst** — ein Pest-Test in `Source/tests/Feature/` scannt die getrackten Dateien des Repos und schlägt fehl, solange der String `REP7MlRSUjsyk6kNFPjcWKc5qVZEEs98TN892ZJO0tQ` in einer getrackten Datei steht. Test läuft vor dem Fix rot, nach dem Fix grün.
2. **Start-Test manuell** — nach der Änderung muss `docker-compose down && docker-compose up -d` mit einer lokalen `.env` fehlerfrei durchlaufen, beide Container werden healthy.
3. **Volle Pest-Suite** muss weiter grün bleiben (169 Tests).

## Abhängigkeiten

- Abhängig von: Keine
- Blockiert: Keine

## Notizen

- Der geleakte Key ist nur in öffentlich einsehbarer Dev-/Test-Konfiguration aufgetaucht und war nie Teil des Produktivsystems.
- GitGuardian-Alert könnte mit den History-Commits weiter bestehen — die Aktion "Mark as revoked" im GitGuardian-Dashboard schließt den Alert sauber ab. Dies ist eine manuelle Dashboard-Aktion und nicht Teil dieser Anforderung.
