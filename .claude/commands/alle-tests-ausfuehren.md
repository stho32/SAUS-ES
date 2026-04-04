# Alle Tests ausfuehren

Fuehrt saemtliche Tests (Unit, Feature, Integration, E2E) aus, erfasst Coverage, identifiziert Null-Tests und erstellt einen ausfuehrlichen Problembericht.

## Arguments

Keine Argumente erforderlich.

## Purpose

Dieser Command fuehrt eine vollstaendige Qualitaetspruefung der Anwendung durch. Er startet alle Testsuiten (Unit, Feature, Integration, Dusk E2E), misst die Code-Coverage, analysiert bestehende Tests auf inhaltliche Qualitaet (Null-Tests ohne echte Assertions) und erstellt einen strukturierten Abschlussbericht. Ziel ist es, den Gesamtzustand der Anwendung festzustellen und alle Probleme so aufzubereiten, dass sie sofort repariert werden koennen.

## Plan

### Phase 1: Discovery — Testlandschaft erfassen

1. Lies die `CLAUDE.md` im Repository-Root, um Testbefehle, Testverzeichnisse und Konventionen zu verstehen.
2. Identifiziere alle Testverzeichnisse und -dateien:
   - `tests/Unit/` — Unit-Tests
   - `tests/Feature/` — Feature/Integration-Tests
   - `tests/Browser/` — Dusk E2E-Tests
   - Suche nach weiteren Testdateien ausserhalb der Standardverzeichnisse
3. Zaehle die Testdateien und Tests pro Kategorie fuer den spaeteren Bericht.
4. Pruefe, ob die Entwicklungsumgebung laeuft (Docker-Container, Datenbank).
5. Stelle sicher, dass die Datenbank in einem sauberen Zustand ist (`migrate:fresh --seed`).

### Phase 2: Null-Test-Analyse — Tests auf Qualitaet pruefen

1. Lese alle Testdateien und analysiere sie auf folgende Muster:
   - **Null-Tests**: Tests ohne echte Assertions (`expect()`, `assert*`, `->assertStatus()`, etc.)
   - **Triviale Tests**: Tests die nur `assertTrue(true)` oder aehnliches pruefen
   - **Fehlende Zustandspruefung**: Tests die eine Aktion ausfuehren, aber weder Vor- noch Endzustand pruefen
   - **Auskommentierte Tests**: Tests die auskommentiert oder mit `skip()`/`markTestSkipped()` deaktiviert sind
   - **Leere Testmethoden**: Testmethoden ohne Inhalt
2. Erstelle eine Liste aller identifizierten Null-Tests mit:
   - Dateiname und Zeilennummer
   - Testname
   - Art des Problems (kein Assert, trivial, auskommentiert, etc.)
   - Vorschlag zur Behebung

### Phase 3: Unit- und Feature-Tests ausfuehren

1. Fuehre alle Pest-Tests mit Coverage aus:
   ```bash
   cd Source
   php artisan test --coverage 2>&1
   ```
   Falls `--coverage` nicht verfuegbar ist (kein Xdebug/PCOV), fuehre ohne Coverage aus:
   ```bash
   php artisan test 2>&1
   ```
2. Erfasse fuer jeden fehlgeschlagenen Test:
   - Testdatei und Testname
   - Fehlermeldung und Stack-Trace
   - Moegliche Ursache (wenn erkennbar)
3. Notiere die Gesamtanzahl: bestanden, fehlgeschlagen, uebersprungen, Laufzeit.

### Phase 4: E2E-Tests (Dusk) ausfuehren

1. Pruefe ob ChromeDriver und Dusk korrekt konfiguriert sind.
2. Fuehre die Dusk-Tests aus:
   ```bash
   cd Source
   php artisan dusk 2>&1
   ```
3. Bei Fehlern: Pruefe auf Screenshots in `tests/Browser/screenshots/` und Console-Logs in `tests/Browser/console/`.
4. Erfasse fuer jeden fehlgeschlagenen Dusk-Test:
   - Testdatei und Testname
   - Fehlermeldung (Element nicht gefunden, Timeout, etc.)
   - Screenshot-Referenz falls vorhanden
   - Moegliche Ursache

### Phase 5: Coverage-Analyse

1. Falls Coverage-Daten verfuegbar:
   - Gesamte Line-Coverage in Prozent
   - Coverage pro Verzeichnis/Namespace (Controllers, Models, Services, Middleware)
   - Dateien mit 0% Coverage identifizieren
   - Dateien mit niedriger Coverage (<50%) identifizieren
2. Falls keine Coverage moeglich: Dokumentiere dies und schlage vor, wie Coverage aktiviert werden kann (PCOV/Xdebug Installation).

### Phase 6: Abschlussbericht erstellen

Erstelle einen strukturierten Bericht mit folgenden Abschnitten:

```
## Testergebnis-Bericht

### Zusammenfassung
- Gesamtzahl Tests: X (Unit: X, Feature: X, E2E: X)
- Bestanden: X | Fehlgeschlagen: X | Uebersprungen: X
- Null-Tests gefunden: X
- Code-Coverage: X%

### Fehlgeschlagene Tests
[Tabelle mit: Kategorie | Datei:Zeile | Testname | Fehlermeldung | Ursache]

### Null-Tests (Tests ohne echte Pruefung)
[Tabelle mit: Datei:Zeile | Testname | Problem | Vorschlag]

### Coverage-Uebersicht
[Tabelle mit: Bereich | Coverage% | Bemerkung]

### Handlungsempfehlungen
[Priorisierte Liste der zu behebenden Probleme]
```

## Adaptive Execution

Wenn du diesen Command ausfuehrst:

1. **Umgebung pruefen**: Stelle sicher, dass Docker-Container laufen und die DB erreichbar ist. Wenn nicht, starte die Umgebung zuerst.

2. **Coverage-Tools erkennen**: Pruefe ob Xdebug oder PCOV im Container/lokal verfuegbar ist. Passe den Test-Befehl entsprechend an. Wenn weder noch verfuegbar, ueberspringe Coverage und dokumentiere es.

3. **Dusk-Voraussetzungen**: Dusk-Tests benoetigen einen laufenden Browser/ChromeDriver. Wenn Dusk nicht ausfuehrbar ist (z.B. kein Display in Docker), dokumentiere dies statt zu scheitern.

4. **Reihenfolge einhalten**: Fuehre die Null-Test-Analyse VOR den Tests aus, damit der Bericht vollstaendig ist auch wenn Tests abbrechen.

5. **Fehler nicht reparieren**: Dieser Command repariert nichts — er diagnostiziert nur. Der Bericht soll so detailliert sein, dass Reparaturen im Anschluss effizient durchgefuehrt werden koennen.

6. **DB-Zustand**: Zwischen Pest-Tests und Dusk-Tests die DB ggf. neu seeden, da Tests den Zustand veraendern koennen.

7. **Timeout beachten**: Dusk-Tests koennen lange dauern. Setze angemessene Timeouts und berichte ueber haengende Tests.

8. **Bericht direkt ausgeben**: Den Bericht als formatierte Markdown-Ausgabe direkt an den User ausgeben, keine Datei erstellen.
