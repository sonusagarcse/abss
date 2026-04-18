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
        .upload-section { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 50px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        .photo-card { background: #fff; border-radius: 30px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f0f4f8; transition: 0.3s; }
        .photo-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.04); }
        .photo-card img { width: 100%; height: 220px; object-fit: cover; }
        .photo-info { padding: 25px; display: flex; justify-content: space-between; align-items: center; }
        .photo-info p { margin: 0; font-weight: 700; color: var(--portal-blue); font-size: 0.9rem; }
        .delete-btn { color: #d32f2f; font-weight: 800; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 40px;">
            <h1>Digital Gallery</h1>
            <p>Manage school memories and event photos.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="upload-section">
            <h3 style="margin-bottom: 25px;">Upload New Photo</h3>
            <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: flex-end;">
                <div class="portal-input-group" style="margin-bottom: 0;">
                    <label>Select Image</label>
                    <input type="file" name="photo" accept="image/*" required style="padding: 12px 20px;">
                </div>
                <div class="portal-input-group" style="margin-bottom: 0;">
                    <label>Caption</label>
                    <input type="text" name="caption" placeholder="Short description..." required>
                </div>
                <button type="submit" class="btn-portal" style="padding: 16px 40px;">Upload Image</button>
            </form>
        </div>

        <div class="gallery-grid">
            <?php while($row = $photos->fetch_assoc()): ?>
            <div class="photo-card">
                <img src="../<?php echo $row['image_path']; ?>" alt="Gallery Image">
                <div class="photo-info">
                    <p><?php echo htmlspecialchars($row['caption']); ?></p>
                    <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this photo?')">DELETE</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>
