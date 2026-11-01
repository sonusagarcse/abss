<?php
// counselor/achievers.php - Hall of Excellence & Top Rankers
require_once 'includes/auth.php';
require_counselor_module('achievers');

$achievers = $conn->query("SELECT * FROM achievers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Excellence | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .achievers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px; }
        .achiever-card { background: #fff; border-radius: 18px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); text-align: center; padding-bottom: 18px; }
        .achiever-img { width: 100%; height: 200px; object-fit: cover; background: #f1f5f9; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-trophy" style="color: #eab308;"></i> Hall of Excellence
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Selected students and exam rankers to showcase during counseling sessions.
                    </p>
                </div>
            </div>

            <?php if (!$achievers || $achievers->num_rows === 0): ?>
                <div class="portal-card" style="text-align: center; padding: 50px; color: #94a3b8;">
                    No achiever records found.
                </div>
            <?php else: ?>
                <div class="achievers-grid">
                    <?php while ($ach = $achievers->fetch_assoc()): ?>
                        <div class="achiever-card">
                            <?php if (!empty($ach['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($ach['image_path']); ?>" alt="<?php echo htmlspecialchars($ach['name']); ?>" class="achiever-img">
                            <?php else: ?>
                                <div class="achiever-img" style="display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #cbd5e1;">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 16px 14px 0;">
                                <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                                    <?php echo htmlspecialchars($ach['name']); ?>
                                </h3>
                                <div style="color: #0d9488; font-weight: 700; font-size: 0.82rem;">
                                    <?php echo htmlspecialchars($ach['target_school']); ?>
                                </div>
                                <span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 50px; font-size: 0.72rem; font-weight: 800; margin-top: 8px;">
                                    Batch: <?php echo htmlspecialchars($ach['batch_year'] ?: '2025-2026'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>

