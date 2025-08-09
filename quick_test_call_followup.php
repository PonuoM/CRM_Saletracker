<?php
// Simple test to check call follow-up data
echo "=== Quick Test Call Follow-up ===\n";

// Check if config file exists
if (!file_exists('config/config.php')) {
    echo "❌ config/config.php not found\n";
    exit;
}

require_once 'config/config.php';

try {
    $db = new Database();
    
    // Check call_logs table
    echo "1. Checking call_logs table...\n";
    $totalCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs")['count'];
    echo "   Total calls: $totalCalls\n";
    
    // Check calls that need follow-up
    $followupCalls = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE call_result IN ('not_interested', 'callback', 'interested', 'complaint')")['count'];
    echo "   Calls needing follow-up: $followupCalls\n";
    
    // Check calls with next_followup_at
    $withFollowupAt = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE next_followup_at IS NOT NULL")['count'];
    echo "   Calls with next_followup_at: $withFollowupAt\n";
    
    // Check calls without next_followup_at but need follow-up
    $missingFollowup = $db->fetchOne("SELECT COUNT(*) as count FROM call_logs WHERE call_result IN ('not_interested', 'callback', 'interested', 'complaint') AND next_followup_at IS NULL")['count'];
    echo "   Calls missing next_followup_at: $missingFollowup\n";
    
    if ($missingFollowup > 0) {
        echo "\n2. Found $missingFollowup calls that need next_followup_at\n";
        
        // Show sample data
        $sampleCalls = $db->fetchAll("
            SELECT 
                cl.log_id,
                cl.customer_id,
                cl.call_result,
                cl.created_at,
                c.first_name,
                c.last_name
            FROM call_logs cl
            JOIN customers c ON cl.customer_id = c.customer_id
            WHERE cl.call_result IN ('not_interested', 'callback', 'interested', 'complaint')
            AND cl.next_followup_at IS NULL
            ORDER BY cl.created_at DESC
            LIMIT 3
        ");
        
        echo "   Sample calls:\n";
        foreach ($sampleCalls as $call) {
            echo "   - ID: {$call['log_id']}, Customer: {$call['first_name']} {$call['last_name']}, Result: {$call['call_result']}, Date: {$call['created_at']}\n";
        }
        
        echo "\n3. This is why the customer list is not showing!\n";
        echo "   The API query requires next_followup_at IS NOT NULL\n";
        echo "   But these calls don't have next_followup_at set.\n";
        
    } else {
        echo "\n✅ All calls that need follow-up have next_followup_at set.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
