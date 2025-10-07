-- Add new columns to scholarships table
ALTER TABLE scholarships
ADD COLUMN scholarship_type VARCHAR(50) NOT NULL DEFAULT 'general' AFTER scholarship_name,
ADD COLUMN additional_requirements JSON NULL AFTER requirements,
ADD COLUMN eligibility_criteria JSON NULL AFTER additional_requirements;

-- Add validation_rules column to scholarship_document_requirements
ALTER TABLE scholarship_document_requirements
ADD COLUMN validation_rules JSON NULL AFTER description;

-- Create index on scholarship_type
CREATE INDEX idx_scholarship_type ON scholarships(scholarship_type);

-- Update existing scholarships to have proper type
UPDATE scholarships SET scholarship_type = 'general' WHERE scholarship_type = 'general';

-- Sample data for national scholarship
INSERT INTO scholarships (
    scholarship_name,
    scholarship_type,
    description,
    amount,
    deadline,
    requirements,
    additional_requirements,
    eligibility_criteria,
    status
) VALUES (
    'National Merit Scholarship',
    'national',
    'National level scholarship for meritorious students.',
    100000.00,
    '2024-03-19',
    'General requirements for national scholarship',
    '[
        {
            "id": "annual_income",
            "type": "text",
            "label": "Annual Family Income",
            "required": true,
            "hint": "Enter annual family income in INR"
        },
        {
            "id": "category",
            "type": "text",
            "label": "Category (General/SC/ST/OBC)",
            "required": true,
            "hint": "Enter your category as per government records"
        }
    ]',
    '[
        "Must be a citizen of the country",
        "Family income should be less than 8 lakhs per annum",
        "Minimum 60% marks in Class 12",
        "Currently enrolled in a recognized institution"
    ]',
    'active'
); 