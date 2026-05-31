<?php
// admin/student_addons.php - Manage Recurring Add-ons for a Student
require_once 'includes/auth.php';

if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = (int)$_GET['id'];
$msg = '';
$err = '';

// Fetch student details
$stmt = $conn->prepare("SELECT name, reg_no, base_fee, monthly_discount FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: students.php");
    exit();
}

// Handle Add Addon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_addon'])) {
    $addon_name = trim($_POST['addon_name']);
    $amount = (float)$_POST['amount'];
    
    if (!empty($addon_name) && $amount > 0) {
        $add_stmt = $conn->prepare("INSERT INTO student_addons (student_id, addon_name, amount) VALUES (?, ?, ?)");
        $add_stmt->bind_param("isd", $student_id, $addon_name, $amount);
        if ($add_stmt->execute()) {
            $msg = "Add-on successfully added.";
        } else {
            $err = "Error adding add-on.";
        }
    } else {
        $err = "Please provide a valid name and amount greater than 0.";
    }
}

// Handle Delete Addon
if (isset($_GET['delete'])) {
    $addon_id = (int)$_GET['delete'];
    $del_stmt = $conn->prepare("DELETE FROM student_addons WHERE id = ? AND student_id = ?");
    $del_stmt->bind_param("ii", $addon_id, $student_id);
    if ($del_stmt->execute()) {
        $msg = "Add-on removed.";
    }
}

// Fetch current addons
$addons_res = $conn->query("SELECT * FROM student_addons WHERE student_id = $student_id ORDER BY created_at DESC");
$total_addons = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Add-ons | ABSS</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .student-card { background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--portal-blue); }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        .inline-btn { width: auto !important; display: inline-block; padding: 8px 15px !important; font-size: 0.85rem !important; margin-right: 5px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="action-bar">
            <h1>Recurring Add-ons</h1>
            <a href="students.php" class="btn-portal inline-btn" style="background:#5c6bc0; text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Students</a>
        </div>

        <div class="student-card">
            <div>
                <h2 style="margin:0 0 5px;"><?php echo htmlspecialchars($student['name']); ?></h2>
                <p style="margin:0; font-size:0.9rem;"><?php echo htmlspecialchars($student['reg_no'] ?? ''); ?></p>
            </div>
            <div style="text-align:right;">
                <p style="margin:0; font-size:0.85rem; font-weight:800; text-transform:uppercase;">Base Fee</p>
                <h3 style="margin:0; color:#2e7d32;">₹<?php echo number_format($student['base_fee'], 2); ?></h3>
                <?php if($student['monthly_discount'] > 0): ?>
                    <p style="margin:0; font-size:0.8rem; color:#d32f2f;">- ₹<?php echo number_format($student['monthly_discount'], 2); ?> Discount</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#feeef2; color:#d32f2f; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:700;"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            <div class="portal-card" style="align-self: start;">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Add New Add-on</h3>
                <form action="student_addons.php?id=<?php echo $student_id; ?>" method="POST">
                    <div class="portal-input-group">
                        <label>Add-on Name</label>
                        <input type="text" name="addon_name" placeholder="e.g., Milk, Bus, Computer Fee" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Monthly Amount (₹)</label>
                        <input type="number" name="amount" placeholder="e.g. 500" step="0.01" required>
                    </div>
                    <button type="submit" name="add_addon" class="btn-portal w-100"><i class="fas fa-save"></i> Save Add-on</button>
                </form>
            </div>

            <div class="portal-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Add-on Name</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($addons_res->num_rows > 0): ?>
                            <?php while($row = $addons_res->fetch_assoc()): $total_addons += $row['amount']; ?>
                            <tr>
                                <td><strong style="color:var(--portal-blue);"><?php echo htmlspecialchars($row['addon_name']); ?></strong></td>
                                <td style="color:#2e7d32; font-weight:800;">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td>
                                    <a href="student_addons.php?id=<?php echo $student_id; ?>&delete=<?php echo $row['id']; ?>" class="btn-portal inline-btn" style="background:#d32f2f; text-decoration:none;" onclick="return confirm('Remove this add-on?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center;">No active add-ons for this student.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if($total_addons > 0): ?>
                <div style="background:#e8f5e9; padding:15px 25px; border-radius:15px; margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                    <strong style="color:#2e7d32; text-transform:uppercase; font-size:0.9rem;">Total Add-ons</strong>
                    <h3 style="margin:0; color:#2e7d32;">₹<?php echo number_format($total_addons, 2); ?></h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
