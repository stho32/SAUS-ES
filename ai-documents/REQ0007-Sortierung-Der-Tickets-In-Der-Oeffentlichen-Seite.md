# REQ0007: Sortierung und Filterung der Tickets in der öffentlichen Übersicht

- Beachte ai-documents\Structure.md
- Beachte die Datenbankstruktur im .\mysql-Verzeichnis (Migrations)

## Anforderungen

- Bitte sortiere die Tickets in der öffentlichen Übersicht (public_php_app\index.php) nach "Letzter Aktivität" per default.
- Füge ein Dropdown ein, mit dem man die Sortierung wechseln zwischen dieser und anderen üblichen Sortierungen wechseln kann.
- Füge eine einfache Volltext-Suchmaske ein, mit der man ausschließlich in den öffentlich sichtbaren Feldern suchen kann.
- Tickets, bei denen mehr als 3 Monate keine Aktivität verzeichnet wird, werden ausgeblendet.
- Es gibt einen Filter, bei denen man Tickets, die mehr als 3 Monate nicht aktiv sind, einblenden kann.
- Wenn du sortierst oder filterst, sorge dafür, dass die Anzeige so gescrollt wird, dass der Filter dann oben ist. (Die Seite wird in einem Frame angezeigt und der Filter würde sonst nach unten rutschen unter die Hinweise...)