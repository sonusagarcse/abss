<?php
// counselor/notices.php - Institutional Notice Board
require_once 'includes/auth.php';
require_counselor_module('notices');

$notices = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notices-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
        .notice-card { background: #fff; border-radius: 18px; border: 1px solid #e2e8f0; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .notice-type { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; }
        .type-important { background: #fee2e2; color: #991b1b; }
        .type-event { background: #fef3c7; color: #92400e; }
        .type-info { background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-bullhorn" style="color: #d97706;"></i> Institutional Notice Board
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Reference circulars, exam dates, schedules, and official advisories for counseling parents.
                    </p>
                </div>
            </div>

            <?php if (!$notices || $notices->num_rows === 0): ?>
                <div class="portal-card" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-bullhorn" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                    No active notices published at this moment.
                </div>
            <?php else: ?>
                <div class="notices-grid">
                    <?php while ($n = $notices->fetch_assoc()): ?>
                        <?php 
                        $t = $n['type'] ?: 'info';
                        $t_class = 'type-info';
                        if ($t === 'important') $t_class = 'type-important';
                        elseif ($t === 'event') $t_class = 'type-event';
                        ?>
                        <div class="notice-card">
                            <div>
                                <span class="notice-type <?php echo $t_class; ?>">
                                    <i class="fas fa-tag"></i> <?php echo ucfirst($t); ?>
                                </span>
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 10px 0;">
                                    <?php echo htmlspecialchars($n['title']); ?>
                                </h3>
                                <p style="color: #475569; font-size: 0.88rem; line-height: 1.5; margin: 0 0 16px 0;">
                                    <?php echo nl2br(htmlspecialchars($n['content'])); ?>
                                </p>
                            </div>
                            <div style="font-size: 0.78rem; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; align-items: center; gap: 6px;">
                                <i class="far fa-calendar-alt"></i> Posted on <?php echo date('d M Y', strtotime($n['created_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
