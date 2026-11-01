<?php
// counselor/fees.php - Student Fee Dues & Billing Lookup Console
require_once 'includes/auth.php';
require_counselor_module('fees');

$search = trim($_GET['search'] ?? '');
$sql = "SELECT f.*, s.name as student_name, s.reg_no, s.phone, s.target_school 
        FROM fees_generated f 
        LEFT JOIN students s ON f.student_id = s.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR s.reg_no LIKE ? OR s.phone LIKE ? OR f.invoice_no LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY f.id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$fees = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Ledger Lookup | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { background: #f8fafc; color: #475569; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .custom-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.86rem; vertical-align: middle; }
        .custom-table tr:hover { background: #f8fafc; }
        .form-input { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.88rem; font-family: inherit; background: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-file-invoice-dollar" style="color: #16a34a;"></i> Student Fee Dues &amp; Billing Lookup
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Verify student fee balances and invoice details during parent counseling.
                    </p>
                </div>
            </div>

            <div class="portal-card">
                <form method="GET" action="fees.php" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student name, reg no, phone or invoice no..." class="form-input" style="flex: 1; min-width: 250px;">
                    <button type="submit" class="btn-portal btn-teal">
                        <i class="fas fa-search"></i> Search Ledger
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="fees.php" class="btn-portal btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Invoice No</th>
                                <th>Billing Month</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                                <th>Billing Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$fees || $fees->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        No billing records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($f = $fees->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a;">
                                                <?php echo htmlspecialchars($f['student_name'] ?: 'Student #' . $f['student_id']); ?>
                                            </div>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                <?php echo htmlspecialchars($f['reg_no'] ?: 'ID #' . $f['student_id']); ?> • <?php echo htmlspecialchars($f['phone'] ?: ''); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-weight: 700; background: #f1f5f9; padding: 3px 8px; border-radius: 6px;">
                                                <?php echo htmlspecialchars($f['invoice_no'] ?: 'INV' . $f['id']); ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 600; color: #334155;">
                                            <?php echo htmlspecialchars($f['month_for']); ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 900; color: <?php echo (float)$f['amount'] > 0 ? '#dc2626' : '#16a34a'; ?>; font-size: 1rem;">
                                                ₹<?php echo number_format((float)$f['amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($f['status'] === 'paid'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Paid</span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;">Due</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.8rem; max-width: 300px;">
                                            <?php echo htmlspecialchars($f['remark'] ?: '-'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
