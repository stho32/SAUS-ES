# REQ0004: Bilder als Thumbnails

- Basiert auf ai-documents\REQ0002-Wiedervorlagedatum.md.
- Beachte ai-documents\Structure.md

## Anforderungen

- Ich möchte bitte, dass die Bilder in der ticket_view.php zunächst (vor dem Klick auf das Bild) als Thumbnails angezeigt werden.
- Die Thumbnails sollen auf 200 Pixel Breite proportional reduziert werden.
- Die Erstellung der Thumbnails soll dynamisch bei Seitenaufruf durchgeführt werden. D.h. es soll ein Proxy-PHP-Skript geben, dass die Konvertierung durchführt.
- Füge dazu get-attachments.php einen weiteren Parameter "asThumbnail" hinzu
- Wenn asThumbnail angegeben ist und es sich um ein Bild handelt, dann schrumpfe auf besonders kompatible Weise die Bilder zusammen.