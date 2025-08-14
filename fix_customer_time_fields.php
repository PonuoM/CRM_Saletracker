<?php
/**
 * Backfill customer_time_base and customer_time_expiry for recently assigned customers
 * Usage (browser or CLI):
 *   - Dry run (default): fix_customer_time_fields.php?hours=72
 *   - Apply changes:      fix_customer_time_fields.php?hours=72&apply=1
 * Note: Requires authenticated admin/super_admin/supervisor session when run via web.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

if (php_sapi_name() !== 'cli') {
    @session_start();
    $role = $_SESSION['role_name'] ?? '';
    if (!in_array($role, ['admin', 'super_admin', 'supervisor'])) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$hours = isset($_GET['hours']) ? max(1, (int)$_GET['hours']) : 72;
$apply = isset($_GET['apply']) && (int)$_GET['apply'] === 1;

$db = new Database();

// Find candidates: assigned, missing time fields, assigned recently
$sqlPreview = "
    SELECT customer_id, assigned_to, assigned_at, customer_time_base, customer_time_expiry
    FROM customers
    WHERE basket_type = 'assigned'
      AND (
        customer_time_base IS NULL OR customer_time_expiry IS NULL OR
        customer_time_base = '0000-00-00 00:00:00' OR customer_time_expiry = '0000-00-00 00:00:00'
      )
      AND assigned_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY assigned_at DESC
";

// Use a manual prepare because Database::query expects positional/named params only; use named
$pdo = $db->getConnection();
$stmt = $pdo->prepare($sqlPreview);
$stmt->execute(['hours' => $hours]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($rows);
echo "Candidates (last {$hours} hours): {$total}\n";

// Group by telesales for quick visibility
$byUser = [];
foreach ($rows as $r) {
    $u = (string)($r['assigned_to'] ?? '');
    if (!isset($byUser[$u])) { $byUser[$u] = 0; }
    $byUser[$u]++;
}
ksort($byUser);
foreach ($byUser as $uid => $cnt) {
    echo " - user_id {$uid}: {$cnt}\n";
}

if ($total === 0) {
    echo "No records to update.\n";
    exit;
}

if (!$apply) {
    echo "\nDry run only. Append &apply=1 to perform updates.\n";
    exit;
}

// Apply update: set base=assigned_at, expiry=assigned_at+30 days
$sqlUpdate = "
    UPDATE customers
    SET customer_time_base = assigned_at,
        customer_time_expiry = DATE_ADD(assigned_at, INTERVAL 30 DAY),
        updated_at = NOW()
    WHERE basket_type = 'assigned'
      AND (
        customer_time_base IS NULL OR customer_time_expiry IS NULL OR
        customer_time_base = '0000-00-00 00:00:00' OR customer_time_expiry = '0000-00-00 00:00:00'
      )
      AND assigned_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
";

$stmt2 = $pdo->prepare($sqlUpdate);
$stmt2->execute(['hours' => $hours]);
$affected = $stmt2->rowCount();

echo "Updated rows: {$affected}\n";


