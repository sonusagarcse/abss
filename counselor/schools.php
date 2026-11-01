<?php
// counselor/schools.php - Coaching Programs & Target Schools Catalog
require_once 'includes/auth.php';
require_counselor_module('schools');

$schools = $conn->query("SELECT * FROM schools WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaching Programs | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .schools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .school-card { background: #fff; border-radius: 18px; border: 1px solid #e2e8f0; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .s-icon { width: 48px; height: 48px; border-radius: 14px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 14px; }
        .badge-prog { display: inline-flex; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-graduation-cap" style="color: #4338ca;"></i> Target Coaching Programs
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Quick reference of all entrance coaching programs offered by ABSS.
                    </p>
                </div>
            </div>

            <?php if (!$schools || $schools->num_rows === 0): ?>
                <div class="portal-card" style="text-align: center; padding: 50px; color: #94a3b8;">
                    No coaching programs listed.
                </div>
            <?php else: ?>
                <div class="schools-grid">
                    <?php while ($sc = $schools->fetch_assoc()): ?>
                        <div class="school-card">
                            <div>
                                <div class="s-icon">
                                    <i class="<?php echo htmlspecialchars($sc['icon'] ?: 'fas fa-school'); ?>"></i>
                                </div>
                                <span class="badge-prog">
                                    <?php echo htmlspecialchars($sc['badge_text'] ?: 'Entrance Program'); ?>
                                </span>
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
                                    <?php echo htmlspecialchars($sc['school_name']); ?>
                                </h3>
                                <p style="font-size: 0.86rem; color: #64748b; line-height: 1.5; margin: 0;">
                                    <?php echo htmlspecialchars($sc['description']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>
