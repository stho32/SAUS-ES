-- Füge Spalte für Hintergrundfarbe zur ticket_status Tabelle hinzu
ALTER TABLE ticket_status 
ADD COLUMN background_color VARCHAR(20) NOT NULL DEFAULT '#6c757d';

-- Aktualisiere bestehende Status mit Pastellfarben
UPDATE ticket_status SET background_color = '#90EE90' WHERE name = 'offen';           -- Hellgrün
UPDATE ticket_status SET background_color = '#ADD8E6' WHERE name = 'in_diskussion';   -- Hellblau
UPDATE ticket_status SET background_color = '#FFE4B5' WHERE name = 'abstimmung';      -- Helles Orange
UPDATE ticket_status SET background_color = '#E6E6FA' WHERE name = 'geschlossen';     -- Helles Violett
UPDATE ticket_status SET background_color = '#D3D3D3' WHERE name = 'archiviert';      -- Hellgrau
