<?php
// admin/gallery.php - Photo Gallery & YouTube Video Player Manager
require_once 'includes/auth.php';

$msg = '';
$err = '';
$active_tab = $_GET['tab'] ?? 'photos';

// Function to Extract YouTube ID from any format
function extractYouTubeVideoId($url) {
    $url = trim($url);
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?\/ ]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return false;
}

// 1. Handle Photo Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
    $caption = trim($_POST['caption'] ?? '');
    $category = trim($_POST['category'] ?? 'Campus Events');
    $target_dir = "../assets/gallery/";
    
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (in_array($file_ext, $allowed)) {
        $file_name = time() . "_" . rand(1000, 9999) . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        $db_path = "assets/gallery/" . $file_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $db_path, $caption, $category);
            $stmt->execute();
            $stmt->close();

            // Create in-built portal notification for all parents
            if (function_exists('create_portal_notification')) {
                create_portal_notification(
                    'photo',
                    "New School Photo: " . ($category ?: 'Campus Gallery'),
                    $caption ? "New photo: \"$caption\"" : "New campus activities & event photos uploaded to the gallery.",
                    "gallery.php#photos",
                    null,
                    null,
                    'fa-camera-retro',
                    '#2563eb'
                );
            }

            $msg = "Photo uploaded and added to gallery successfully.";
            $active_tab = 'photos';
        } else {
            $err = "Sorry, there was an error uploading your file.";
        }
    } else {
        $err = "Invalid image format. Allowed: JPG, PNG, WEBP, GIF.";
    }
}

