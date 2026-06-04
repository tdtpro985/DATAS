-- ============================================================
-- Migration: Add Sales Funnel Fields to sales_tracking table
-- ============================================================
-- This migration adds the required fields for the sales funnel:
-- - contacted (Yes/No) - Has the project been contacted?
-- - quoted (Yes/No) - Has a quote been provided?
-- - sales_qualified (Yes/No) - Is this a Sales Qualified Lead?
-- - to_win (Yes/No) - Is this project won?
-- - wa_amount (DECIMAL) - Win/Loss Amount
-- - tracking_status (ENUM) - Sales Tracking Status
-- - remarks (TEXT) - Additional notes (already exists as 'notes')
-- ============================================================

-- Add the new columns to sales_tracking table
ALTER TABLE sales_tracking 
ADD COLUMN contacted ENUM('Yes', 'No') NULL COMMENT 'Has the project been contacted?',
ADD COLUMN quoted ENUM('Yes', 'No') NULL COMMENT 'Has a quote been provided?',
ADD COLUMN sales_qualified ENUM('Yes', 'No') NULL COMMENT 'Is this a Sales Qualified Lead?',
ADD COLUMN to_win ENUM('Yes', 'No') NULL COMMENT 'Is this project won?',
ADD COLUMN wa_amount DECIMAL(18,2) NULL DEFAULT 0.00 COMMENT 'Win/Loss Amount',
ADD COLUMN tracking_status ENUM('Not Started', 'In Progress', 'Complete') NOT NULL DEFAULT 'Not Started' COMMENT 'Sales tracking progress status';

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_sales_tracking_contacted ON sales_tracking(contacted);
CREATE INDEX IF NOT EXISTS idx_sales_tracking_quoted ON sales_tracking(quoted);
CREATE INDEX IF NOT EXISTS idx_sales_tracking_sales_qualified ON sales_tracking(sales_qualified);
CREATE INDEX IF NOT EXISTS idx_sales_tracking_to_win ON sales_tracking(to_win);
CREATE INDEX IF NOT EXISTS idx_sales_tracking_status ON sales_tracking(tracking_status);

-- Note: 'remarks' field already exists as 'notes' in the current schema
-- No need to add it again

COMMIT;