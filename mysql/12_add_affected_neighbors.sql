-- Füge Spalte für die Anzahl betroffener Nachbarn zur tickets Tabelle hinzu
ALTER TABLE tickets
ADD COLUMN affected_neighbors INT NULL COMMENT 'Anzahl der von diesem Ticket betroffenen Nachbarn';

-- Index für Performance bei Abfragen nach betroffenen Nachbarn
CREATE INDEX idx_tickets_affected_neighbors ON tickets(affected_neighbors);
