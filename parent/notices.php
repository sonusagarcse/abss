<?php
// parent/notices.php - Parent Announcements Board

require_once 'includes/auth.php';

// Fetch all active notices
$notices = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notice-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 30px; margin-top: 20px; }
        .notice-card { background: #fff; padding: 35px; border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); border: 1px solid #f0f4f8; position: relative; transition: 0.3s; }
        .notice-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(26,35,126,0.04); }
        .notice-type { position: absolute; top: 35px; right: 35px; padding: 6px 15px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .type-info { background: #eef2ff; color: var(--portal-purple); }
        .type-important { background: #feeef2; color: #d32f2f; }
        .type-event { background: #f0fdf4; color: #2e7d32; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 40px;">
            <h1>Notice Board</h1>
            <p>Official broadcasts, scheduled events, and academic notices for parents.</p>
        </header>

        <div class="notice-grid">
            <?php if ($notices->num_rows == 0): ?>
                <div class="portal-card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                    <i class="fas fa-bullhorn" style="font-size: 3rem; color: #9aa5ce; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Notice Board is Empty</h3>
                    <p style="margin:0;">No official announcements have been published yet.</p>
                </div>
            <?php else: ?>
                <?php while($row = $notices->fetch_assoc()): ?>
                <div class="notice-card">
                    <span class="notice-type type-<?php echo $row['type']; ?>"><?php echo $row['type']; ?></span>
                    <h3 style="margin-bottom: 20px; padding-right: 85px; font-size: 1.25rem; font-weight: 800; color: var(--portal-indigo);"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px; color: #5c6bc0; font-weight: 500;"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f4f8; padding-top: 20px;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: #9aa5ce;">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                        </span>
                        <span style="font-size:0.8rem; font-weight:700; color:var(--portal-purple);">
                            <i class="fas fa-check-double"></i> Verified Broadcast
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
