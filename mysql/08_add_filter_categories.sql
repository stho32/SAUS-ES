-- Füge Spalte für Filterkategorie zur ticket_status Tabelle hinzu
ALTER TABLE ticket_status 
ADD COLUMN filter_category VARCHAR(20) NOT NULL DEFAULT 'in_bearbeitung';

-- Setze die Filterkategorien für die Status
-- "In Bearbeitung" umfasst die meisten aktiven Status
UPDATE ticket_status SET filter_category = 'in_bearbeitung' 
WHERE name IN ('offen', 'in_bearbeitung', 'zur_ueberpruefung', 'warten_auf_feedback', 'wartet_auf_1892', 'verschoben');

-- "Zurückgestellt" ist eine eigene isolierte Gruppe
UPDATE ticket_status SET filter_category = 'zurueckgestellt' 
WHERE name = 'zurueckgestellt';

-- "Geschlossen/Erledigt" für abgeschlossene Tickets
UPDATE ticket_status SET filter_category = 'geschlossen' 
WHERE name IN ('gescheitert', 'abgelehnt');

-- "Archiviert" für archivierte Tickets
UPDATE ticket_status SET filter_category = 'archiviert' 
WHERE name = 'archiviert';
