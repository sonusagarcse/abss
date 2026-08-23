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
    <title>Notice Board | ABSS Faculty Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notice-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 25px; 
            margin-top: 25px; 
        }
        .notice-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(16px);
            padding: 28px; 
            border-radius: var(--radius-lg); 
            border: 1px solid var(--glass-border); 
            box-shadow: var(--glass-shadow); 
            position: relative; 
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease; 
        }
        .notice-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.1); 
        }
        .notice-badge { 
            position: absolute; 
            top: 24px; 
            right: 24px; 
            padding: 4px 12px; 
            border-radius: 50px; 
            font-size: 0.72rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        .type-info { background: #e0e7ff; color: #3730a3; }
        .type-important { background: #fee2e2; color: #991b1b; }
        .type-event { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px;">
            <h1 style="font-size: 1.85rem; color: var(--teacher-dark); font-weight: 900; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bullhorn" style="color: var(--teacher-purple);"></i> Institutional Notice Board
            </h1>
            <p style="color: #64748b; margin-top: 4px; font-size: 0.95rem;">Official announcements, circulars, academic notices, and event updates.</p>
        </header>

        <div class="notice-grid">
            <?php if ($notices && $notices->num_rows > 0): ?>
                <?php while ($n = $notices->fetch_assoc()): 
                    $n_type = strtolower($n['type'] ?? 'info');
                    $badge_class = 'type-info';
                    if (strpos($n_type, 'important') !== false || strpos($n_type, 'urgent') !== false) {
                        $badge_class = 'type-important';
                    } elseif (strpos($n_type, 'event') !== false || strpos($n_type, 'activity') !== false) {
                        $badge_class = 'type-event';
                    }
                ?>
                    <div class="notice-card">
                        <span class="notice-badge <?= $badge_class ?>"><?= htmlspecialchars($n['type'] ?? 'General') ?></span>
                        <h3 style="color: #1e1b4b; font-size: 1.15rem; font-weight: 900; margin-bottom: 12px; padding-right: 80px; line-height: 1.3;">
                            <?= htmlspecialchars($n['title']) ?>
                        </h3>
                        <p style="color: #475569; font-size: 0.92rem; line-height: 1.65; margin-bottom: 22px; font-weight: 500;">
                            <?= nl2br(htmlspecialchars($n['content'])) ?>
                        </p>
                        <div style="border-top: 1px solid #ede9fe; padding-top: 14px; color: #94a3b8; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i class="far fa-calendar-alt" style="color: var(--teacher-purple);"></i> Posted on <?= date('M d, Y', strtotime($n['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(16px); padding: 45px; border-radius: var(--radius-lg); border: 1px solid var(--glass-border); text-align: center; color: #94a3b8;">
                    <i class="fas fa-bullhorn" style="font-size: 3rem; margin-bottom: 15px; color: #cbd5e1; display: block;"></i>
                    <h3 style="margin: 0 0 6px 0; color: #64748b; font-weight: 800;">No Announcements Yet</h3>
                    <p style="margin: 0; font-size: 0.9rem; font-weight: 600;">Check back later for newly published institutional circulars.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
