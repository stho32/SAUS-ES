-- Füge SecretString-Spalte zu tickets Tabelle hinzu
ALTER TABLE tickets
ADD COLUMN secret_string VARCHAR(50) NULL COMMENT 'Zufälliger 50-Zeichen-Code für öffentlichen Bildzugriff';

-- Erstelle Index für Performance
CREATE INDEX idx_tickets_secret_string ON tickets(secret_string);

-- Funktion zum Generieren eines zufälligen Strings
DELIMITER //
CREATE FUNCTION generate_random_string() 
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(50) DEFAULT '';
    DECLARE chars VARCHAR(62) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    DECLARE i INT DEFAULT 1;
    
    -- Generiere 50 zufällige Zeichen
    WHILE i <= 50 DO
        SET result = CONCAT(result, SUBSTRING(chars, FLOOR(1 + RAND() * 62), 1));
        SET i = i + 1;
    END WHILE;
    
    RETURN result;
END//
DELIMITER ;

-- Füge für alle bestehenden Tickets einen SecretString hinzu
UPDATE tickets
SET secret_string = generate_random_string()
WHERE secret_string IS NULL;

-- Erstelle einen Trigger für neue Tickets
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

-- WICHTIG: Die Funktion generate_random_string wird vom Trigger benötigt und darf NICHT gelöscht werden!
