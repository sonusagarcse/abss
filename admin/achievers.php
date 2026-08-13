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
        .achievers-layout-2col {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 25px;
            align-items: start;
        }
        
        .upload-section { 
            background: #fff; 
            padding: 30px; 
            border-radius: var(--radius-lg); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
            border: 1px solid #e2e8f0; 
            position: sticky;
            top: 20px;
        }

        .gallery-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
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
        .photo-info { padding: 18px; }
        .photo-info h3 { margin: 0 0 4px 0; color: var(--portal-dark); font-size: 1.05rem; font-weight: 800; }
        .photo-info p { margin: 0 0 12px 0; font-weight: 600; color: #64748b; font-size: 0.85rem; }
        .action-row { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 12px; }
        .delete-btn { color: #dc2626; font-weight: 800; text-decoration: none; font-size: 0.78rem; }
        .badge { background: #eff6ff; color: var(--portal-blue); padding: 4px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 800; }

        @media (max-width: 900px) {
            .achievers-layout-2col { grid-template-columns: 1fr; }
            .upload-section { position: static; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 25px;">
            <h1 style="margin:0 0 4px 0; font-size:1.8rem;">Hall of Excellence</h1>
            <p style="margin:0; color:#64748b;">Manage student achievers and entrance exam placements in 2-column view.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#dcfce7; color:#15803d; padding:14px 20px; border-radius:var(--radius-md); margin-bottom:25px; font-weight:700; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- 2-COLUMN SIDE-BY-SIDE LAYOUT -->
        <div class="achievers-layout-2col">
            
            <!-- LEFT COLUMN: ADD FORM -->
            <div class="upload-section">
                <h3 style="margin:0 0 20px 0; font-size:1.1rem; color:var(--portal-dark); font-weight:800; padding-bottom:10px; border-bottom:2px solid #f1f5f9;">
                    <i class="fas fa-user-plus" style="color:var(--portal-blue);"></i> Add New Achiever
                </h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="portal-input-group">
                        <label>Student Name</label>
                        <input type="text" name="name" placeholder="Rahul Kumar" required>
                    </div>

                    <div class="portal-input-group">
                        <label>Target School / Exam</label>
                        <input type="text" name="target_school" placeholder="Netarhat Residential" required>
                    </div>

                    <div class="portal-input-group">
                        <label>Batch / Year</label>
                        <input type="text" name="batch_year" placeholder="Batch 2024-25" required>
                    </div>

                    <div class="portal-input-group">
                        <label>Student Photo</label>
                        <input type="file" name="photo" accept="image/*" required style="padding: 10px;">
                    </div>

                    <button type="submit" class="btn-portal w-100" style="padding: 14px; font-weight:800; margin-top:10px;">
                        <i class="fas fa-plus-circle"></i> Add Achiever
                    </button>
                </form>
            </div>

            <!-- RIGHT COLUMN: EXISTING ACHIEVERS LIST GRID -->
            <div>
                <div class="gallery-grid">
                    <?php if($achievers && $achievers->num_rows > 0): while($row = $achievers->fetch_assoc()): ?>
                    <div class="photo-card">
                        <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" alt="Student Photo">
                        <div class="photo-info">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p><?php echo htmlspecialchars($row['batch_year']); ?></p>
                            <div class="action-row">
                                <span class="badge"><?php echo htmlspecialchars($row['target_school']); ?></span>
                                <a href="?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this achiever?')">DELETE</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div style="grid-column:1/-1; background:#fff; padding:40px; border-radius:20px; text-align:center; color:#94a3b8; font-weight:600; border:1px solid #e2e8f0;">
                        No achievers added yet. Use the form on the left to add achievers.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
