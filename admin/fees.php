<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_payment'])) {
    $sid = $_POST['student_id'];
    $amount = $_POST['amount'];
    $date = $_POST['payment_date'];
    $month = $_POST['month_for'];
    $method = $_POST['payment_method'];

    $stmt = $conn->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, month_for, payment_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $sid, $amount, $date, $month, $method);
    $stmt->execute();
    $msg = "Payment recorded successfully.";
}

$students = $conn->query("SELECT id, name FROM students WHERE status = 'active' ORDER BY name ASC");
$payments = $conn->query("
    SELECT f.*, s.name 
    FROM fee_payments f 
    JOIN students s ON f.student_id = s.id 
    ORDER BY f.payment_date DESC LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Ledger | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; }
        td { padding: 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; font-weight: 600; color: #5c6bc0; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .amount-tag { background: #f0fdf4; color: #166534; padding: 8px 15px; border-radius: 10px; font-weight: 800; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Fee Management</h1>
            <p>Record payments and track tuition history.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-grid">
            <div class="portal-card">
                <h3 style="margin-bottom: 30px;">Collect Fee</h3>
                <form action="" method="POST">
                    <div class="portal-input-group">
                        <label>Student Name</label>
                        <select name="student_id" required>
                            <option value="">Select Student...</option>
                            <?php while($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" placeholder="e.g. 5000" required>
                    </div>
                    <div class="portal-input-group">
                        <label>For Month</label>
                        <select name="month_for" required>
                            <?php 
                            $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                            foreach($months as $m) echo "<option value='$m'>$m</option>";
                            ?>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Method</label>
                        <select name="payment_method">
                            <option>Cash</option>
                            <option>PhonePe / GPay</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" name="record_payment" class="btn-portal w-100">Submit Payment</button>
                </form>
            </div>

            <div class="list-section">
                <h3 style="margin-bottom: 25px; padding-left: 20px;">Recent Collections</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $payments->fetch_assoc()): ?>
                            <tr class="attendance-row">
                                <td style="color:var(--portal-blue); font-weight:800;"><?php echo htmlspecialchars($p['name']); ?></td>
                                <td><?php echo $p['month_for']; ?></td>
                                <td><span class="amount-tag">₹ <?php echo number_format($p['amount']); ?></span></td>
                                <td><?php echo date('d M, Y', strtotime($p['payment_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
