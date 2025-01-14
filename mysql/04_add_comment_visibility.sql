-- Füge Felder für Sichtbarkeit zu comments hinzu
ALTER TABLE comments 
ADD COLUMN is_visible BOOLEAN NOT NULL DEFAULT TRUE,
ADD COLUMN hidden_by VARCHAR(50) NULL,
ADD COLUMN hidden_at DATETIME NULL;

-- Index für schnellere Abfragen
CREATE INDEX idx_comments_visibility ON comments(is_visible);
