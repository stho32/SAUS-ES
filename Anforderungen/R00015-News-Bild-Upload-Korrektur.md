---
id: R00015
titel: "News-Bild-Upload und Thumbnail-Generierung korrigieren"
typ: Bug
status: Offen
erstellt: 2026-04-03
---

# R00015: News-Bild-Upload und Thumbnail-Generierung korrigieren

## Zusammenfassung
Die alte Anwendung speichert News-Bilder in Unterverzeichnissen pro News-ID (z.B. `uploads/news/1/`) und generiert Thumbnails mit `thumb_`-Praefix. Die Laravel-Portierung muss dieses Verhalten exakt abbilden fuer Kompatibilitaet mit den bestehenden Bilddaten.

## Ist-Zustand (aus Produktiv-Dateien)
```
uploads/news/
├── 1/
│   ├── 686591c1283d6739067ba92fefce22a0.png
│   ├── 77d65ec67ad60bc5fd15f5390921be29.jpg
│   └── thumb_77d65ec67ad60bc5fd15f5390921be29.jpg
├── 2/
│   └── ...
```

## Anforderungen
- Bilder werden in `uploads/news/{newsId}/` gespeichert (nicht flach)
- Thumbnail wird mit `thumb_`-Praefix im gleichen Verzeichnis generiert
- Bestehende Bilder aus der Produktion muessen weiterhin korrekt angezeigt werden
- GD-Library fuer Thumbnail-Generierung (200px Breite, proportional)

## Akzeptanzkriterien
- [ ] Upload speichert in korrektem Unterverzeichnis
- [ ] Thumbnail mit thumb_-Praefix wird generiert
- [ ] Bestehende Produktionsbilder werden korrekt angezeigt
- [ ] Feature-Test fuer Bild-Upload mit Thumbnail
