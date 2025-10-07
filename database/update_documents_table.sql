-- Add missing columns to application_documents table
ALTER TABLE application_documents
ADD COLUMN IF NOT EXISTS original_filename VARCHAR(255) NOT NULL DEFAULT '',
ADD COLUMN IF NOT EXISTS file_size BIGINT NOT NULL DEFAULT 0; 