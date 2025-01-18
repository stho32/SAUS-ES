-- Add fields for website visibility and public comment to tickets table
ALTER TABLE tickets
ADD COLUMN show_on_website BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Controls visibility of the ticket on the website',
ADD COLUMN public_comment TEXT NULL COMMENT 'Public comment shown on the website alongside the ticket title';

-- Update existing tickets to not be shown on website by default
UPDATE tickets SET show_on_website = FALSE WHERE show_on_website IS NULL;
