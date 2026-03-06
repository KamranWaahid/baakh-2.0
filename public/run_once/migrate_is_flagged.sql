-- Hesudhar: Add is_flagged column to baakh_hesudhars
-- Run this once in phpMyAdmin > SQL tab
-- Safe to run: it checks if the column already exists before adding

ALTER TABLE `baakh_hesudhars`
    ADD COLUMN IF NOT EXISTS `is_flagged` TINYINT(1) NOT NULL DEFAULT 0 AFTER `correct`;
