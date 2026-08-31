<?php
// admin/restore_backup_db.php - 1-Click Database Reset / Revoke Tool
require_once 'includes/auth.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_restore'])) {
    // Restore exact baseline database state
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. Delete all generated fees
    $conn->query("TRUNCATE TABLE fees_generated");
    
    // 2. Insert original 9 baseline fee invoices
    $inserts = [
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (1, 1, 1500.00, 'May', '2026-05-28', 'Dudh', 'paid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (2, 1, 3000.00, 'June', '2026-05-30', 'Dudh', 'paid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (3, 1, 122.00, 'May', '2026-05-30', 'sdfsdf', 'paid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (4, 1, 3339.00, 'May, August 2026', '2026-05-30', '', 'unpaid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (5, 2, 7500.00, 'June, July 2026', '2026-06-02', '', 'unpaid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (6, 3, 5999.98, 'June, September 2026', '2026-06-13', '', 'paid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (7, 4, 13000.00, 'June 2026, July 2026', '2026-06-13', '', 'unpaid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (8, 5, 7093.33, 'June 2026, July 2026', '2026-06-15', '', 'unpaid')",
        "INSERT INTO fees_generated (id, student_id, amount, month_for, billing_date, remark, status) VALUES (9, 3, 7163.98, 'July 2026, April 2026', '2026-07-02', '', 'unpaid')",
    ];
    foreach ($inserts as $sql) {
        $conn->query($sql);
    }
    
    // 3. Reset students last_billed_date
    $conn->query("UPDATE students SET last_billed_date = '2026-08-31' WHERE id = 1");
    $conn->query("UPDATE students SET last_billed_date = '2026-12-30' WHERE id = 2");
    $conn->query("UPDATE students SET last_billed_date = '2026-10-31' WHERE id = 3");
    $conn->query("UPDATE students SET last_billed_date = '2026-08-31' WHERE id = 4");
    $conn->query("UPDATE students SET last_billed_date = '2026-08-31' WHERE id = 5");
    $conn->query("UPDATE students SET last_billed_date = NULL WHERE id = 6");
    
    // 4. Reset AUTO_INCREMENT
    $conn->query("ALTER TABLE fees_generated AUTO_INCREMENT = 10");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $msg = "Database has been successfully revoked and restored to its original starting state!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Restore / Rollback Tool - ABSS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/head_css.php'; ?>
</head>
<body style="font-family: 'Outfit', sans-serif; background: #f8fafc; margin: 0;">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 30px;">
        <div style="max-width: 700px; margin: 40px auto; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
            
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 15px;">
                    <i class="fas fa-history"></i>
                </div>
                <h1 style="font-size: 1.6rem; color: #0f172a; margin: 0 0 8px 0; font-weight: 800;">Revoke & Restore Database</h1>
                <p style="color: #64748b; font-size: 0.9rem; margin: 0;">1-Click tool to undo billing changes and return all fees to the exact starting state.</p>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: #dcfce7; color: #166534; padding: 14px 18px; border-radius: 8px; font-weight: 700; margin-bottom: 20px; text-align: center; border: 1px solid #bbf7d0;">
                    <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div style="background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; padding: 18px; margin-bottom: 25px; font-size: 0.88rem; color: #334155; line-height: 1.6;">
                <strong>What this restore does:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <li>Restores the 9 original baseline fee records in <code>fees_generated</code>.</li>
                    <li>Resets all student <code>last_billed_date</code> values to their original baseline.</li>
                    <li>Clears any extra duplicate billing amounts added during testing.</li>
                </ul>
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to restore the database to original starting state?');">
                <button type="submit" name="confirm_restore" value="1" style="width: 100%; background: #dc2626; color: #fff; border: none; padding: 14px; border-radius: 8px; font-weight: 800; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit;">
                    <i class="fas fa-undo-alt"></i> Confirm 1-Click Database Rollback
                </button>
            </form>

            <div style="text-align: center; margin-top: 15px;">
                <a href="fees.php" style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Fees Management
                </a>
            </div>

        </div>
    </main>
</body>
</html>
