-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- Update application_documents to reference the applications table
ALTER TABLE application_documents
DROP FOREIGN KEY application_documents_ibfk_1;

-- Migrate data from scholarship_applications to applications if not already there
INSERT IGNORE INTO applications (
    id, user_id, scholarship_id, status, additional_info, 
    application_date, updated_at
)
SELECT 
    id, user_id, scholarship_id, status, additional_info,
    application_date, updated_at
FROM scholarship_applications;

-- Drop the old table
DROP TABLE scholarship_applications;

-- Add foreign key constraint to application_documents
ALTER TABLE application_documents
ADD CONSTRAINT application_documents_ibfk_1 
FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE;

-- Update applications table structure
ALTER TABLE applications
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'waitlisted') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS review_notes TEXT,
ADD COLUMN IF NOT EXISTS reviewed_by INT,
ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS documents_submitted JSON,
ADD FOREIGN KEY IF NOT EXISTS (reviewed_by) REFERENCES admin(id);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1; 