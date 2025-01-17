-- Füge Bearbeiter-Spalte und Zeitstempel zur tickets Tabelle hinzu
ALTER TABLE tickets
ADD COLUMN assignee VARCHAR(200) DEFAULT NULL COMMENT 'Zuständige Bearbeiter (Freitext)',
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Letzte Aktualisierung';

-- Aktualisiere die Bearbeiter-Spalte für bestehende Tickets
UPDATE tickets SET assignee = '' WHERE assignee IS NULL;

-- Setze initial updated_at auf created_at für bestehende Tickets
UPDATE tickets SET updated_at = created_at WHERE updated_at IS NULL;
