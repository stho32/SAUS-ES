-- =============================================================================
-- SAUS-ES Combined Database Schema
-- Generated from mysql/01_tables.sql through mysql/17_add_news.sql
-- This file is idempotent - safe to run multiple times
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 01_tables.sql: Core tables
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ticket_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    sort_order INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_archived BOOLEAN NOT NULL DEFAULT FALSE,
    is_closed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    ki_summary TEXT,
    ki_interim TEXT,
    status_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    FOREIGN KEY (status_id) REFERENCES ticket_status(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    value ENUM('up', 'down') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id),
    UNIQUE KEY unique_comment_vote (comment_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS master_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_code VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NULL,
    partner_name VARCHAR(50) NULL,
    partner_link VARCHAR(255) NOT NULL UNIQUE,
    partner_list TEXT NULL,
    is_master BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View for comment statistics
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

-- Functions for partner lists and vote evaluation
DELIMITER //

CREATE FUNCTION IF NOT EXISTS get_ticket_partners(p_ticket_id INT)
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

CREATE FUNCTION IF NOT EXISTS has_sufficient_positive_votes(p_ticket_id INT, p_min_votes INT)
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

-- -----------------------------------------------------------------------------
-- 02_initial_data.sql: Initial data
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO ticket_status (name, description, sort_order, is_active) VALUES
('offen', 'Ticket wurde erstellt und wartet auf Diskussion', 10, TRUE),
('in_diskussion', 'Ticket wird aktiv diskutiert', 20, TRUE),
('abstimmung', 'Ticket ist in der Abstimmungsphase', 30, TRUE),
('geschlossen', 'Ticket wurde abgeschlossen', 40, TRUE),
('archiviert', 'Ticket wurde archiviert', 50, TRUE);

INSERT IGNORE INTO master_links (link_code, description, is_active) VALUES
('initial_master_2024', 'Initialer Master-Link fuer das System', TRUE);

-- -----------------------------------------------------------------------------
-- 03_update_status.sql: Add is_archived and is_closed columns
-- (Already in CREATE TABLE above, so only run updates)
-- -----------------------------------------------------------------------------

UPDATE ticket_status SET is_closed = TRUE WHERE name IN ('geschlossen', 'abgeschlossen', 'erledigt');
UPDATE ticket_status SET is_archived = TRUE WHERE name IN ('archiviert');

-- -----------------------------------------------------------------------------
-- 04_add_comment_visibility.sql: Comment visibility fields
-- -----------------------------------------------------------------------------

-- Add columns if they don't exist (using ALTER IGNORE pattern)
SET @dbname = DATABASE();

-- is_visible column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'is_visible');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE comments ADD COLUMN is_visible BOOLEAN NOT NULL DEFAULT TRUE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- hidden_by column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'hidden_by');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE comments ADD COLUMN hidden_by VARCHAR(50) NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- hidden_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'hidden_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE comments ADD COLUMN hidden_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 05_add_comment_edit.sql: Comment edit tracking
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE comments ADD COLUMN updated_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'is_edited');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE comments ADD COLUMN is_edited BOOLEAN NOT NULL DEFAULT FALSE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 06_add_status_color.sql: Status background color
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ticket_status' AND COLUMN_NAME = 'background_color');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE ticket_status ADD COLUMN background_color VARCHAR(20) NOT NULL DEFAULT ''#6c757d''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE ticket_status SET background_color = '#90EE90' WHERE name = 'offen' AND background_color = '#6c757d';
UPDATE ticket_status SET background_color = '#ADD8E6' WHERE name = 'in_diskussion' AND background_color = '#6c757d';
UPDATE ticket_status SET background_color = '#FFE4B5' WHERE name = 'abstimmung' AND background_color = '#6c757d';
UPDATE ticket_status SET background_color = '#E6E6FA' WHERE name = 'geschlossen' AND background_color = '#6c757d';
UPDATE ticket_status SET background_color = '#D3D3D3' WHERE name = 'archiviert' AND background_color = '#6c757d';

-- -----------------------------------------------------------------------------
-- 07_add_additional_status.sql: Additional status entries
-- -----------------------------------------------------------------------------

UPDATE ticket_status SET is_active = FALSE WHERE name != 'offen' AND name NOT IN ('in_bearbeitung', 'zur_ueberpruefung', 'warten_auf_feedback', 'wartet_auf_1892', 'zurueckgestellt', 'verschoben', 'gescheitert', 'abgelehnt');

