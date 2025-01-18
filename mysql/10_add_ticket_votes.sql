-- Erstelle ticket_votes Tabelle
CREATE TABLE ticket_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    value ENUM('up', 'down') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    UNIQUE KEY unique_ticket_vote (ticket_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View für Ticket-Statistiken
CREATE OR REPLACE VIEW ticket_statistics AS
SELECT 
    t.id as ticket_id,
    COUNT(CASE WHEN tv.value = 'up' THEN 1 END) as up_votes,
    COUNT(CASE WHEN tv.value = 'down' THEN 1 END) as down_votes,
    COUNT(tv.id) as total_votes
FROM tickets t
LEFT JOIN ticket_votes tv ON t.id = tv.ticket_id
GROUP BY t.id;
