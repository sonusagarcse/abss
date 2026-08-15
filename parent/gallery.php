<?php
// parent/gallery.php - Parent Portal School Photo Gallery & YouTube Video Player
require_once 'includes/auth.php';

$active_tab = $_GET['tab'] ?? 'photos';
$selected_cat = $_GET['cat'] ?? 'all';

// Fetch Photos
if ($selected_cat !== 'all') {
    $stmt_p = $conn->prepare("SELECT * FROM gallery WHERE category = ? ORDER BY created_at DESC");
    $stmt_p->bind_param("s", $selected_cat);
    $stmt_p->execute();
    $photos_res = $stmt_p->get_result();
} else {
    $photos_res = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
}

// Fetch YouTube Videos
if ($selected_cat !== 'all') {
    $stmt_v = $conn->prepare("SELECT * FROM youtube_videos WHERE status = 1 AND category = ? ORDER BY created_at DESC");
    $stmt_v->bind_param("s", $selected_cat);
    $stmt_v->execute();
    $videos_res = $stmt_v->get_result();
} else {
    $videos_res = $conn->query("SELECT * FROM youtube_videos WHERE status = 1 ORDER BY created_at DESC");
}

// Total Counts
$total_photos_count = $conn->query("SELECT COUNT(*) as c FROM gallery")->fetch_assoc()['c'] ?? 0;
$total_videos_count = $conn->query("SELECT COUNT(*) as c FROM youtube_videos WHERE status = 1")->fetch_assoc()['c'] ?? 0;

// Get distinct categories
$photo_cats_res = $conn->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL AND category != ''");
$video_cats_res = $conn->query("SELECT DISTINCT category FROM youtube_videos WHERE status = 1 AND category IS NOT NULL AND category != ''");

