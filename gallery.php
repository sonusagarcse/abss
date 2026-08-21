<?php
// gallery.php - Official Public Campus Photo & Video Gallery for ABSS
require_once 'includes/security.php';
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();

$active_tab = $_GET['tab'] ?? 'photos';
$selected_cat = $_GET['cat'] ?? 'all';

// 1. Fetch Photos
if ($selected_cat !== 'all') {
    $stmt_p = $conn->prepare("SELECT * FROM gallery WHERE category = ? ORDER BY created_at DESC");
    $stmt_p->bind_param("s", $selected_cat);
    $stmt_p->execute();
    $photos_res = $stmt_p->get_result();
} else {
    $photos_res = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
}

$photos = [];
if ($photos_res && $photos_res->num_rows > 0) {
    while ($row = $photos_res->fetch_assoc()) {
        $photos[] = $row;
    }
}

// Fallback curated photos if gallery has few entries
$fallback_photos = [
    ['id' => 901, 'image_path' => 'assets/gallery/1776881454_1600.jpg', 'caption' => 'Competitive Entrance Coaching & Classroom Session', 'category' => 'Academics', 'created_at' => date('Y-m-d')],
    ['id' => 902, 'image_path' => 'assets/gallery/1780112974_9409.png', 'caption' => 'Morning Assembly & Physical Fitness Training', 'category' => 'Campus Life', 'created_at' => date('Y-m-d')],
    ['id' => 903, 'image_path' => 'assets/gallery/1787299395_6788.png', 'caption' => 'OMR Weekly Test Series & Merit Evaluation', 'category' => 'Examinations', 'created_at' => date('Y-m-d')],
    ['id' => 904, 'image_path' => 'assets/gallery/1787299429_3090.png', 'caption' => 'Annual Merit Awards & Scholar Felicitation', 'category' => 'Excellence', 'created_at' => date('Y-m-d')],
    ['id' => 905, 'image_path' => 'images/home.jpeg', 'caption' => 'ABSS Campus Entrance & Administrative Block', 'category' => 'Campus Life', 'created_at' => date('Y-m-d')],
    ['id' => 906, 'image_path' => 'assets/achievers/1776881528_9857.jpg', 'caption' => 'Netarhat & Navodaya Qualifying Scholars', 'category' => 'Achievers', 'created_at' => date('Y-m-d')],
    ['id' => 907, 'image_path' => 'assets/achievers/1776882403_7564.jpg', 'caption' => 'Sainik School & Simultala Selected Candidates', 'category' => 'Pride of ABSS', 'created_at' => date('Y-m-d')],
    ['id' => 908, 'image_path' => 'assets/Secratery.png', 'caption' => 'Mentorship & Student Career Counseling', 'category' => 'Mentorship', 'created_at' => date('Y-m-d')]
];

if (empty($photos) || count($photos) < 8) {
    foreach ($fallback_photos as $fb) {
        $exists = false;
        foreach ($photos as $p) {
            if ($p['image_path'] === $fb['image_path']) {
                $exists = true;
                break;
            }
        }
        if (!$exists && file_exists(__DIR__ . '/' . $fb['image_path'])) {
            if ($selected_cat === 'all' || $fb['category'] === $selected_cat) {
                $photos[] = $fb;
            }
        }
    }
}

// 2. Fetch YouTube Videos
if ($selected_cat !== 'all') {
    $stmt_v = $conn->prepare("SELECT * FROM youtube_videos WHERE status = 1 AND category = ? ORDER BY created_at DESC");
    $stmt_v->bind_param("s", $selected_cat);
    $stmt_v->execute();
    $videos_res = $stmt_v->get_result();
} else {
    $videos_res = $conn->query("SELECT * FROM youtube_videos WHERE status = 1 ORDER BY created_at DESC");
}

$videos = [];
if ($videos_res && $videos_res->num_rows > 0) {
    while ($row = $videos_res->fetch_assoc()) {
        $videos[] = $row;
    }
}

// Total Counts
$total_photos_count = count($photos);
$total_videos_count = count($videos);

// Get distinct categories from database & fallbacks
$photo_cats_res = $conn->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL AND category != ''");
$video_cats_res = $conn->query("SELECT DISTINCT category FROM youtube_videos WHERE status = 1 AND category IS NOT NULL AND category != ''");

$categories = [
    'Academics' => true,
    'Campus Life' => true,
    'Examinations' => true,
    'Achievers' => true,
    'Campus Events' => true
];

if ($photo_cats_res) {
    while($r = $photo_cats_res->fetch_assoc()) {
        $categories[$r['category']] = true;
    }
}
if ($video_cats_res) {
    while($r = $video_cats_res->fetch_assoc()) {
        $categories[$r['category']] = true;
    }
}
$cat_list = array_keys($categories);

$page_title = "Campus Gallery & Videos | Awasiya Bal Shikshan Sansthan (ABSS) Imamganj";
include 'includes/header.php';
?>

