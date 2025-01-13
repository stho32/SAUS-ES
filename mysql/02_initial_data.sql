-- Initialisiere ticket_status Tabelle mit Standardwerten
INSERT INTO ticket_status (name, description, sort_order, is_active) VALUES
('offen', 'Ticket wurde erstellt und wartet auf Diskussion', 10, TRUE),
('in_diskussion', 'Ticket wird aktiv diskutiert', 20, TRUE),
('abstimmung', 'Ticket ist in der Abstimmungsphase', 30, TRUE),
('geschlossen', 'Ticket wurde abgeschlossen', 40, TRUE),
('archiviert', 'Ticket wurde archiviert', 50, TRUE);

-- Erstelle initialen Master-Link
INSERT INTO master_links (link_code, description, is_active) VALUES
('initial_master_2024', 'Initialer Master-Link für das System', TRUE);
