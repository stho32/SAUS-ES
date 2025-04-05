-- Hinzufügen der Wiedervorlagedatum-Spalte zur Tickets-Tabelle
ALTER TABLE tickets ADD COLUMN follow_up_date DATE DEFAULT NULL COMMENT 'Wiedervorlagedatum für das Ticket';

-- Hinzufügen eines Flags, um Tickets von der Verfolgung auszuschließen
ALTER TABLE tickets ADD COLUMN do_not_track TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag, um Tickets von der Verfolgung auszuschließen';

-- Index für effiziente Abfragen nach Wiedervorlagedatum
CREATE INDEX idx_tickets_follow_up_date ON tickets(follow_up_date);

-- Index für effiziente Abfragen nach dem Verfolgungsflag
CREATE INDEX idx_tickets_do_not_track ON tickets(do_not_track);
