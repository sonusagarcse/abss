<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['photo'])) {
    $name = $_POST['name'];
    $target_school = $_POST['target_school'];
    $batch_year = $_POST['batch_year'];
    $target_dir = "../assets/achievers/";
    
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $file_name = time() . "_" . rand(1000, 9999) . "." . $file_ext;
    $target_file = $target_dir . $file_name;
    $db_path = "assets/achievers/" . $file_name;

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO achievers (name, target_school, batch_year, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $target_school, $batch_year, $db_path);
        if ($stmt->execute()) {
            $msg = "Achiever uploaded and added to Hall of Excellence.";
        } else {
            $msg = "Database Error: " . $stmt->error;
        }
    } else {
        $msg = "Sorry, there was an error uploading your file.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $photo = $conn->query("SELECT image_path FROM achievers WHERE id = $id")->fetch_assoc();
    if ($photo) {
        $file_path = "../" . $photo['image_path'];
        if (file_exists($file_path)) unlink($file_path);
        $conn->query("DELETE FROM achievers WHERE id = $id");
    }
    header("Location: achievers.php");
    exit();
}

$achievers = $conn->query("SELECT * FROM achievers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Achievers | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .upload-section { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 50px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        .photo-card { background: #fff; border-radius: 30px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f0f4f8; transition: 0.3s; }
        .photo-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.04); }
        .photo-card img { width: 100%; height: 250px; object-fit: cover; }
        .photo-info { padding: 25px; }
        .photo-info h3 { margin: 0 0 5px 0; color: var(--portal-blue); font-size: 1.2rem; font-weight: 800; }
        .photo-info p { margin: 0 0 15px 0; font-weight: 600; color: #5c6bc0; font-size: 0.9rem; }
        .action-row { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f4f8; padding-top: 15px; }
        .delete-btn { color: #d32f2f; font-weight: 800; text-decoration: none; font-size: 0.8rem; }
        .badge { background: #eef2ff; color: var(--portal-blue); padding: 5px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 40px;">
            <h1>Hall of Excellence</h1>
            <p>Manage student achievers and entrance exam placements.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="upload-section">
            <h3 style="margin-bottom: 25px;">Add New Achiever</h3>
            <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: flex-start;">
                
                <div class="portal-input-group">
                    <label>Student Name</label>
                    <input type="text" name="name" placeholder="E.g. Rahul Kumar" required>
                </div>

                <div class="portal-input-group">
                    <label>Target School / Exam</label>
                    <input type="text" name="target_school" placeholder="E.g. Netarhat Residential" required>
                </div>

                <div class="portal-input-group">
                    <label>Batch / Year</label>
                    <input type="text" name="batch_year" placeholder="E.g. Batch 2024-25" required>
                </div>

                <div class="portal-input-group">
                    <label>Student Photo</label>
                    <input type="file" name="photo" accept="image/*" required style="padding: 12px 20px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn-portal" style="padding: 16px 40px;">Add Achiever</button>
                </div>
            </form>
        </div>

        <div class="gallery-grid">
            <?php if($achievers): while($row = $achievers->fetch_assoc()): ?>
            <div class="photo-card">
                <img src="../<?php echo $row['image_path']; ?>" alt="Student Photo">
                <div class="photo-info">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><?php echo htmlspecialchars($row['batch_year']); ?></p>
                    <div class="action-row">
                        <span class="badge"><?php echo htmlspecialchars($row['target_school']); ?></span>
                        <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this achiever?')">DELETE</a>
                    </div>
                </div>
            </div>
            <?php endwhile; endif; ?>
        </div>
    </main>
</body>
</html>