// 2. Handle YouTube Video Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_youtube_video'])) {
    $title       = trim($_POST['title'] ?? '');
    $video_url   = trim($_POST['video_url'] ?? '');
    $category    = trim($_POST['category'] ?? 'Campus Life');
    $description = trim($_POST['description'] ?? '');

    $yt_id = extractYouTubeVideoId($video_url);
    if (!$yt_id) {
        $err = "Invalid YouTube URL. Please enter a valid YouTube video or shorts link.";
        $active_tab = 'videos';
    } elseif (empty($title)) {
        $err = "Video Title is required.";
        $active_tab = 'videos';
    } else {
        $clean_url = "https://www.youtube.com/watch?v=" . $yt_id;
        $thumb_url = "https://img.youtube.com/vi/" . $yt_id . "/hqdefault.jpg";

        $stmt = $conn->prepare("INSERT INTO youtube_videos (title, video_url, youtube_id, thumbnail_url, category, description, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssss", $title, $clean_url, $yt_id, $thumb_url, $category, $description);
        if ($stmt->execute()) {
            // Create in-built portal notification for all parents
            if (function_exists('create_portal_notification')) {
                create_portal_notification(
                    'video',
                    "New Activity Video: " . $title,
                    $description ? (substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '')) : "Watch new classroom & event video highlights on the parent portal.",
                    "gallery.php?tab=videos&cat=all",
                    null,
                    null,
                    'fa-play-circle',
                    '#ea580c'
                );
            }

            $msg = "YouTube video added to portal successfully.";
            $active_tab = 'videos';
        } else {
            $err = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 3. Handle Photo Delete
if (isset($_GET['delete_photo'])) {
    $id = (int)$_GET['delete_photo'];
    $photo = $conn->query("SELECT image_path FROM gallery WHERE id = $id")->fetch_assoc();
    if ($photo) {
        $file_path = "../" . $photo['image_path'];
        if (file_exists($file_path)) @unlink($file_path);
        $conn->query("DELETE FROM gallery WHERE id = $id");
        $msg = "Photo deleted from gallery.";
    }
    header("Location: gallery.php?tab=photos&msg=" . urlencode($msg));
    exit();
}

// 4. Handle Video Delete
if (isset($_GET['delete_video'])) {
    $id = (int)$_GET['delete_video'];
    $conn->query("DELETE FROM youtube_videos WHERE id = $id");
    header("Location: gallery.php?tab=videos&msg=" . urlencode("Video removed from portal."));
    exit();
}

// 5. Handle Video Status Toggle
if (isset($_GET['toggle_video'])) {
    $id = (int)$_GET['toggle_video'];
    $conn->query("UPDATE youtube_videos SET status = IF(status = 1, 0, 1) WHERE id = $id");
    header("Location: gallery.php?tab=videos&msg=" . urlencode("Video visibility updated."));
    exit();
}

$photos = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
$videos = $conn->query("SELECT * FROM youtube_videos ORDER BY created_at DESC");

$total_photos = $photos ? $photos->num_rows : 0;
$total_videos = $videos ? $videos->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery & Video Player Management | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .nav-tabs-custom {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: #fff;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.92rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .tab-btn:hover { background: #eff6ff; color: var(--portal-blue); border-color: var(--portal-blue); }
        .tab-btn.active {
            background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        }

        .gallery-layout-2col {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 25px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .gallery-layout-2col { grid-template-columns: 1fr; }
        }

        .upload-section { 
            background: #fff; 
            padding: 28px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
            position: sticky;
            top: 20px;
        }

        .gallery-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
            gap: 20px; 
        }

        .photo-card { 
            background: #fff; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
            transition: all 0.25s ease; 
        }
        .photo-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
        .photo-card img { width: 100%; height: 180px; object-fit: cover; }
        .photo-info { padding: 14px 16px; }
        .photo-info p { margin: 0 0 6px 0; font-weight: 800; color: var(--portal-dark); font-size: 0.9rem; }
        .badge-cat { padding: 3px 8px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; background: #eff6ff; color: #2563eb; display: inline-block; }

        /* Video Card */
        .video-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: all 0.25s ease;
        }
        .video-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
        .video-thumb-wrap { position: relative; width: 100%; height: 170px; background: #0f172a; overflow: hidden; }
        .video-thumb-wrap img { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; }
        .play-overlay-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.9);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .play-overlay-btn:hover { transform: translate(-50%, -50%) scale(1.1); background: #dc2626; }
        .video-info { padding: 16px; }
        .video-info h4 { margin: 0 0 6px 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; line-height: 1.3; }
        .video-info p { margin: 0 0 10px 0; font-size: 0.8rem; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Video Modal */
        #ytPlayerModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        #ytPlayerModal.active { display: flex; }
        .modal-player-box {
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            position: relative;
        }
        .close-player-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            z-index: 10;
            transition: background 0.2s;
        }
        .close-player-btn:hover { background: #ef4444; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
            <div>
                <h1 style="margin:0 0 4px 0; font-size:1.8rem;">Media & Video Player Centre</h1>
                <p style="margin:0; color:#64748b; font-size:0.92rem;">Manage school campus photos and YouTube video streams displayed on the Parent Portal & Website.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <span class="badge-cat" style="padding:8px 16px; font-size:0.82rem;"><i class="fas fa-images"></i> <?php echo $total_photos; ?> Photos</span>
                <span class="badge-cat" style="padding:8px 16px; font-size:0.82rem; background:#fee2e2; color:#dc2626;"><i class="fab fa-youtube"></i> <?php echo $total_videos; ?> YouTube Videos</span>
            </div>
        </header>

        <?php if($msg || isset($_GET['msg'])): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg ?: $_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if($err): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #fca5a5;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <!-- TAB NAVIGATION -->
        <div class="nav-tabs-custom">
            <a href="?tab=photos" class="tab-btn <?php echo $active_tab === 'photos' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Photo Gallery (<?php echo $total_photos; ?>)
            </a>
            <a href="?tab=videos" class="tab-btn <?php echo $active_tab === 'videos' ? 'active' : ''; ?>">
                <i class="fab fa-youtube" style="color:<?php echo $active_tab === 'videos' ? '#fff' : '#dc2626'; ?>;"></i> YouTube Video Player (<?php echo $total_videos; ?>)
            </a>
        </div>

        <?php if ($active_tab === 'photos'): ?>
            <!-- PHOTOS TAB VIEW -->
            <div class="gallery-layout-2col">
                
                <!-- LEFT COLUMN: PHOTO UPLOAD FORM -->
                <div class="upload-section">
                    <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:var(--portal-dark); font-weight:800; padding-bottom:10px; border-bottom:2px solid #f1f5f9;">
                        <i class="fas fa-cloud-upload-alt" style="color:var(--portal-blue);"></i> Upload New Photo
                    </h3>
                    
                    <form action="?tab=photos" method="POST" enctype="multipart/form-data">
                        <div class="portal-input-group">
                            <label>Select Image File</label>
                            <input type="file" name="photo" accept="image/*" required style="padding: 10px;">
                        </div>
                        <div class="portal-input-group">
                            <label>Event / Category</label>
                            <select name="category" class="form-control">
                                <option value="Campus Events">Campus Events</option>
                                <option value="Sports & Athletics">Sports & Athletics</option>
                                <option value="Classroom & Labs">Classroom & Labs</option>
                                <option value="Hostel & Dining">Hostel & Dining</option>
                                <option value="Annual Function">Annual Function</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Image Caption</label>
                            <input type="text" name="caption" placeholder="Short description..." required>
                        </div>
                        <button type="submit" name="upload_photo" class="btn-portal w-100" style="padding: 14px; font-weight:800; margin-top:10px; width:100%;">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>

                <!-- RIGHT COLUMN: PHOTOS GRID -->
                <div>
                    <div class="gallery-grid">
                        <?php if($photos && $photos->num_rows > 0): while($row = $photos->fetch_assoc()): ?>
                        <div class="photo-card">
                            <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image">
                            <div class="photo-info">
                                <span class="badge-cat"><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></span>
                                <p style="margin-top:6px;"><?php echo htmlspecialchars($row['caption']); ?></p>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                                    <small style="color:#94a3b8;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></small>
                                    <a href="?delete_photo=<?php echo $row['id']; ?>" style="color:#ef4444; font-weight:800; font-size:0.78rem; text-decoration:none;" onclick="return confirm('Delete this image?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div style="grid-column:1/-1; background:#fff; padding:40px; border-radius:20px; text-align:center; color:#94a3b8; font-weight:600; border:1px solid #e2e8f0;">
                            <i class="fas fa-image" style="font-size:2.5rem; opacity:0.4; display:block; margin-bottom:12px;"></i>
                            No gallery photos uploaded yet. Use the upload panel on the left.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- YOUTUBE VIDEOS TAB VIEW -->
            <div class="gallery-layout-2col">
                
                <!-- LEFT COLUMN: ADD YOUTUBE VIDEO FORM -->
                <div class="upload-section">
                    <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:var(--portal-dark); font-weight:800; padding-bottom:10px; border-bottom:2px solid #f1f5f9;">
                        <i class="fab fa-youtube" style="color:#dc2626;"></i> Add YouTube Video
                    </h3>
                    
                    <form action="?tab=videos" method="POST">
                        <div class="portal-input-group">
                            <label>YouTube Video URL or Shorts Link *</label>
                            <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required oninput="previewYtId(this.value)">
                            <small style="color:#64748b; font-size:0.75rem; margin-top:3px; display:block;">Paste full YouTube video URL, short link, or Shorts URL.</small>
                        </div>
                        <div class="portal-input-group">
                            <label>Video Title *</label>
                            <input type="text" name="title" placeholder="e.g. Annual Day Cultural Fest 2026" required>
                        </div>
                        <div class="portal-input-group">
                            <label>Video Category</label>
                            <select name="category" class="form-control">
                                <option value="Campus Life">Campus Life</option>
                                <option value="Annual Function">Annual Function</option>
                                <option value="Sports Meet">Sports Meet</option>
                                <option value="Classroom & Science Fest">Classroom & Science Fest</option>
                                <option value="Hostel Life">Hostel Life</option>
                                <option value="Student Achievements">Student Achievements</option>
                            </select>
                        </div>
                        <div class="portal-input-group">
                            <label>Short Description (Optional)</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Highlights and notes about this video..."></textarea>
                        </div>
                        <button type="submit" name="add_youtube_video" class="btn-portal w-100" style="padding: 14px; font-weight:800; margin-top:10px; width:100%; background:linear-gradient(135deg, #dc2626, #b91c1c);">
                            <i class="fab fa-youtube"></i> Add Video to Portal
                        </button>
                    </form>
                </div>

                <!-- RIGHT COLUMN: YOUTUBE VIDEOS GRID -->
                <div>
                    <div class="gallery-grid">
                        <?php if($videos && $videos->num_rows > 0): while($v = $videos->fetch_assoc()): ?>
                        <div class="video-card">
                            <div class="video-thumb-wrap">
                                <img src="<?php echo htmlspecialchars($v['thumbnail_url'] ?: 'https://img.youtube.com/vi/' . $v['youtube_id'] . '/hqdefault.jpg'); ?>" alt="<?php echo htmlspecialchars($v['title']); ?>">
                                <div class="play-overlay-btn" onclick="openVideoPlayer('<?php echo htmlspecialchars($v['youtube_id']); ?>', '<?php echo htmlspecialchars(addslashes($v['title'])); ?>')">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="video-info">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span class="badge-cat" style="background:#fee2e2; color:#dc2626;"><?php echo htmlspecialchars($v['category']); ?></span>
                                    <?php if($v['status'] == 1): ?>
                                        <span style="font-size:0.72rem; color:#16a34a; font-weight:800;"><i class="fas fa-eye"></i> Visible</span>
                                    <?php else: ?>
                                        <span style="font-size:0.72rem; color:#94a3b8; font-weight:800;"><i class="fas fa-eye-slash"></i> Hidden</span>
                                    <?php endif; ?>
                                </div>
                                <h4><?php echo htmlspecialchars($v['title']); ?></h4>
                                <?php if(!empty($v['description'])): ?>
                                    <p><?php echo htmlspecialchars($v['description']); ?></p>
                                <?php endif; ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f1f5f9; padding-top:10px; margin-top:10px;">
                                    <button type="button" class="btn-portal" style="padding:5px 12px; font-size:0.78rem; width:auto; background:#dc2626;" onclick="openVideoPlayer('<?php echo htmlspecialchars($v['youtube_id']); ?>', '<?php echo htmlspecialchars(addslashes($v['title'])); ?>')">
                                        <i class="fas fa-play"></i> Watch
                                    </button>
                                    <div style="display:flex; gap:10px;">
                                        <a href="?tab=videos&toggle_video=<?php echo $v['id']; ?>" style="color:#64748b; font-weight:800; font-size:0.78rem; text-decoration:none;">
                                            <?php echo $v['status'] == 1 ? 'Hide' : 'Show'; ?>
                                        </a>
                                        <a href="?tab=videos&delete_video=<?php echo $v['id']; ?>" style="color:#ef4444; font-weight:800; font-size:0.78rem; text-decoration:none;" onclick="return confirm('Remove this video?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <div style="grid-column:1/-1; background:#fff; padding:40px; border-radius:20px; text-align:center; color:#94a3b8; font-weight:600; border:1px solid #e2e8f0;">
                            <i class="fab fa-youtube" style="font-size:2.5rem; color:#dc2626; opacity:0.4; display:block; margin-bottom:12px;"></i>
                            No YouTube videos added yet. Paste a YouTube link on the left to add school clips.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- INTERACTIVE YOUTUBE PLAYER MODAL -->
    <div id="ytPlayerModal" onclick="closeVideoPlayer(event)">
        <div class="modal-player-box" onclick="event.stopPropagation()">
            <button class="close-player-btn" onclick="closeVideoPlayer()"><i class="fas fa-times"></i></button>
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden;">
                <iframe id="ytIframe" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>
            <div style="padding:16px 20px; background:#1e293b; color:#fff;">
                <h4 id="ytModalTitle" style="margin:0; font-size:1.05rem; font-weight:800;"></h4>
            </div>
        </div>
    </div>

    <script>
        function openVideoPlayer(ytId, title) {
            const modal = document.getElementById('ytPlayerModal');
            const iframe = document.getElementById('ytIframe');
            const titleEl = document.getElementById('ytModalTitle');
            if (modal && iframe) {
                iframe.src = 'https://www.youtube-nocookie.com/embed/' + ytId + '?autoplay=1&rel=0';
                if (titleEl) titleEl.innerText = title || 'ABSS Video Highlight';
                modal.classList.add('active');
            }
        }

        function closeVideoPlayer(e) {
            const modal = document.getElementById('ytPlayerModal');
            const iframe = document.getElementById('ytIframe');
            if (modal && iframe) {
                iframe.src = '';
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
