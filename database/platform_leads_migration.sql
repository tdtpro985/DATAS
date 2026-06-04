-- Migration: Create platform_leads table
-- This migration creates the platform_leads table for storing platform lead information

-- Create platform_leads table
CREATE TABLE IF NOT EXISTS platform_leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source VARCHAR(50) NOT NULL,
    company_name VARCHAR(255) NULL,
    contact_person VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50) NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    company_location VARCHAR(255) NULL,
    materials_quantity TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    archived_by INT NULL,
    
    INDEX idx_source (source),
    INDEX idx_contact_person (contact_person),
    INDEX idx_email_address (email_address),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by),
    INDEX idx_archived_at (archived_at)
);

-- Insert some sample data for testing (optional)
-- INSERT INTO platform_leads (source, company_name, contact_person, contact_number, email_address, company_location, materials_quantity, created_by) VALUES
-- ('DPWH', 'Sample Construction Corp', 'John Smith', '(02) 123-4567', 'john.smith@sample.com', 'Manila, Philippines', 'Steel pipes: 100 pcs\nConcrete blocks: 500 pcs', 1),
-- ('BCI', 'Metro Infrastructure Inc', 'Jane Doe', '09171234567', 'jane.doe@metro.com', 'Quezon City, Philippines', 'Sheet piles: 200 linear meters\nRebar: 10 tons', 1),
-- ('PHILGEPS', null, 'Robert Johnson', '(032) 555-0123', 'r.johnson@email.com', null, null, 1);