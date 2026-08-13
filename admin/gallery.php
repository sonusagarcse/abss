<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo'])) {
    $caption = $_POST['caption'];
    $target_dir = "../assets/gallery/";
    
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $file_name = time() . "_" . rand(1000, 9999) . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "assets/gallery/" . $file_name;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO gallery (image_path, caption) VALUES (?, ?)");
        $stmt->bind_param("ss", $db_path, $caption);
        $stmt->execute();
        $msg = "Photo uploaded and added to gallery.";
    } else {
        $msg = "Sorry, there was an error uploading your file.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $photo = $conn->query("SELECT image_path FROM gallery WHERE id = $id")->fetch_assoc();
    if ($photo) {
        $file_path = "../" . $photo['image_path'];
        if (file_exists($file_path)) unlink($file_path);
        $conn->query("DELETE FROM gallery WHERE id = $id");
    }
    header("Location: gallery.php");
    exit();
}

$photos = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .gallery-layout-2col {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 25px;
            align-items: start;
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
        .photo-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .photo-card img { width: 100%; height: 180px; object-fit: cover; }
        .photo-info { padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .photo-info p { margin: 0; font-weight: 700; color: var(--portal-dark); font-size: 0.88rem; }
        .delete-btn { color: #dc2626; font-weight: 800; text-decoration: none; font-size: 0.78rem; }

        @media (max-width: 900px) {
            .gallery-layout-2col { grid-template-columns: 1fr; }
            .upload-section { position: static; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px;">
            <h1 style="margin:0 0 4px 0; font-size:1.8rem;">Digital Gallery</h1>
            <p style="margin:0; color:#64748b;">Manage school memories and event photos in 2-column view.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- 2-COLUMN LAYOUT -->
        <div class="gallery-layout-2col">
            
            <!-- LEFT COLUMN: UPLOAD FORM -->
            <div class="upload-section">
                <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:var(--portal-dark); font-weight:800; padding-bottom:10px; border-bottom:2px solid #f1f5f9;">
                    <i class="fas fa-cloud-upload-alt" style="color:var(--portal-blue);"></i> Upload Photo
                </h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="portal-input-group">
                        <label>Select Image File</label>
                        <input type="file" name="photo" accept="image/*" required style="padding: 10px;">
                    </div>
                    <div class="portal-input-group">
                        <label>Image Caption</label>
                        <input type="text" name="caption" placeholder="Short description..." required>
                    </div>
                    <button type="submit" class="btn-portal w-100" style="padding: 14px; font-weight:800; margin-top:10px;">
                        <i class="fas fa-upload"></i> Upload Image
                    </button>
                </form>
            </div>

            <!-- RIGHT COLUMN: GALLERY GRID -->
            <div>
                <div class="gallery-grid">
                    <?php if($photos && $photos->num_rows > 0): while($row = $photos->fetch_assoc()): ?>
                    <div class="photo-card">
                        <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" alt="Gallery Image">
                        <div class="photo-info">
                            <p><?php echo htmlspecialchars($row['caption']); ?></p>
                            <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this image?')">DELETE</a>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div style="grid-column:1/-1; background:#fff; padding:40px; border-radius:20px; text-align:center; color:#94a3b8; font-weight:600; border:1px solid #e2e8f0;">
                        No gallery photos uploaded yet. Use the upload panel on the left.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
