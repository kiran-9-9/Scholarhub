CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(255) PRIMARY KEY,
    `value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings if they don't exist
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('site_name', 'ScholarHub'),
('site_email', 'admin@scholarhub.com'),
('maintenance_mode', '0'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('application_deadline_reminder', '7'),
('max_scholarship_applications', '5'),
('enable_email_notifications', '1'),
('default_pagination_limit', '10'),
('file_upload_max_size', '5'),
('allowed_file_types', 'pdf,doc,docx'),
('system_timezone', 'UTC'),
('scholarship_categories', 'Academic,Sports,Arts,Need-based,Merit-based,International'); 