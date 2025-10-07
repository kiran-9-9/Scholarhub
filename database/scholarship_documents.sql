-- Create table for scholarships
CREATE TABLE IF NOT EXISTS scholarships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_name VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    deadline DATE NOT NULL,
    requirements TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for scholarship applications
CREATE TABLE IF NOT EXISTS scholarship_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    scholarship_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    additional_info TEXT,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (scholarship_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create table for scholarship document requirements
CREATE TABLE IF NOT EXISTS scholarship_document_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    description TEXT,
    is_required TINYINT(1) DEFAULT 1,
    max_size_mb INT DEFAULT 5,
    allowed_types VARCHAR(255) DEFAULT 'pdf,doc,docx,jpg,jpeg,png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    INDEX idx_scholarship_doc (scholarship_id, document_type)
);

-- Create application documents table
CREATE TABLE IF NOT EXISTS application_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_requirement_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (document_requirement_id) REFERENCES scholarship_document_requirements(id),
    INDEX idx_application_doc (application_id, document_requirement_id)
);

-- Document types enum for reference
-- JN Tata Endowment Scholarship
-- pan_card
-- aadhar_card
-- passport
-- marksheets
-- transcripts
-- test_scores
-- sop
-- cv
-- lor
-- cost_estimate
-- funds_declaration
-- bank_statement
-- work_certificate
-- appointment_letter
-- itr
-- guarantor_pan
-- guarantor_income
-- guarantor_photo
-- achievements
-- cost_breakdown

-- National Scholarship Scheme
-- aadhar_card
-- voter_id
-- bank_passbook
-- domicile_certificate
-- caste_certificate
-- income_certificate
-- marksheet_10
-- marksheet_12
-- admission_letter
-- photo
-- additional_docs 