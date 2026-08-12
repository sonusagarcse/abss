<?php
require_once __DIR__ . '/../config/db.php';

$db = getDB();
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
}

$notifications = [];
$result = $db->query("SELECT * FROM notifications ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Control Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 1.75rem; color: #0f172a; font-weight: 700; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        
        .grid { display: grid; grid-template-columns: 350px 1fr; gap: 24px; }
        @media (max-width: 850px) { .grid { grid-template-columns: 1fr; } }

        .card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 16px; color: #334155; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; outline: none; transition: border 0.2s; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        textarea.form-control { resize: vertical; min-height: 90px; }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f1f5f9; color: #475569; font-weight: 600; }
        tr:hover { background: #f8fafc; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #f1f5f9; color: #64748b; }
        .actions { display: flex; gap: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Polling Notification Module Control</h1>
            <a href="polling_example.html" target="_blank" class="btn btn-secondary">Open Polling Demo &rarr;</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['msg'] === 'success') echo "Notification saved successfully!";
                elseif ($_GET['msg'] === 'deleted') echo "Notification deleted successfully!";
                elseif ($_GET['msg'] === 'status_updated') echo "Notification status updated!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Add / Edit Form -->
            <div class="card">
                <h2 class="card-title"><?= $edit_item ? 'Edit Notification' : 'Add Notification' ?></h2>
                <form action="save.php" method="POST">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. New Admission" value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="form-control" required placeholder="e.g. ABSS Admission Started"><?= htmlspecialchars($edit_item['message'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="url">Target URL (Optional)</label>
                        <input type="url" id="url" name="url" class="form-control" placeholder="https://example.com/admission" value="<?= htmlspecialchars($edit_item['url'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="1" <?= ($edit_item['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active (1)</option>
                            <option value="0" <?= ($edit_item['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive (0)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="flex:1;"><?= $edit_item ? 'Update' : 'Add Notification' ?></button>
                        <?php if ($edit_item): ?>
                            <a href="admin.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- View Notifications Table -->
            <div class="card" style="overflow-x: auto;">
                <h2 class="card-title">View Notifications</h2>
                <?php if (empty($notifications)): ?>
                    <p style="color: #64748b; font-size: 0.95rem;">No notifications created yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title & Message</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td><strong>#<?= $n['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                                        <div style="color: #64748b; font-size: 0.85rem; margin-top: 2px;"><?= htmlspecialchars($n['message']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($n['url'])): ?>
                                            <a href="<?= htmlspecialchars($n['url']) ?>" target="_blank" style="color:#2563eb; text-decoration:none;">Link &nearr;</a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($n['status'] == 1): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:#64748b; font-size: 0.8rem;"><?= $n['created_at'] ?></td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($n['status'] == 1): ?>
                                                <a href="status.php?id=<?= $n['id'] ?>&status=0" class="btn btn-warning btn-sm" title="Deactivate">Disable</a>
                                            <?php else: ?>
                                                <a href="status.php?id=<?= $n['id'] ?>&status=1" class="btn btn-success btn-sm" title="Activate">Enable</a>
                                            <?php endif; ?>
                                            <a href="admin.php?edit=<?= $n['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <a href="delete.php?id=<?= $n['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this notification?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
