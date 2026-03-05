-- Run this SQL to add missing columns (fix "Unknown column avis_count" error)
-- Execute with: mysql -u USER -p DATABASE < migrations/fix_avis_columns.sql
-- Or run each statement in your DB client (phpMyAdmin, TablePlus, etc.)
-- If a column already exists, you'll get a duplicate error for that line; skip it.

-- 1. Add avis_count to post table
ALTER TABLE post ADD COLUMN avis_count INT DEFAULT 0 NOT NULL;

-- 2. Add avis_count and parent_id to comment table
ALTER TABLE comment ADD COLUMN avis_count INT DEFAULT 0 NOT NULL;
ALTER TABLE comment ADD COLUMN parent_id INT DEFAULT NULL;

-- 3. Add foreign key and index for comment replies (omit if you get "already exists" errors)
ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE CASCADE;
CREATE INDEX IDX_9474526C727ACA70 ON comment (parent_id);

-- 4. Add dislike_count for like/dislike feature
ALTER TABLE post ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL;
ALTER TABLE comment ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL;
