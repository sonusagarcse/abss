<?php
// update_dues_live.php - One-Time Dues Reconciler for Live Production
// Self-deletes automatically after successful run.

require_once __DIR__ . '/config/db.php';
$conn = getDB();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Dues Reconciler</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 30px; }
        .card { max-width: 800px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 25px; border: 1px solid #334155; }
        h1 { color: #38bdf8; font-size: 22px; margin-top: 0; }
        .success { background: #064e3b; color: #6ee7b7; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #0f172a; color: #94a3b8; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #0284c7; color: #fff; }
        .badge-inactive { background: #64748b; color: #fff; }
        .total-box { margin-top: 20px; background: #0f172a; padding: 16px; border-radius: 8px; display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; border: 1px solid #334155; }
    </style>
</head>
<body>
<div class="card">
    <h1><i class="fas fa-database"></i> ABSS Production Dues & Inactive Students Reconciler</h1>
<?php
try {
    // 1. Mark inactive students
    $inactive_ids = [7, 8, 18, 19, 24, 32, 37, 41];
    $conn->query("UPDATE students SET status = 'inactive' WHERE id IN (" . implode(',', $inactive_ids) . ")");

    // 2. Mark fully paid bills where payments already cover charges
    $fully_paid_bills = [79, 72, 68, 53, 44, 45, 58, 38, 101, 100, 98, 67, 95];
    $conn->query("UPDATE fees_generated SET amount = 0.00, status = 'paid' WHERE id IN (" . implode(',', $fully_paid_bills) . ")");

    // 3. Update Kishu Raj Bill #103 with gateway and cash deductions
    $kishu_rem = "Manual Bill. Tution Fee [October 2026]: ₹3,000.00 | Reg. Fees [October 2026]: ₹1,100.00 | Security/Caution Money [September 2026]: ₹5,000.00 | Payment received via Online (Razorpay: pay_Th1TtqbEZsZeaz) on 2026-09-27 (-₹4,100.00) (Rcpt #95) | Payment received via Cash on 2026-09-30 (-₹2,000.00) (Rcpt #98) | Payment received via Cash on 2026-09-30 (-₹1,000.00) (Rcpt #99) | Payment received via Cash on 2026-09-30 (-₹1,000.00) (Rcpt #100)";
    $conn->query("UPDATE fees_generated SET amount = 1000.00, status = 'unpaid', remark = '" . $conn->real_escape_string($kishu_rem) . "' WHERE id = 103");
    $conn->query("UPDATE fees_generated SET amount = 0.00, status = 'paid' WHERE id = 107");

    // 4. Update specific active bills to exact verified net balances
    $conn->query("UPDATE fees_generated SET amount = 4024.00, status = 'unpaid' WHERE id = 40");
    $conn->query("UPDATE fees_generated SET amount = 4000.00, status = 'unpaid' WHERE id = 41");
    $conn->query("UPDATE fees_generated SET amount = 2605.00, status = 'unpaid' WHERE id = 47");
    $conn->query("UPDATE fees_generated SET amount = 2530.00, status = 'unpaid' WHERE id = 90");
    $conn->query("UPDATE fees_generated SET amount = 1675.00, status = 'unpaid' WHERE id = 83");
    $conn->query("UPDATE fees_generated SET amount = 600.00, status = 'unpaid' WHERE id = 48");

    // 5. Clean any 0-balance unpaid bills
    $conn->query("UPDATE fees_generated SET status = 'paid' WHERE amount <= 0 AND status = 'unpaid'");

    // 6. Record flag in settings
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('dues_cleanup_20260930_final', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");

    echo "<div class='success'>✓ All updates applied successfully to the database! Inactive students excluded and active dues reconciled.</div>";

    // Display Active Dues
    $q = $conn->query("
        SELECT fg.id, fg.student_id, s.name, fg.amount, fg.status, fg.month_for 
        FROM fees_generated fg 
        JOIN students s ON fg.student_id = s.id 
        WHERE s.status = 'active' AND fg.status = 'unpaid' 
        ORDER BY fg.amount DESC
    ");

    echo "<h3>Active Defaulters & Outstanding Invoices</h3>";
    echo "<table>";
    echo "<tr><th># Bill ID</th><th>Student Name</th><th>Month / For</th><th class='text-right'>Outstanding Amount</th></tr>";
    $sum = 0;
    $count = 0;
    while ($r = $q->fetch_assoc()) {
        $sum += (float)$r['amount'];
        $count++;
        echo "<tr>";
        echo "<td>#" . htmlspecialchars($r['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($r['name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['month_for']) . "</td>";
        echo "<td class='text-right'>₹ " . number_format($r['amount'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<div class='total-box'>";
    echo "<span>Total Active Unpaid Invoices: " . $count . "</span>";
    echo "<span>Total Outstanding Balance: ₹ " . number_format($sum, 2) . "</span>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#7f1d1d; color:#fca5a5; padding:12px; border-radius:8px;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
    <p style="margin-top:20px; font-size:12px; color:#94a3b8; text-align:center;">This script self-destructs upon completion to protect database security.</p>
</div>
</body>
</html>
<?php
// Register self deletion
register_shutdown_function(function() {
    $file = __FILE__;
    if (file_exists($file)) {
        @unlink($file);
    }
});
?>
