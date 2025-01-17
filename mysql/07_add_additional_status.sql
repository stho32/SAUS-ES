-- Deaktiviere bestehende Status (außer 'offen')
UPDATE ticket_status SET is_active = FALSE WHERE name != 'offen';

-- Füge neue Status hinzu
INSERT INTO ticket_status (name, description, sort_order, is_active, background_color) VALUES
('in_bearbeitung', 'Ticket wird aktiv bearbeitet', 20, TRUE, '#FFFFE0'),
('zur_ueberpruefung', 'Ticket ist abgeschlossen, wartet auf Prüfung/Abnahme', 30, TRUE, '#FFD700'),
('warten_auf_feedback', 'Ticket wartet auf Rückmeldung von anderen Personen', 40, TRUE, '#87CEEB'),
('wartet_auf_1892', 'Ticket wartet auf die Fertigstellung von Ticket 1892', 45, TRUE, '#DDA0DD'),
('zurueckgestellt', 'Ticket ist aktuell pausiert oder nicht priorisiert', 50, TRUE, '#F5DEB3'),
('verschoben', 'Ticket wurde auf einen späteren Zeitpunkt verschoben', 60, TRUE, '#FFEFD5'),
('gescheitert', 'Ticket wurde abgebrochen oder wird nicht weiterverfolgt', 70, TRUE, '#FFB6C1'),
('abgelehnt', 'Ticket wurde abgelehnt und wird nicht umgesetzt', 80, TRUE, '#FA8072'),
('archiviert', 'Ticket wurde archiviert', 90, TRUE, '#D3D3D3');

-- Setze is_closed für bestimmte Status
UPDATE ticket_status SET is_closed = TRUE 
WHERE name IN ('gescheitert', 'abgelehnt', 'archiviert');

-- Setze is_archived für den archivierten Status
UPDATE ticket_status SET is_archived = TRUE 
WHERE name = 'archiviert';
