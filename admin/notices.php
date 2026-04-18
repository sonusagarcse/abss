<?php
require_once 'includes/auth.php';

$msg = '';

// Handle Add/Edit Notice
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_notice'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, type=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $content, $type, $id);
        $stmt->execute();
        $msg = "Notice updated successfully.";
    } else {
        $stmt = $conn->prepare("INSERT INTO notices (title, content, type) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $type);
        $stmt->execute();
        $msg = "Notice published successfully.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notices WHERE id = $id");
    header("Location: notices.php");
    exit();
}

$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notice-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin-top: 40px; }
        .notice-card { background: #fff; padding: 35px; border-radius: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f0f4f8; position: relative; transition: 0.3s; }
        .notice-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.04); }
        .notice-type { position: absolute; top: 35px; right: 35px; padding: 6px 15px; border-radius: 100px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .type-info { background: #eef2ff; color: #0d47a1; }
        .type-important { background: #feeef2; color: #d32f2f; }
        .type-event { background: #f0fdf4; color: #22c55e; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,21,113,0.3); backdrop-filter: blur(8px); z-index: 4000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 50px; border-radius: 40px; width: 100%; max-width: 600px; box-shadow: 0 40px 100px rgba(0,21,113,0.2); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1>Notice Board</h1>
                <p>Digital announcements for students and parents.</p>
            </div>
            <button class="btn-portal" onclick="showModal()">
                <i class="fas fa-plus"></i> Create Notice
            </button>
        </header>

        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:30px; font-weight:700;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="notice-grid">
            <?php while($row = $notices->fetch_assoc()): ?>
            <div class="notice-card">
                <span class="notice-type type-<?php echo $row['type']; ?>"><?php echo $row['type']; ?></span>
                <h3 style="margin-bottom: 15px; padding-right: 80px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px;"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f4f8; pt: 20px; padding-top: 20px;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #9aa5ce;">
                        <i class="far fa-calendar-alt"></i> <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                    </span>
                    <div>
                        <button style="background:none; border:none; color:var(--portal-blue); cursor:pointer; font-weight:700; font-size:0.9rem; margin-right:15px;" onclick='editNotice(<?php echo json_encode($row); ?>)'>Edit</button>
                        <a href="?delete=<?php echo $row['id']; ?>" style="color:#d32f2f; font-weight:700; font-size:0.9rem; text-decoration:none;" onclick="return confirm('Delete this notice?')">Delete</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal" id="noticeModal">
            <div class="modal-content">
                <h2 style="margin-bottom: 30px;">Publish Notice</h2>
                <form action="" method="POST">
                    <input type="hidden" name="id" id="notice_id">
                    <div class="portal-input-group">
                        <label>Notice Title</label>
                        <input type="text" name="title" id="title" placeholder="Short descriptive heading" required>
                    </div>
                    <div class="portal-input-group">
                        <label>Notice Category</label>
                        <select name="type" id="type">
                            <option value="info">General Information</option>
                            <option value="important">Important / Urgent</option>
                            <option value="event">School Event</option>
                        </select>
                    </div>
                    <div class="portal-input-group">
                        <label>Content Description</label>
                        <textarea name="content" id="content" rows="5" placeholder="Detailed notice body..." required></textarea>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="submit" name="save_notice" class="btn-portal w-100">Publish Now</button>
                        <button type="button" class="btn-glass w-100" onclick="hideModal()">Discard</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showModal() {
            document.getElementById('noticeModal').style.display = 'flex';
            document.getElementById('notice_id').value = '';
            document.querySelector('#noticeModal form').reset();
        }
        function hideModal() {
            document.getElementById('noticeModal').style.display = 'none';
        }
        function editNotice(data) {
            document.getElementById('noticeModal').style.display = 'flex';
            document.getElementById('notice_id').value = data.id;
            document.getElementById('title').value = data.title;
            document.getElementById('type').value = data.type;
            document.getElementById('content').value = data.content;
        }
    </script>
</body>
</html>