$categories = [];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery & Video Player | ABSS Parent Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .hero-media-header {
            background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 60%, #7c3aed 100%);
            border-radius: var(--radius-lg);
            padding: 35px 30px;
            color: #ffffff;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(109, 40, 217, 0.2);
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .media-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 2px solid #ede9fe;
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        .media-tab-btn {
            background: #ffffff;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.25s ease;
        }
        .media-tab-btn:hover { color: var(--portal-purple); border-color: var(--portal-purple); background: #f5f3ff; }
        .media-tab-btn.active {
            background: linear-gradient(135deg, var(--portal-purple), var(--portal-purple-dark));
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.3);
        }

        /* Category Filter Pills */
        .category-pills-bar {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 25px;
            scrollbar-width: thin;
        }
        .cat-pill {
            background: #ffffff;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .cat-pill:hover, .cat-pill.active {
            background: #ede9fe;
            color: var(--portal-purple);
            border-color: var(--portal-purple);
        }
        .cat-pill.active { font-weight: 800; }

        /* Photo Gallery Grid */
        .parent-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 22px;
        }
        .gallery-photo-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }
        .gallery-photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(124, 58, 237, 0.12);
        }
        .gallery-img-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f1f5f9;
            position: relative;
        }
        .gallery-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .gallery-photo-card:hover .gallery-img-container img {
            transform: scale(1.08);
        }
        .zoom-overlay {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.25s ease;
            color: #fff;
            font-size: 1.6rem;
        }
        .gallery-photo-card:hover .zoom-overlay { opacity: 1; }
        .gallery-meta { padding: 16px 18px; }
        .gallery-meta h4 { margin: 0 0 6px 0; font-size: 0.95rem; font-weight: 800; color: var(--portal-indigo); line-height: 1.3; }
        .badge-purple { padding: 3px 10px; border-radius: 50px; background: #ede9fe; color: var(--portal-purple); font-size: 0.72rem; font-weight: 800; display: inline-block; }

        /* Video Gallery Grid */
        .parent-video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .parent-video-card {
            background: #ffffff;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid #ede9fe;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .parent-video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(220, 38, 38, 0.12);
        }
        .video-thumb-holder {
            position: relative;
            width: 100%;
            height: 190px;
            background: #0f172a;
            overflow: hidden;
            cursor: pointer;
        }
        .video-thumb-holder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .parent-video-card:hover .video-thumb-holder img {
            transform: scale(1.06);
        }
        .parent-play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.95);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.5);
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .video-thumb-holder:hover .parent-play-btn {
            transform: translate(-50%, -50%) scale(1.15);
            background: #ef4444;
        }
        .video-details { padding: 18px 20px; }
        .video-details h4 { margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.35; }
        .video-details p { margin: 0 0 12px 0; color: #64748b; font-size: 0.85rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Full Screen Modals */
        .media-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(8px);
            z-index: 999999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .media-modal.active { display: flex; }
        .modal-box {
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            width: 100%;
            max-width: 860px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            position: relative;
        }
        .modal-close-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.6);
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            z-index: 20;
            transition: all 0.2s ease;
        }
        .modal-close-icon:hover { background: #ef4444; transform: scale(1.1); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        
        <!-- HERO BANNER -->
        <div class="hero-media-header">
            <div>
                <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; background:rgba(255,255,255,0.2); padding:5px 14px; border-radius:50px; display:inline-block; margin-bottom:10px;">
                    <i class="fas fa-camera-retro"></i> Life at ABSS School
                </span>
                <h1 style="margin:0 0 6px 0; font-size:1.85rem; font-weight:900; color:#fff;">Campus Gallery & Video Player</h1>
                <p style="margin:0; color:#e9d5ff; font-size:0.95rem; max-width:600px;">Explore high-definition photos and YouTube video highlights of your child's academic events, sports meets, and classroom achievements.</p>
            </div>
            <div style="display:flex; gap:12px;">
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); padding:12px 20px; border-radius:18px; text-align:center; border:1px solid rgba(255,255,255,0.2);">
                    <div style="font-size:1.5rem; font-weight:900;"><?php echo $total_photos_count; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; opacity:0.85;">Photos</div>
                </div>
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); padding:12px 20px; border-radius:18px; text-align:center; border:1px solid rgba(255,255,255,0.2);">
                    <div style="font-size:1.5rem; font-weight:900;"><?php echo $total_videos_count; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; font-weight:700; opacity:0.85;">Videos</div>
                </div>
            </div>
        </div>

        <!-- TAB SWITCHER -->
        <div class="media-tabs">
            <a href="gallery.php?tab=photos&cat=<?php echo urlencode($selected_cat); ?>" class="media-tab-btn <?php echo $active_tab === 'photos' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Photo Gallery (<?php echo $total_photos_count; ?>)
            </a>
            <a href="gallery.php?tab=videos&cat=<?php echo urlencode($selected_cat); ?>" class="media-tab-btn <?php echo $active_tab === 'videos' ? 'active' : ''; ?>">
                <i class="fab fa-youtube" style="color:<?php echo $active_tab === 'videos' ? '#fff' : '#dc2626'; ?>;"></i> YouTube Video Player (<?php echo $total_videos_count; ?>)
            </a>
        </div>

        <!-- CATEGORY FILTER PILLS -->
        <div class="category-pills-bar">
            <a href="gallery.php?tab=<?php echo $active_tab; ?>&cat=all" class="cat-pill <?php echo $selected_cat === 'all' ? 'active' : ''; ?>">
                All Categories
            </a>
            <?php foreach($cat_list as $catName): ?>
                <a href="gallery.php?tab=<?php echo $active_tab; ?>&cat=<?php echo urlencode($catName); ?>" class="cat-pill <?php echo $selected_cat === $catName ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($catName); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- PHOTOS TAB CONTENT -->
        <?php if ($active_tab === 'photos'): ?>
            <div class="parent-gallery-grid">
                <?php if ($photos_res && $photos_res->num_rows > 0): ?>
                    <?php while($p = $photos_res->fetch_assoc()): ?>
                        <div class="gallery-photo-card" onclick="openPhotoModal('../<?php echo htmlspecialchars($p['image_path']); ?>', '<?php echo htmlspecialchars(addslashes($p['caption'])); ?>', '<?php echo htmlspecialchars($p['category'] ?? 'General'); ?>', '<?php echo date('d M Y', strtotime($p['created_at'])); ?>')">
                            <div class="gallery-img-container">
                                <img src="../<?php echo htmlspecialchars($p['image_path']); ?>" alt="<?php echo htmlspecialchars($p['caption']); ?>" loading="lazy">
                                <div class="zoom-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                            <div class="gallery-meta">
                                <span class="badge-purple"><?php echo htmlspecialchars($p['category'] ?? 'General'); ?></span>
                                <h4><?php echo htmlspecialchars($p['caption']); ?></h4>
                                <small style="color:#94a3b8; font-weight:600;"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($p['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1; background:#fff; padding:60px 20px; border-radius:24px; text-align:center; border:1px solid #ede9fe;">
                        <i class="fas fa-images" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px; display:block;"></i>
                        <h3 style="margin:0 0 6px 0; color:var(--portal-indigo);">No Photos Found</h3>
                        <p style="margin:0; color:#64748b;">No pictures found in the selected category.</p>
                    </div>
                <?php endif; ?>
            </div>

        <!-- VIDEOS TAB CONTENT -->
        <?php else: ?>
            <div class="parent-video-grid">
                <?php if ($videos_res && $videos_res->num_rows > 0): ?>
                    <?php while($v = $videos_res->fetch_assoc()): ?>
                        <div class="parent-video-card">
                            <div class="video-thumb-holder" onclick="openParentVideoPlayer('<?php echo htmlspecialchars($v['youtube_id']); ?>', '<?php echo htmlspecialchars(addslashes($v['title'])); ?>')">
                                <img src="<?php echo htmlspecialchars($v['thumbnail_url'] ?: 'https://img.youtube.com/vi/' . $v['youtube_id'] . '/hqdefault.jpg'); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>" loading="lazy">
                                <div class="parent-play-btn">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="video-details">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                    <span class="badge-purple" style="background:#fee2e2; color:#dc2626;"><?php echo htmlspecialchars($v['category'] ?? 'Campus Life'); ?></span>
                                    <small style="color:#94a3b8; font-weight:700; font-size:0.75rem;"><i class="fab fa-youtube" style="color:#dc2626;"></i> YouTube Stream</small>
                                </div>
                                <h4><?php echo htmlspecialchars($v['title']); ?></h4>
                                <?php if(!empty($v['description'])): ?>
                                    <p><?php echo htmlspecialchars($v['description']); ?></p>
                                <?php endif; ?>
                                <button type="button" class="btn-portal" style="width:100%; padding:10px; font-size:0.88rem; background:linear-gradient(135deg, #dc2626, #b91c1c); border-radius:12px;" onclick="openParentVideoPlayer('<?php echo htmlspecialchars($v['youtube_id']); ?>', '<?php echo htmlspecialchars(addslashes($v['title'])); ?>')">
                                    <i class="fas fa-play-circle"></i> Play Video Clip
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column:1/-1; background:#fff; padding:60px 20px; border-radius:24px; text-align:center; border:1px solid #ede9fe;">
                        <i class="fab fa-youtube" style="font-size:3rem; color:#fca5a5; margin-bottom:15px; display:block;"></i>
                        <h3 style="margin:0 0 6px 0; color:var(--portal-indigo);">No YouTube Videos Available</h3>
                        <p style="margin:0; color:#64748b;">No video highlights found in the selected category.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- PHOTO LIGHTBOX MODAL -->
    <div id="photoLightboxModal" class="media-modal" onclick="closePhotoModal(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <button class="modal-close-icon" onclick="closePhotoModal()"><i class="fas fa-times"></i></button>
            <div style="background:#0f172a; max-height:75vh; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                <img id="lightboxImg" src="" alt="Enlarged View" style="max-width:100%; max-height:75vh; object-fit:contain;">
            </div>
            <div style="padding:20px 25px; background:#ffffff; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <span id="lightboxCat" class="badge-purple"></span>
                    <h3 id="lightboxCaption" style="margin:8px 0 2px 0; font-size:1.15rem; font-weight:800; color:var(--portal-indigo);"></h3>
                    <small id="lightboxDate" style="color:#94a3b8; font-weight:600;"></small>
                </div>
                <a id="lightboxDownloadBtn" href="" download class="btn-portal" style="width:auto; padding:10px 18px; font-size:0.85rem; background:#475569; text-decoration:none;">
                    <i class="fas fa-download"></i> Save Image
                </a>
            </div>
        </div>
    </div>

    <!-- YOUTUBE PLAYER MODAL -->
    <div id="parentYtModal" class="media-modal" onclick="closeParentVideoPlayer(event)">
        <div class="modal-box" style="background:#000; border-radius:24px; max-width:880px;" onclick="event.stopPropagation()">
            <button class="modal-close-icon" onclick="closeParentVideoPlayer()"><i class="fas fa-times"></i></button>
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden;">
                <iframe id="parentYtIframe" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>
            <div style="padding:18px 24px; background:#1e1b4b; color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
                <h4 id="parentYtTitle" style="margin:0; font-size:1.1rem; font-weight:800; color:#fff;"></h4>
            </div>
        </div>
    </div>

    <script>
        // Photo Modal Handler
        function openPhotoModal(imgSrc, caption, cat, dateStr) {
            const modal = document.getElementById('photoLightboxModal');
            const img = document.getElementById('lightboxImg');
            const cap = document.getElementById('lightboxCaption');
            const catEl = document.getElementById('lightboxCat');
            const dateEl = document.getElementById('lightboxDate');
            const dlBtn = document.getElementById('lightboxDownloadBtn');

            if (modal && img) {
                img.src = imgSrc;
                if (cap) cap.innerText = caption || 'School Event Photo';
                if (catEl) catEl.innerText = cat || 'Campus';
                if (dateEl) dateEl.innerText = dateStr ? 'Captured: ' + dateStr : '';
                if (dlBtn) dlBtn.href = imgSrc;
                modal.classList.add('active');
            }
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoLightboxModal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Video Player Modal Handler
        function openParentVideoPlayer(ytId, title) {
            const modal = document.getElementById('parentYtModal');
            const iframe = document.getElementById('parentYtIframe');
            const titleEl = document.getElementById('parentYtTitle');

            if (modal && iframe) {
                iframe.src = 'https://www.youtube-nocookie.com/embed/' + ytId + '?autoplay=1&rel=0';
                if (titleEl) titleEl.innerText = title || 'ABSS Video Stream';
                modal.classList.add('active');
            }
        }

        function closeParentVideoPlayer() {
            const modal = document.getElementById('parentYtModal');
            const iframe = document.getElementById('parentYtIframe');
            if (modal && iframe) {
                iframe.src = '';
                modal.classList.remove('active');
            }
        }

        // Escape Key to Close Modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
                closeParentVideoPlayer();
            }
        });
    </script>
</body>
</html>
