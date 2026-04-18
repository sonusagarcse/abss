<?php
require_once 'includes/auth.php';

// Handle Status Update
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    $conn->query("UPDATE inquiries SET status = '$status' WHERE id = $id");
    header("Location: inquiries.php");
    exit();
}

$inquiries = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .table-card { background: #fff; border-radius: 35px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        th { text-align: left; padding: 15px 25px; color: var(--portal-blue); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; font-size: 0.95rem; color: #5c6bc0; font-weight: 500; }
        td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
        td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }
        
        .status-badge { padding: 6px 14px; border-radius: 100px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-new { background: #eef2ff; color: #0d47a1; }
        .status-contacted { background: #fff7ed; color: #f97316; }
        .status-admitted { background: #f0fdf4; color: #22c55e; }

        .btn-sm-action { padding: 8px 12px; font-size: 0.75rem; border-radius: 10px; text-decoration: none; font-weight: 800; display: inline-block; margin-right: 5px; }
        .btn-mark { background: #f8faff; color: var(--portal-blue); border: 1px solid #eef2ff; }
        .btn-mark:hover { background: var(--portal-blue); color: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Lead Management</h1>
            <p>Monitor and track website inquiries.</p>
        </header>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name / Contact</th>
                        <th>Target School</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $inquiries->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="font-weight:800; color:var(--portal-blue);"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div style="font-size:0.8rem; opacity:0.7;"><?php echo htmlspecialchars($row['phone']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['target_school'] ?: 'General Inquiry'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php if($row['status'] == 'new'): ?>
                                <a href="?id=<?php echo $row['id']; ?>&status=contacted" class="btn-sm-action btn-mark">Mark Contacted</a>
                            <?php elseif($row['status'] == 'contacted'): ?>
                                <a href="?id=<?php echo $row['id']; ?>&status=admitted" class="btn-sm-action btn-mark" style="color:#22c55e;">Mark Admitted</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