<!-- Page Hero Header -->
<section style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e40af 100%); padding: 70px 0 50px 0; color: #ffffff; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -20%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(56,189,248,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;"></div>
    
    <div class="container" style="position: relative; z-index: 2; text-align: center;">
        <span style="display: inline-flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8; padding: 6px 18px; border-radius: 50px; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px;">
            <i class="fas fa-photo-video"></i> Life at ABSS Imamganj
        </span>
        <h1 style="font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 900; margin: 0 0 14px 0; line-height: 1.15; color: #ffffff;">
            Campus Moments & Video Gallery
        </h1>
        <p style="color: #cbd5e1; font-size: 1.05rem; max-width: 680px; margin: 0 auto; font-weight: 500; line-height: 1.6;">
            Explore our state-of-the-art residential campus, classroom coaching sessions, weekly testing drills, celebrations, and academic triumphs.
        </p>
    </div>
</section>

<!-- Main Gallery Content Section -->
<section style="padding: 60px 0 90px 0; background: #f8fafc; min-height: 60vh;">
    <div class="container">

        <!-- Dual Media Tabs Switcher -->
        <div class="media-tabs-bar">
            <a href="gallery.php?tab=photos&cat=<?= urlencode($selected_cat) ?>" class="media-tab-btn <?= $active_tab === 'photos' ? 'active' : '' ?>">
                <i class="fas fa-images"></i> Photo Gallery (<?= $total_photos_count ?>)
            </a>
            <a href="gallery.php?tab=videos&cat=<?= urlencode($selected_cat) ?>" class="media-tab-btn <?= $active_tab === 'videos' ? 'active' : '' ?>">
                <i class="fab fa-youtube" style="color: <?= $active_tab === 'videos' ? '#fff' : '#ef4444' ?>;"></i> Video Player (<?= $total_videos_count ?>)
            </a>
        </div>

        <!-- Category Filter Pills Bar -->
        <div class="category-pills-bar">
            <a href="gallery.php?tab=<?= $active_tab ?>&cat=all" class="cat-pill <?= $selected_cat === 'all' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> All Media
            </a>
            <?php foreach ($cat_list as $catName): ?>
                <a href="gallery.php?tab=<?= $active_tab ?>&cat=<?= urlencode($catName) ?>" class="cat-pill <?= $selected_cat === $catName ? 'active' : '' ?>">
                    <?= htmlspecialchars($catName) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- TAB 1: PHOTO GALLERY -->
        <?php if ($active_tab === 'photos'): ?>
            <?php if (!empty($photos)): ?>
                <div class="public-gallery-grid">
                    <?php foreach ($photos as $p): ?>
                        <div class="gallery-photo-card">
                            <a href="<?= htmlspecialchars($p['image_path']) ?>" class="glightbox" data-gallery="campus-public" data-title="<?= htmlspecialchars($p['caption'] ?: 'Campus Life at ABSS') ?>" data-description="<?= htmlspecialchars($p['category'] ?? 'Campus Activity') ?>">
                                <div class="gallery-img-container">
                                    <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['caption']) ?>" loading="lazy">
                                    <div class="zoom-overlay">
                                        <div class="zoom-icon"><i class="fas fa-search-plus"></i></div>
                                    </div>
                                </div>
                                <div class="gallery-meta">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <span class="cat-badge"><?= htmlspecialchars($p['category'] ?? 'Campus Life') ?></span>
                                        <?php if (!empty($p['created_at'])): ?>
                                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?= date('d M Y', strtotime($p['created_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h4><?= htmlspecialchars($p['caption'] ?: 'Life at ABSS Campus') ?></h4>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-media-box">
                    <i class="fas fa-camera-retro" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: #334155; margin-bottom: 6px;">No Photos Found</h3>
                    <p style="color: #64748b; font-size: 0.9rem;">There are no photos in this category yet. Check back soon!</p>
                </div>
            <?php endif; ?>

        <!-- TAB 2: VIDEO GALLERY -->
        <?php else: ?>
            <?php if (!empty($videos)): ?>
                <div class="public-video-grid">
                    <?php foreach ($videos as $v): 
                        $thumb = !empty($v['thumbnail_url']) ? $v['thumbnail_url'] : 'https://img.youtube.com/vi/' . htmlspecialchars($v['youtube_id']) . '/hqdefault.jpg';
                    ?>
                        <div class="video-card" onclick="openPublicVideoModal('<?= htmlspecialchars($v['youtube_id']) ?>', '<?= htmlspecialchars(addslashes($v['title'])) ?>', '<?= htmlspecialchars(addslashes($v['description'] ?? '')) ?>')">
                            <div class="video-thumb-container">
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($v['title']) ?>" loading="lazy">
                                <div class="play-btn-overlay">
                                    <div class="play-btn-circle">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                                <?php if (!empty($v['duration'])): ?>
                                    <span class="video-duration-badge"><?= htmlspecialchars($v['duration']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="video-meta">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span class="cat-badge" style="background: #fee2e2; color: #dc2626;"><?= htmlspecialchars($v['category'] ?? 'Campus Tour') ?></span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;"><?= date('d M Y', strtotime($v['created_at'])) ?></span>
                                </div>
                                <h4><?= htmlspecialchars($v['title']) ?></h4>
                                <?php if (!empty($v['description'])): ?>
                                    <p><?= htmlspecialchars($v['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-media-box">
                    <i class="fab fa-youtube" style="font-size: 3.5rem; color: #fca5a5; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: #334155; margin-bottom: 6px;">No Videos Available</h3>
                    <p style="color: #64748b; font-size: 0.9rem;">No videos have been uploaded in this category yet. Stay tuned for campus recordings and events!</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<!-- Video Player Modal -->
<div class="video-modal-overlay" id="publicVideoModal">
    <div class="video-modal-card">
        <div class="modal-header-bar">
            <h3 id="pubVidTitle" style="margin:0; font-size:1.1rem; color:#fff; font-weight:800;"></h3>
            <button type="button" class="close-modal-btn" onclick="closePublicVideoModal()" aria-label="Close Video">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="video-iframe-wrapper">
            <iframe id="pubVidIframe" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div style="padding: 16px 20px; background: #0f172a;">
            <p id="pubVidDesc" style="color: #94a3b8; font-size: 0.88rem; margin: 0; line-height: 1.5;"></p>
        </div>
    </div>
</div>

<style>
    /* Tabs & Categories */
    .media-tabs-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .media-tab-btn {
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #e2e8f0;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .media-tab-btn:hover {
        color: #2563eb;
        border-color: #93c5fd;
        background: #eff6ff;
    }
    .media-tab-btn.active {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
    }

    .category-pills-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 12px;
        margin-bottom: 30px;
        scrollbar-width: thin;
    }
    .cat-pill {
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 7px 18px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .cat-pill:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .cat-pill.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    }

    /* Photo Grid */
    .public-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }
    .gallery-photo-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .gallery-photo-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .gallery-photo-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.12);
    }
    .gallery-img-container {
        height: 220px;
        position: relative;
        overflow: hidden;
        background: #0f172a;
    }
    .gallery-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .gallery-photo-card:hover .gallery-img-container img {
        transform: scale(1.08);
    }
    .zoom-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .gallery-photo-card:hover .zoom-overlay {
        opacity: 1;
    }
    .zoom-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .gallery-meta {
        padding: 16px 18px;
    }
    .gallery-meta h4 {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.35;
    }
    .cat-badge {
        display: inline-block;
        background: #eff6ff;
        color: #2563eb;
        font-size: 0.72rem;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Video Grid */
    .public-video-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
    }
    .video-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .video-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(220, 38, 38, 0.12);
    }
    .video-thumb-container {
        height: 200px;
        position: relative;
        overflow: hidden;
        background: #0f172a;
    }
    .video-thumb-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .video-card:hover .video-thumb-container img {
        transform: scale(1.06);
    }
    .play-btn-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.25s ease;
    }
    .video-card:hover .play-btn-overlay {
        background: rgba(15, 23, 42, 0.5);
    }
    .play-btn-circle {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #dc2626;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
        transition: transform 0.25s ease;
        padding-left: 4px;
    }
    .video-card:hover .play-btn-circle {
        transform: scale(1.15);
    }
    .video-duration-badge {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(15, 23, 42, 0.85);
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.72rem;
        font-weight: 800;
    }
    .video-meta {
        padding: 16px 18px;
    }
    .video-meta h4 {
        margin: 0 0 6px 0;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.35;
    }
    .video-meta p {
        margin: 0;
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .empty-media-box {
        text-align: center;
        background: #ffffff;
        border-radius: 24px;
        padding: 60px 20px;
        border: 1px dashed #cbd5e1;
        max-width: 500px;
        margin: 40px auto;
    }

    /* Video Player Modal */
    .video-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 99999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .video-modal-card {
        background: #0f172a;
        border-radius: 20px;
        overflow: hidden;
        width: 100%;
        max-width: 800px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.1);
    }
    .modal-header-bar {
        padding: 14px 20px;
        background: #1e293b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .close-modal-btn {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.25rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .close-modal-btn:hover {
        color: #ffffff;
    }
    .video-iframe-wrapper {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        background: #000;
    }
    .video-iframe-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>

<script>
    function openPublicVideoModal(videoId, title, desc) {
        document.getElementById('pubVidTitle').innerText = title;
        document.getElementById('pubVidDesc').innerText = desc;
        document.getElementById('pubVidIframe').src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
        document.getElementById('publicVideoModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePublicVideoModal() {
        document.getElementById('pubVidIframe').src = '';
        document.getElementById('publicVideoModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.getElementById('publicVideoModal')?.addEventListener('click', function(e) {
        if (e.target === this) closePublicVideoModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePublicVideoModal();
    });
</script>

<?php include 'includes/footer.php'; ?>
