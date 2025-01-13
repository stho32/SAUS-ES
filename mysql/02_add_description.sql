-- Füge description-Feld zur tickets-Tabelle hinzu
ALTER TABLE tickets ADD COLUMN description TEXT NOT NULL AFTER title;
