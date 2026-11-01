<?php
// counselor/syllabus.php - Academic Syllabus Reference Console
require_once 'includes/auth.php';
require_counselor_module('syllabus');

$cards = $conn->query("SELECT * FROM syllabus_cards ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Syllabus | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .syllabus-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .s-card { background: #fff; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .s-header { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
        .s-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-book-open" style="color: #2563eb;"></i> Academic Syllabus &amp; Curriculum
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Reference subject breakdowns and program curriculum during admission counseling.
                    </p>
                </div>
            </div>

            <?php if (!$cards || $cards->num_rows === 0): ?>
                <div class="portal-card" style="text-align: center; padding: 50px; color: #94a3b8;">
                    No syllabus cards configured.
                </div>
            <?php else: ?>
                <div class="syllabus-grid">
                    <?php while ($card = $cards->fetch_assoc()): ?>
                        <?php 
                        $color = $card['accent_color'] ?: '#2563eb';
                        $subjects = !empty($card['subjects_json']) ? json_decode($card['subjects_json'], true) : [];
                        ?>
                        <div class="s-card">
                            <div class="s-header">
                                <div class="s-icon" style="background: <?php echo $color; ?>15; color: <?php echo $color; ?>;">
                                    <i class="<?php echo htmlspecialchars($card['icon'] ?: 'fas fa-book'); ?>"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a;">
                                        <?php echo htmlspecialchars($card['title']); ?>
                                    </h3>
                                    <small style="color: #64748b; font-weight: 600;">
                                        <?php echo htmlspecialchars($card['subtitle']); ?>
                                    </small>
                                </div>
                            </div>
                            <p style="font-size: 0.86rem; color: #475569; line-height: 1.5; margin-bottom: 16px;">
                                <?php echo htmlspecialchars($card['overview']); ?>
                            </p>
                            <?php if (is_array($subjects) && !empty($subjects)): ?>
                                <div style="border-top: 1px solid #f1f5f9; padding-top: 12px;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Key Subjects / Modules:</span>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;">
                                        <?php foreach ($subjects as $sb): ?>
                                            <span style="background: #f1f5f9; color: #334155; padding: 3px 8px; border-radius: 6px; font-size: 0.76rem; font-weight: 700;">
                                                <?php echo htmlspecialchars(is_array($sb) ? ($sb['name'] ?? '') : $sb); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>

