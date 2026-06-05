-- Platform Tracking Table Migration
-- Run this to add sales tracking for platform leads

-- Drop table if exists (for clean migration)
DROP TABLE IF EXISTS `platform_tracking`;

CREATE TABLE `platform_tracking` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform_id` int(11) NOT NULL,
  `sales_rep_id` int(10) UNSIGNED DEFAULT NULL,
  `contacted` enum('Yes','No') DEFAULT NULL COMMENT 'Has the lead been contacted?',
  `quoted` enum('Yes','No') DEFAULT NULL COMMENT 'Has a quote been provided?',
  `sales_qualified` enum('Yes','No') DEFAULT NULL COMMENT 'Is this a Sales Qualified Lead?',
  `to_win` enum('Yes','No') DEFAULT NULL COMMENT 'Is this lead won?',
  `wa_amount` decimal(18,2) DEFAULT 0.00 COMMENT 'Weighted Amount',
  `notes` text DEFAULT NULL COMMENT 'Sales tracking notes',
  `branch` varchar(100) DEFAULT NULL COMMENT 'Sales rep branch',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_id` (`platform_id`),
  KEY `idx_platform_tracking` (`platform_id`),
  KEY `idx_sales_rep` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE `platform_tracking`
  ADD CONSTRAINT `fk_platform_tracking_platform` 
  FOREIGN KEY (`platform_id`) 
  REFERENCES `platform_leads` (`id`) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_platform_tracking_sales_rep`
  FOREIGN KEY (`sales_rep_id`)
  REFERENCES `users` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;
