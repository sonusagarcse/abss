<?php
// counselor/gallery.php - Campus Media & Infrastructure Showcase
require_once 'includes/auth.php';
require_counselor_module('gallery');

$gallery = $conn->query("SELECT * FROM gallery ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Gallery | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .gallery-card { background: #fff; border-radius: 18px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .g-img { width: 100%; height: 210px; object-fit: cover; background: #f1f5f9; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fas fa-images" style="color: #0891b2;"></i> Campus Media &amp; Facility Gallery
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Showcase institute campus, classrooms, library, and hostel facilities to visiting families.
                    </p>
                </div>
            </div>

            <?php if (!$gallery || $gallery->num_rows === 0): ?>
                <div class="portal-card" style="text-align: center; padding: 50px; color: #94a3b8;">
                    No media gallery photos uploaded yet.
                </div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php while ($g = $gallery->fetch_assoc()): ?>
                        <div class="gallery-card">
                            <img src="../<?php echo htmlspecialchars($g['image_path']); ?>" alt="<?php echo htmlspecialchars($g['caption']); ?>" class="g-img">
                            <div style="padding: 14px 16px;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 50px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($g['category'] ?: 'Campus'); ?>
                                </span>
                                <h4 style="margin: 8px 0 0 0; font-size: 0.95rem; font-weight: 700; color: #0f172a;">
                                    <?php echo htmlspecialchars($g['caption'] ?: 'Campus Photograph'); ?>
                                </h4>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>

