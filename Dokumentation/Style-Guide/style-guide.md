# SAUS-ES Style Guide

Abgeleitet von der Hauptseite [1892.de](https://1892.de/) und angepasst fuer die SAUS-ES Anwendung.

## Farbpalette

### Primaerfarben (von 1892.de)

| Farbe | Hex | Verwendung |
|-------|-----|------------|
| **Brand Blue** | `#0786c0` | Navigation, Buttons, Links, Akzente |
| **Brand Blue Dark** | `#0675a9` | Button-Hover, aktive Elemente |
| **Brand Blue Darker** | `#0073aa` | Focus-States, Formular-Buttons |

### Neutralfarben (von 1892.de)

| Farbe | Hex | Verwendung |
|-------|-----|------------|
| **Weiss** | `#ffffff` | Hintergrund Karten, Hauptinhalt |
| **Warmes Beige** | `#f0efe8` | Seiten-Hintergrund, Sektionswechsel (statt kaltes Grau) |
| **Helles Beige** | `#edece7` | Banner-Hintergruende, Kontaktbereich |
| **Blasses Grau** | `#e3e2dd` | Sekundaere Hintergruende, Menue geoeffnet |
| **Mittelgrau** | `#c8c8c8` | Raender, Icons |
| **Dunkelgrau** | `#3c3c3c` | **Primaerer Text** (nicht schwarz!) |
| **Sehr Dunkelgrau** | `#292b2d` | Hover-Links, starker Kontrast |

> **Wichtig:** 1892.de nutzt warme Beige/Off-White-Toene statt kaltem Grau fuer Hintergruende.
> Der Seiten-Hintergrund ist `#f0efe8` (warmes Beige), nicht `#f1f1f1` (kaltes Grau).

### Funktionale Farben

| Farbe | Hex | Verwendung |
|-------|-----|------------|
| **Erfolg** | `#16a34a` | Erfolgs-Meldungen, positive Aktionen |
| **Warnung** | `#f59e0b` | Warnungen, Wiedervorlage heute |
| **Fehler** | `#dc2626` | Fehler, ueberfaellige Wiedervorlagen |
| **Info** | `#0786c0` | Informations-Hinweise (= Brand Blue) |

### Ticket-Status-Farben (aus bestehender DB)

| Status | Hex | Anzeige |
|--------|-----|---------|
| Offen | `#90EE90` | Hellgruen |
| In Bearbeitung | `#FFFFE0` | Hellgelb |
| Zur Ueberpruefung | `#FFD700` | Gold |
| Warten auf Feedback | `#87CEEB` | Hellblau |
| Wartet auf 1892 | `#DDA0DD` | Flieder |
| Zurueckgestellt | `#F5DEB3` | Weizen |
| Verschoben | `#FFEFD5` | Pfirsich |
| Gescheitert | `#FFB6C1` | Hellrosa |
| Abgelehnt | `#FA8072` | Lachs |
| Archiviert | `#D3D3D3` | Hellgrau |

## Typografie

| Element | Schrift | Groesse | Gewicht |
|---------|---------|---------|---------|
| Ueberschrift h1 | System (sans-serif) | 1.5rem (24px) | Bold (700) |
| Ueberschrift h2 | System (sans-serif) | 1.25rem (20px) | Semibold (600) |
| Fliesstext | System (sans-serif) | 0.875rem (14px) | Normal (400) |
| Navigation | System (sans-serif) | 0.875rem (14px) | Medium (500) |
| Buttons | System (sans-serif) | 0.875rem (14px) | Medium (500) |
| Labels/Badges | System (sans-serif) | 0.75rem (12px) | Semibold (600) |

## Tailwind CSS Mapping

### Primaerfarben → Tailwind-Klassen

Statt Tailwinds Standard `indigo-600` verwenden wir einen Custom-Color via Tailwind Config:

```html
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#e6f4fb',
                    100: '#cce9f7',
                    200: '#99d3ef',
                    300: '#66bde7',
                    400: '#33a7df',
                    500: '#0786c0',  /* Brand Blue von 1892.de */
                    600: '#0675a9',
                    700: '#056492',
                    800: '#04537b',
                    900: '#034264',
                }
            }
        }
    }
}
</script>
```

### Klassen-Ersetzungstabelle

| Bisherig (indigo) | Neu (brand) |
|-------------------|-------------|
| `bg-indigo-600` | `bg-brand-500` |
| `bg-indigo-700` | `bg-brand-600` |
| `hover:bg-indigo-700` | `hover:bg-brand-600` |
| `text-indigo-600` | `text-brand-500` |
| `focus:ring-indigo-500` | `focus:ring-brand-500` |
| `border-indigo-300` | `border-brand-300` |

## Komponenten

### Navigation
- Hintergrund: `bg-brand-500` (#0786c0)
- Text: `text-white`
- Hover: `bg-brand-600`
- Aktiver Link: `bg-brand-700 rounded`

### Buttons
- Primaer: `bg-brand-500 text-white hover:bg-brand-600`
- Sekundaer: `bg-white text-gray-700 border border-gray-300 hover:bg-gray-50`
- Gefahr: `text-red-600 hover:text-red-800`

### Karten
- Hintergrund: `bg-white`
- Rahmen: `border border-gray-200`
- Schatten: `shadow-sm`
- Ecken: `rounded-lg`

### Flash-Meldungen
- Erfolg: `bg-green-500 text-white`
- Fehler: `bg-red-100 border-red-400 text-red-700`

### Tabellen
- Kopfzeile: `bg-gray-50`
- Zeilen: `bg-white` alternierend
- Hover: `hover:bg-gray-50`
- Rahmen: `divide-y divide-gray-200`

## Abstaende

| Kontext | Wert |
|---------|------|
| Seiten-Padding | `px-4 sm:px-6 lg:px-8` |
| Karten-Padding | `p-4` oder `p-6` |
| Abstand zwischen Karten | `gap-4` oder `gap-6` |
| Max-Breite Inhalt | `max-w-7xl mx-auto` |

## Responsive Breakpoints

| Breakpoint | Pixel | Verwendung |
|------------|-------|------------|
| sm | 640px | Kompakte Tabellen, 2-spaltig |
| md | 768px | Sidebar sichtbar, 2-Spalten-Layout |
| lg | 1024px | Volle Navigation, 3-Spalten-Grid |
| xl | 1280px | Max-Breite Container |

## Barrierefreiheit

- Minimum Touch-Target: 44px x 44px
- Kontrastverhaltnis Text: mindestens 4.5:1
- Focus-Indicator: `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2`
- Alle interaktiven Elemente mit `title`- oder `aria-label`-Attribut
