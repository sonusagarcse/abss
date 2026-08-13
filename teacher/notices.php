<?php
require_once 'includes/auth.php';

// Fetch all notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notice-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 25px; }
        .notice-card { background: #ffffff; padding: 28px; border-radius: 20px; border: 1px solid #ede9fe; box-shadow: 0 4px 15px rgba(0,0,0,0.02); position: relative; transition: transform 0.3s; }
        .notice-card:hover { transform: translateY(-4px); }
        .notice-badge { position: absolute; top: 24px; right: 24px; padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .type-info { background: #e0e7ff; color: #3730a3; }
        .type-important { background: #fee2e2; color: #991b1b; }
        .type-event { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <h1 style="font-size: 1.8rem; color: var(--teacher-dark); font-weight: 800;">School Notice Board</h1>
            <p style="color: #64748b; margin-top: 4px;">Official announcements for faculty, staff, and students.</p>
        </header>

        <div class="notice-grid">
            <?php if ($notices && $notices->num_rows > 0): ?>
                <?php while ($n = $notices->fetch_assoc()): ?>
                    <div class="notice-card">
                        <span class="notice-badge type-<?= htmlspecialchars($n['type'] ?? 'info') ?>"><?= htmlspecialchars($n['type'] ?? 'General') ?></span>
                        <h3 style="color: #1e1b4b; font-size: 1.1rem; margin-bottom: 12px; padding-right: 70px;"><?= htmlspecialchars($n['title']) ?></h3>
                        <p style="color: #475569; font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px;"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                        <div style="border-top: 1px solid #ede9fe; padding-top: 14px; color: #94a3b8; font-size: 0.8rem; font-weight: 600;">
                            <i class="far fa-calendar-alt"></i> Posted on <?= date('M d, Y', strtotime($n['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; background: white; padding: 40px; border-radius: 20px; text-align: center; color: #94a3b8;">
                    <i class="fas fa-bullhorn" style="font-size: 2.5rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                    <p>No active school notices published.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
