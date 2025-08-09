# System Settings Column Name Fix Summary

## Problem
The user encountered an error when trying to apply the `add_call_followup_system.sql` script:

```
MySQL said: #1054 - Unknown column 'setting_description' in 'field list'
```

This error occurred when the script attempted to insert data into the `system_settings` table using a column name that doesn't exist.

## Root Cause
The `add_call_followup_system.sql` script contains this INSERT statement:

```sql
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`, `setting_type`) VALUES
('call_followup_enabled', '1', 'เปิดใช้งานระบบติดตามการโทร', 'boolean'),
('call_followup_auto_queue', '1', 'สร้างคิวการติดตามอัตโนมัติ', 'boolean'),
('call_followup_notification', '1', 'แจ้งเตือนการติดตาม', 'boolean'),
('call_followup_max_days', '30', 'จำนวนวันสูงสุดในการติดตาม', 'integer')
```

However, according to the actual database schema in `database/primacom_Customer.sql`, the `system_settings` table has a column named `description`, not `setting_description`:

```sql
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,  -- Correct column name
  `is_editable` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
)
```

## Solution
Updated the `fix_schema_and_performance.php` script to include the corrected `system_settings` update with the proper column name:

```sql
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `setting_type`) VALUES
('call_followup_enabled', '1', 'เปิดใช้งานระบบติดตามการโทร', 'boolean'),
('call_followup_auto_queue', '1', 'สร้างคิวการติดตามอัตโนมัติ', 'boolean'),
('call_followup_notification', '1', 'แจ้งเตือนการติดตาม', 'boolean'),
('call_followup_max_days', '30', 'จำนวนวันสูงสุดในการติดตาม', 'integer')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description),
updated_at = NOW()
```

## Changes Made
1. **Added Step 6** to `fix_schema_and_performance.php` to update `system_settings`
2. **Corrected column name** from `setting_description` to `description`
3. **Updated step numbering** to maintain proper sequence
4. **Added error handling** for the system_settings update

## Expected Outcome
- The `system_settings` table will be properly updated with call follow-up configuration
- No more "Unknown column 'setting_description'" errors
- The call follow-up system will have proper configuration settings
- All schema issues from `add_call_followup_system.sql` will be resolved

## Next Steps
Run the updated `fix_schema_and_performance.php` script to apply all fixes including the corrected `system_settings` update.
