-- Füge neue Spalten zur ticket_status Tabelle hinzu
ALTER TABLE ticket_status 
ADD COLUMN is_archived BOOLEAN NOT NULL DEFAULT FALSE AFTER is_active,
ADD COLUMN is_closed BOOLEAN NOT NULL DEFAULT FALSE AFTER is_archived;

-- Setze Standard-Status
UPDATE ticket_status SET is_closed = TRUE WHERE name IN ('geschlossen', 'abgeschlossen', 'erledigt');
UPDATE ticket_status SET is_archived = TRUE WHERE name IN ('archiviert');
