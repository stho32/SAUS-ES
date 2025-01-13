-- Erstelle ticket_status Tabelle
CREATE TABLE ticket_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erstelle tickets Tabelle
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    ki_summary TEXT,
    ki_interim TEXT,
    status_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    FOREIGN KEY (status_id) REFERENCES ticket_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erstelle comments Tabelle
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erstelle comment_votes Tabelle
CREATE TABLE comment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    value ENUM('up', 'down') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id),
    UNIQUE KEY unique_comment_vote (comment_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erstelle master_links Tabelle
CREATE TABLE master_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_code VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erstelle partners Tabelle
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NULL,
    partner_name VARCHAR(50) NULL,
    partner_link VARCHAR(255) NOT NULL UNIQUE,
    is_master BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View für Kommentar-Statistiken
CREATE OR REPLACE VIEW comment_statistics AS
SELECT 
    c.id as comment_id,
    c.ticket_id,
    COUNT(CASE WHEN cv.value = 'up' THEN 1 END) as up_votes,
    COUNT(CASE WHEN cv.value = 'down' THEN 1 END) as down_votes,
    COUNT(cv.id) as total_votes
FROM comments c
LEFT JOIN comment_votes cv ON c.id = cv.comment_id
GROUP BY c.id, c.ticket_id;

-- Funktionen für Partner-Listen und Abstimmungsauswertung
DELIMITER //

CREATE OR REPLACE FUNCTION get_ticket_partners(p_ticket_id INT) 
RETURNS TEXT
DETERMINISTIC
BEGIN
    DECLARE partner_list TEXT;
    
    SELECT GROUP_CONCAT(DISTINCT partner_name ORDER BY created_at SEPARATOR ', ')
    INTO partner_list
    FROM partners
    WHERE ticket_id = p_ticket_id
    AND partner_name IS NOT NULL;
    
    RETURN COALESCE(partner_list, '');
END//

CREATE OR REPLACE FUNCTION has_sufficient_positive_votes(p_ticket_id INT, p_min_votes INT) 
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE total_positive_votes INT;
    
    SELECT COUNT(DISTINCT c.id) INTO total_positive_votes
    FROM comments c
    JOIN comment_statistics cs ON c.id = cs.comment_id
    WHERE c.ticket_id = p_ticket_id
    AND cs.up_votes > cs.down_votes;
    
    RETURN total_positive_votes >= p_min_votes;
END//

DELIMITER ;
