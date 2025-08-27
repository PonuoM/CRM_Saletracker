# CRM SalesTracker Cron Job Fix Documentation

## Problem Description

The cron job was failing with the following error:
```
Fatal error: Database connection failed: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)
```

This error occurred because the cron job was incorrectly detecting the environment as development instead of production, causing it to use the development database credentials (root with no password) instead of the production credentials.

## Root Cause

The issue was in the environment detection logic in `config/config.php`. When cron jobs run via CLI (Command Line Interface), the `$_SERVER['HTTP_HOST']` variable is not set, so the system was defaulting to development settings.

## Solution Implemented

### 1. Enhanced Environment Detection

Modified `config/config.php` to properly detect the production environment when running from CLI:

```php
// ตรวจสอบ environment
$is_production = (isset($_SERVER['HTTP_HOST']) && 
                 strpos($_SERVER['HTTP_HOST'], 'prima49.com') !== false);

// สำหรับ CLI scripts (เช่น cron jobs) ตรวจสอบจาก path แทน
if (php_sapi_name() === 'cli') {
    $is_production = (strpos(__DIR__, '/home/primacom/domains/prima49.com/') !== false);
}
```

This additional check examines the file path when running from CLI to determine if it's in the production environment.

### 2. Improved Logging

Enhanced `cron/run_all_jobs.php` with better logging capabilities:

- Added detailed logging with timestamps and environment information
- Created a separate log file `cron/cron_execution_log.txt` for detailed tracking
- Improved error reporting with more context

## How to Test the Fix

### 1. Test Database Connection

Run the test script to verify database connectivity:
```bash
cd /home/primacom/domains/prima49.com/public_html/Customer
php test_database_connection.php
```

### 2. Test Cron Job Execution

Run the cron job manually to verify it works:
```bash
cd /home/primacom/domains/prima49.com/public_html/Customer
php cron/run_all_jobs.php
```

Check the log files:
- `cron/cron_execution_log.txt` for detailed execution logs
- Database table `cron_job_logs` for job execution history

## Cron Job Setup

### Current Cron Job Entry

The current cron job entry should work correctly with the fix:
```
/usr/local/bin/php -q /home/primacom/domains/prima49.com/public_html/Customer/cron/run_all_jobs.php >> /home/primacom/domains/prima49.com/public_html/Customer/cron/cron.log 2>&1
```

### Recommended Cron Job Schedule

For optimal performance, the following schedule is recommended:
```
# Run every 5 minutes
*/5 * * * * /usr/local/bin/php -q /home/primacom/domains/prima49.com/public_html/Customer/cron/run_all_jobs.php >> /home/primacom/domains/prima49.com/public_html/Customer/cron/cron.log 2>&1
```

## Monitoring and Troubleshooting

### Log Files

1. **Main Log**: `cron/cron.log` - Standard output from cron execution
2. **Detailed Log**: `cron/cron_execution_log.txt` - Detailed execution information
3. **Database Log**: `cron_job_logs` table in database - Structured job execution history

### Common Issues and Solutions

1. **Database Connection Errors**
   - Verify environment detection is working correctly
   - Check database credentials in `config/config.php`
   - Ensure database server is accessible

2. **Permission Issues**
   - Ensure the web server user has read/write permissions on log files
   - Verify PHP execution permissions

3. **Path Issues**
   - Confirm all file paths in the cron job are absolute
   - Ensure the PHP binary path is correct (`/usr/local/bin/php`)

## Verification Steps

1. Run the test database connection script
2. Execute the cron job manually
3. Check log files for successful execution
4. Verify database entries in `cron_job_logs` table
5. Monitor for any errors in the standard cron log

## Additional Recommendations

1. **Regular Monitoring**: Set up alerts for cron job failures
2. **Log Rotation**: Implement log rotation to prevent disk space issues
3. **Backup Strategy**: Ensure cron job logs are backed up regularly
4. **Performance Monitoring**: Monitor execution time of cron jobs