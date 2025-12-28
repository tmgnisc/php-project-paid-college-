-- SQL Script to Update Admin Credentials
-- Change admin username from "anshika" to "admin"
-- 
-- Usage: Run this in phpMyAdmin or MySQL command line
-- 
-- IMPORTANT: Replace 'your_current_password' with the actual current password
--            or remove the password line if you only want to change the username

-- Option 1: Update only the username (keep current password)
UPDATE `admin_cred` SET `admin_name` = 'admin' WHERE `admin_name` = 'anshika';

-- Option 2: Update both username and password
-- Uncomment the line below and replace 'your_new_password' with your desired password
-- UPDATE `admin_cred` SET `admin_name` = 'admin', `admin_pass` = 'your_new_password' WHERE `admin_name` = 'anshika';

-- Verify the update
SELECT `sr_no`, `admin_name` FROM `admin_cred`;

