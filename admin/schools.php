<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Add School
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_school'])) {
    $name = trim($_POST['school_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO schools (school_name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $msg = "School added successfully.";
        }
    }
}

// Handle Delete School
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM schools WHERE id = $id");
    header("Location: schools.php");
    exit();
}

$schools = $conn->query("SELECT * FROM schools ORDER BY school_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schools | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .manage-section { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 30px; }
        .schools-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .school-card { background: #f8faff; padding: 20px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eef2ff; }
        .school-card span { font-weight: 700; color: var(--portal-blue); }
        .delete-link { color: #d32f2f; font-weight: 800; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 40px;">
            <h1>Target School List</h1>
            <p>Manage the list of schools available in the Admission Form.</p>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="manage-section">
            <h3 style="margin-bottom: 20px;">Add New School</h3>
            <form action="" method="POST" style="display: flex; gap: 15px;">
                <div class="portal-input-group" style="flex: 1; margin-bottom: 0;">
                    <input type="text" name="school_name" placeholder="Enter school name..." required>
                </div>
                <button type="submit" name="add_school" class="btn-portal">Add School</button>
            </form>
        </div>

        <div class="schools-list">
            <?php if ($schools && $schools->num_rows > 0): ?>
                <?php while($row = $schools->fetch_assoc()): ?>
                <div class="school-card">
                    <span><?php echo htmlspecialchars($row['school_name']); ?></span>
                    <a href="?delete=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Delete this school?')">DELETE</a>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #888;">No schools added yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
