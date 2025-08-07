-- Add supervisor_id column to users table for team management
-- This allows supervisors to manage their team members

ALTER TABLE `users` 
ADD COLUMN `supervisor_id` INT NULL AFTER `company_id`,
ADD FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL;

-- Add index for better performance
ALTER TABLE `users` 
ADD INDEX `idx_supervisor_id` (`supervisor_id`);

-- Update existing telesales users to be assigned to supervisor (user_id = 2)
UPDATE `users` 
SET `supervisor_id` = 2 
WHERE `role_id` = 4 AND `user_id` IN (3, 4);

-- Add comment to document the relationship
ALTER TABLE `users` 
MODIFY COLUMN `supervisor_id` INT NULL COMMENT 'References user_id of supervisor who manages this user';
