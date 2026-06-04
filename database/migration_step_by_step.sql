-- ============================================================
-- Step-by-Step Migration for phpMyAdmin
-- ============================================================
-- Run these commands ONE BY ONE in phpMyAdmin SQL tab
-- Copy and paste each command separately
-- ============================================================

-- Step 1: Add contacted column
ALTER TABLE sales_tracking 
ADD COLUMN contacted ENUM('Yes', 'No') NULL COMMENT 'Has the project been contacted?';

-- Step 2: Add quoted column  
ALTER TABLE sales_tracking 
ADD COLUMN quoted ENUM('Yes', 'No') NULL COMMENT 'Has a quote been provided?';

-- Step 3: Add sales_qualified column (changed from 'sql' since it's a reserved word)
ALTER TABLE sales_tracking 
ADD COLUMN sales_qualified ENUM('Yes', 'No') NULL COMMENT 'Is this a Sales Qualified Lead?';

-- Step 4: Add to_win column
ALTER TABLE sales_tracking 
ADD COLUMN to_win ENUM('Yes', 'No') NULL COMMENT 'Is this project won?';

-- Step 5: Add wa_amount column
ALTER TABLE sales_tracking 
ADD COLUMN wa_amount DECIMAL(18,2) NULL DEFAULT 0.00 COMMENT 'Win/Loss Amount';

-- Step 6: Add tracking_status column
ALTER TABLE sales_tracking 
ADD COLUMN tracking_status ENUM('Not Started', 'In Progress', 'Complete') NOT NULL DEFAULT 'Not Started' COMMENT 'Sales tracking progress status';

-- Step 7: Add indexes (run these one by one too)
CREATE INDEX idx_sales_tracking_contacted ON sales_tracking(contacted);

CREATE INDEX idx_sales_tracking_quoted ON sales_tracking(quoted);

CREATE INDEX idx_sales_tracking_sales_qualified ON sales_tracking(sales_qualified);

CREATE INDEX idx_sales_tracking_to_win ON sales_tracking(to_win);

CREATE INDEX idx_sales_tracking_status ON sales_tracking(tracking_status);