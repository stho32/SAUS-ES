-- Füge Felder für Kommentar-Bearbeitung hinzu
ALTER TABLE comments 
ADD COLUMN updated_at DATETIME NULL,
ADD COLUMN is_edited BOOLEAN NOT NULL DEFAULT FALSE;
