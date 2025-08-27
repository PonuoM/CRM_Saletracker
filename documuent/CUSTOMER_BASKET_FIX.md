# Customer Basket Management Fix Documentation

## Problem Description

ลูกค้าที่มีเวลาเหลือ น้อยกว่า 0 ตามกฎที่ตั้งไว้จะต้องถูกดึงคืนไปอยู่ตระกรารอ แต่ปัจจุบันมีปัญหาที่ลูกค้าที่เกินมา 2 วันแล้วยังไม่ถูกดึงคืน

## Root Cause Analysis

After analyzing the code, we found several issues in the customer basket management logic:

1. **Conflicting Logic**: The [CronJobService::customerBasketManagement()](file:///c:/xampp/htdocs/CRM-CURSOR/app/services/CronJobService.php#L437-L576) and [WorkflowService::runManualRecall()](file:///c:/xampp/htdocs/CRM-CURSOR/app/services/WorkflowService.php#L226-L282) had conflicting logic for handling customers with expired [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222).

2. **Missing Expiry Handling**: The cron job service was not properly handling customers with expired [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222) - some queries had conditions requiring [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222) > NOW(), which would exclude expired customers from being processed.

3. **Incomplete Logic**: The logic for moving customers from "waiting" to "distribution" baskets was not considering expired customers properly.

## Solution Implemented

### 1. Fixed CronJobService::customerBasketManagement()

Enhanced the method with proper handling of expired customers:

- **Added explicit expiry handling**: Added a new SQL query to deactivate customers with expired [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222)
- **Updated existing queries**: Modified all existing queries to properly consider expired customers
- **Added new result counter**: Added tracking for expired customers deactivated

### 2. Fixed WorkflowService::runManualRecall()

Updated the method to have consistent logic with the cron job service:

- **Consistent expiry handling**: Made sure both services handle expired customers the same way
- **Proper query conditions**: Updated all queries to correctly handle customers with and without [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222)

### 3. Enhanced Logging

Added proper logging for the new activity type:
- Added "expired_customers_deactivated" to the activity descriptions

## Files Modified

1. `app/services/CronJobService.php` - Fixed customer basket management logic
2. `app/services/WorkflowService.php` - Fixed manual recall logic
3. Created `test_basket_fix.php` - Test script to verify the fix
4. Created `CUSTOMER_BASKET_FIX.md` - This documentation

## How the Fix Works

### New Logic Flow

1. **Fix Data Consistency**: First, fix any inconsistent data (assigned_at, basket_type)
2. **Handle Expired Customers**: Deactivate customers with expired [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222)
3. **Process Assigned Customers**: Move assigned customers who have timed out back to distribution
4. **Process Expired Assigned Customers**: Move assigned customers with expired time to waiting
5. **Process Waiting Customers**: Move waiting customers who have waited 30 days back to distribution

### Key Changes

1. **Expiry Check**: All queries now properly check for [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222) being NULL or > NOW() for active processing
2. **Explicit Expiry Handling**: Added explicit handling for customers with [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222) <= NOW()
3. **Consistent Logic**: Both cron job and manual recall now use the same logic

## Testing the Fix

To test the fix, run:

```bash
cd /home/primacom/domains/prima49.com/public_html/Customer
php test_basket_fix.php
```

This will execute the customer basket management logic and show the results.

## Verification

After deploying the fix, you should see:

1. Customers with expired [customer_time_expiry](file:///c:/xampp/htdocs/CRM-CURSOR/app/core/Database.php#L222-L222) being properly deactivated
2. Customers in the "waiting" basket for more than 30 days being moved to "distribution"
3. Proper logging in the activity logs
4. No more customers stuck in incorrect basket states

## Monitoring

Monitor the cron job logs and activity logs to ensure:
- The fix is working correctly
- No customers are being incorrectly processed
- Performance is acceptable