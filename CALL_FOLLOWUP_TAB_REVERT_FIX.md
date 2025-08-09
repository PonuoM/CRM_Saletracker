# Call Follow-up Table Tab Revert Fix

## Problem
The user reported that the "Call Follow-up" table was displaying in the wrong tab. After the previous fixes, the table was showing in the "Do" tab instead of the "Call Management" tab (หน้า 3 - การโทรติดตาม).

User feedback: "ใช่ !! มันแสดง แต่มันไม่ควรแสดงในหน้า do มันควรแสดงในหน้า 3 คือหน้า การโทรติดตาม นำหน้า do กลับมาเป็นเหมือนเดิมและนำหน้านี้ไปอยู่ใน หน้า 3"

## Solution
Reverted the changes in `app/views/customers/index.php`:

### Changes Made:

1. **Removed from "Do" tab:**
   - Removed the `call-followup-table` div that was dynamically loaded by JavaScript
   - Restored the original PHP-rendered table content for `$followUpCustomers`

2. **Restored "Do" tab to original state:**
   - The "Do" tab now shows the PHP-rendered table with static data from `$followUpCustomers`
   - Includes proper table structure with headers, rows, and action buttons
   - Shows follow-up dates, priorities, and status badges

3. **"Call Management" tab remains correct:**
   - The `call-followup-table` div is already correctly positioned in the "calls" tab
   - This tab will be dynamically populated by JavaScript when users switch to it

## File Modified
- `app/views/customers/index.php`

## Expected Result
- **"Do" tab:** Shows static PHP-rendered table with today's follow-up tasks
- **"Call Management" tab (หน้า 3):** Shows dynamic JavaScript-loaded call follow-up table with filtering options

## Testing
1. Login as telesales user
2. Navigate to customers page
3. Verify "Do" tab shows static follow-up table
4. Click on "การโทรติดตาม" tab (tab 3)
5. Verify call follow-up table loads dynamically with JavaScript

## Status
✅ **COMPLETED** - Table placement reverted to correct tabs as requested by user.