INSERT IGNORE INTO ticket_status (name, description, sort_order, is_active, background_color) VALUES
('in_bearbeitung', 'Ticket wird aktiv bearbeitet', 20, TRUE, '#FFFFE0'),
('zur_ueberpruefung', 'Ticket ist abgeschlossen, wartet auf Pruefung/Abnahme', 30, TRUE, '#FFD700'),
('warten_auf_feedback', 'Ticket wartet auf Rueckmeldung von anderen Personen', 40, TRUE, '#87CEEB'),
('wartet_auf_1892', 'Ticket wartet auf die Fertigstellung von Ticket 1892', 45, TRUE, '#DDA0DD'),
('zurueckgestellt', 'Ticket ist aktuell pausiert oder nicht priorisiert', 50, TRUE, '#F5DEB3'),
('verschoben', 'Ticket wurde auf einen spaeteren Zeitpunkt verschoben', 60, TRUE, '#FFEFD5'),
('gescheitert', 'Ticket wurde abgebrochen oder wird nicht weiterverfolgt', 70, TRUE, '#FFB6C1'),
('abgelehnt', 'Ticket wurde abgelehnt und wird nicht umgesetzt', 80, TRUE, '#FA8072'),
('archiviert', 'Ticket wurde archiviert', 90, TRUE, '#D3D3D3');

UPDATE ticket_status SET is_closed = TRUE WHERE name IN ('gescheitert', 'abgelehnt', 'archiviert');
UPDATE ticket_status SET is_archived = TRUE WHERE name = 'archiviert';

-- -----------------------------------------------------------------------------
-- 08_add_filter_categories.sql: Filter categories for status
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'ticket_status' AND COLUMN_NAME = 'filter_category');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE ticket_status ADD COLUMN filter_category VARCHAR(20) NOT NULL DEFAULT ''in_bearbeitung''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE ticket_status SET filter_category = 'in_bearbeitung'
WHERE name IN ('offen', 'in_bearbeitung', 'zur_ueberpruefung', 'warten_auf_feedback', 'wartet_auf_1892', 'verschoben');

UPDATE ticket_status SET filter_category = 'zurueckgestellt'
WHERE name = 'zurueckgestellt';

UPDATE ticket_status SET filter_category = 'geschlossen'
WHERE name IN ('gescheitert', 'abgelehnt');

UPDATE ticket_status SET filter_category = 'archiviert'
WHERE name = 'archiviert';

-- -----------------------------------------------------------------------------
-- 09_add_assignee.sql: Assignee and updated_at for tickets
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'assignee');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN assignee VARCHAR(200) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE tickets SET assignee = '' WHERE assignee IS NULL;
UPDATE tickets SET updated_at = created_at WHERE updated_at IS NULL;

-- -----------------------------------------------------------------------------
-- 10_add_ticket_votes.sql: Ticket voting
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ticket_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    value ENUM('up', 'down') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    UNIQUE KEY unique_ticket_vote (ticket_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW ticket_statistics AS
SELECT
    t.id as ticket_id,
    COUNT(CASE WHEN tv.value = 'up' THEN 1 END) as up_votes,
    COUNT(CASE WHEN tv.value = 'down' THEN 1 END) as down_votes,
    COUNT(tv.id) as total_votes
FROM tickets t
LEFT JOIN ticket_votes tv ON t.id = tv.ticket_id
GROUP BY t.id;

-- -----------------------------------------------------------------------------
-- 11_add_website_fields.sql: Website visibility
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'show_on_website');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN show_on_website BOOLEAN NOT NULL DEFAULT FALSE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'public_comment');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN public_comment TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 12_add_affected_neighbors.sql: Affected neighbors count
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'affected_neighbors');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN affected_neighbors INT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 13_add_ticket_attachments.sql: File attachments
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    upload_date DATETIME NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14_add_secret_string.sql: Secret string for public image access
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'secret_string');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN secret_string VARCHAR(50) NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Function to generate a random string
DELIMITER //

CREATE FUNCTION IF NOT EXISTS generate_random_string()
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(50) DEFAULT '';
    DECLARE chars VARCHAR(62) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    DECLARE i INT DEFAULT 1;

    WHILE i <= 50 DO
        SET result = CONCAT(result, SUBSTRING(chars, FLOOR(1 + RAND() * 62), 1));
        SET i = i + 1;
    END WHILE;

    RETURN result;
END//

DELIMITER ;

-- Add secret string for existing tickets
UPDATE tickets SET secret_string = generate_random_string() WHERE secret_string IS NULL;

-- Trigger for new tickets (use DROP IF EXISTS pattern for idempotency)
DROP TRIGGER IF EXISTS tickets_before_insert;

DELIMITER //
CREATE TRIGGER tickets_before_insert
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
    IF NEW.secret_string IS NULL THEN
        SET NEW.secret_string = generate_random_string();
    END IF;
END//
DELIMITER ;

-- -----------------------------------------------------------------------------
-- 15_add_follow_up_date.sql: Follow-up date and tracking flag
-- -----------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'follow_up_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN follow_up_date DATE DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'tickets' AND COLUMN_NAME = 'do_not_track');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE tickets ADD COLUMN do_not_track TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 16_add_contact_persons.sql: Contact persons
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_persons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `contact_notes` TEXT NULL,
  `responsibility_notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_contact_persons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT NOT NULL,
  `contact_person_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_contact_unique` (`ticket_id`, `contact_person_id`),
  CONSTRAINT `fk_ticket_contact_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_contact_person` FOREIGN KEY (`contact_person_id`) REFERENCES `contact_persons` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 17_add_news.sql: News management
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `image_filename` VARCHAR(255) NULL,
  `event_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(50) NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
