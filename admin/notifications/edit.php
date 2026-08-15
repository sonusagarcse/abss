<?php
// admin/notifications/edit.php - Edit Notification Record
require_once '../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$err = '';

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Handle Form Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_notification'])) {
    $title    = trim($_POST['title'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $image    = trim($_POST['image'] ?? '');
    $url      = trim($_POST['url'] ?? '');
    $category = trim($_POST['category'] ?? 'General');

    if (empty($title) || empty($message)) {
        $err = "Title and Message fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE notification_history SET title = ?, message = ?, image = ?, url = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $message, $image, $url, $category, $id);
        if ($stmt->execute()) {
            header("Location: index.php?msg=" . urlencode("Notification record #$id updated successfully."));
            exit();
        } else {
            $err = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch record
$stmt = $conn->prepare("SELECT * FROM notification_history WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    header("Location: index.php?err=" . urlencode("Notification record not found."));
    exit();
}

$categories = [
    'Admission',
    'Fee Reminder',
    'Exam Notice',
    'Result',
    'Holiday',
    'Hostel',
    'Group A',
    'Group B',
    'Group C',
    'Group D',
    'General'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Notification #<?php echo $id; ?> | ABSS Admin</title>
    <?php include '../includes/head_css.php'; ?>
    <style>
        .form-card { background: #fff; border-radius: 28px; padding: 35px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 0.82rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; }
        .form-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #e2e8f0; background: #fff; font-family: inherit; font-size: 0.95rem; font-weight: 600; color: #0f172a; outline: none; transition: border-color 0.2s ease; }
        .form-input:focus { border-color: #2563eb; }

        .btn-update { width: 100%; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; padding: 15px; border-radius: 14px; font-weight: 900; font-size: 1.05rem; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(37, 99, 235, 0.45); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <a href="index.php" style="color: #64748b; font-weight: 800; text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to Notifications
                </a>
            </div>
            <h1 style="font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0;">Edit Notification Record #<?php echo $id; ?></h1>
        </header>

        <?php if($err): ?>
            <div style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 14px 20px; border-radius: 14px; font-weight: 700; margin-bottom: 25px; max-width: 800px; margin-left: auto; margin-right: auto;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="" method="POST">
                
                <div class="form-group">
                    <label class="form-label">Notification Title <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($record['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Notification Message <span style="color:#ef4444;">*</span></label>
                    <textarea name="message" class="form-input" rows="4" required><?php echo htmlspecialchars($record['message']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Category <span style="color:#ef4444;">*</span></label>
                    <select name="category" class="form-input" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php if(($record['category'] ?? '') === $cat) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Banner Image URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="image" class="form-input" value="<?php echo htmlspecialchars($record['image'] ?? ''); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label class="form-label">Action Click URL <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                    <input type="url" name="url" class="form-input" value="<?php echo htmlspecialchars($record['url'] ?? ''); ?>">
                </div>

                <button type="submit" name="update_notification" class="btn-update">
                    <i class="fas fa-save"></i> Save Notification Changes
                </button>
            </form>
        </div>

    </main>
</body>
</html>
