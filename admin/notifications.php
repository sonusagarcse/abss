<?php
require_once 'includes/auth.php';

$msg = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') $msg = "Notification saved successfully.";
    elseif ($_GET['msg'] === 'deleted') $msg = "Notification deleted successfully.";
    elseif ($_GET['msg'] === 'status_updated') $msg = "Notification status updated.";
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Handle Edit Fetch
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
}

// Fetch all notifications
$notifications = [];
$res = $conn->query("SELECT * FROM notifications ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push Notifications | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .notifications-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        @media (max-width: 992px) {
            .notifications-grid {
                grid-template-columns: 1fr;
            }
        }
        .portal-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            border: 1px solid #f0f4f8;
        }
        .portal-card h3 {
            font-size: 1.25rem;
            color: #002171;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .form-input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 4px rgba(13, 71, 161, 0.1);
        }
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        .btn-portal {
            background: var(--portal-blue);
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            font-size: 0.95rem;
        }
        .btn-portal:hover {
            background: var(--portal-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-danger:hover {
            background: #fca5a5;
        }
        .btn-success {
            background: #dcfce7;
            color: #166534;
        }
        .btn-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .custom-table th, .custom-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f4f8;
            font-size: 0.9rem;
        }
        .custom-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #f1f5f9; color: #64748b; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 1.8rem; color: #002171; font-weight: 800;">Push Notifications</h1>
                <p style="color: #64748b; margin-top: 4px;">Create & manage real-time browser polling notifications for site visitors.</p>
            </div>
            <div>
                <a href="../notifications/polling_example.html" target="_blank" class="btn-portal btn-secondary">
                    <i class="fas fa-external-link-alt"></i> Live Polling Demo
                </a>
            </div>
        </header>

        <?php if ($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:15px 25px; border-radius:16px; margin-bottom:25px; font-weight:700; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:15px 25px; border-radius:16px; margin-bottom:25px; font-weight:700; border: 1px solid #fecaca;">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="notifications-grid">
            <!-- Add / Edit Notification Form Card -->
            <div class="portal-card">
                <h3><i class="fas <?= $edit_item ? 'fa-edit' : 'fa-plus-circle' ?>"></i> <?= $edit_item ? 'Edit Notification' : 'Create Notification' ?></h3>
                
                <form action="../notifications/save.php" method="POST">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" class="form-input" required placeholder="e.g. New Admission Open" value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="form-input" required placeholder="e.g. ABSS Admission process for 2026 has officially started."><?= htmlspecialchars($edit_item['message'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="url">Target Link (Optional)</label>
                        <input type="url" id="url" name="url" class="form-input" placeholder="https://example.com/admission" value="<?= htmlspecialchars($edit_item['url'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-input">
                            <option value="1" <?= ($edit_item['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active (Broadcast)</option>
                            <option value="0" <?= ($edit_item['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive (Draft)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                        <button type="submit" class="btn-portal" style="flex: 1; justify-content: center;">
                            <i class="fas fa-save"></i> <?= $edit_item ? 'Update Notification' : 'Publish Notification' ?>
                        </button>
                        <?php if ($edit_item): ?>
                            <a href="notifications.php" class="btn-portal btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- View Notifications List Card -->
            <div class="portal-card">
                <h3><i class="fas fa-list"></i> Manage Notifications</h3>
                <div class="table-responsive">
                    <?php if (empty($notifications)): ?>
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-bell-slash" style="font-size: 2.5rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                            <p>No notifications created yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title & Content</th>
                                    <th>Target URL</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $n): ?>
                                    <tr>
                                        <td><strong>#<?= $n['id'] ?></strong></td>
                                        <td>
                                            <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($n['title']) ?></div>
                                            <div style="color: #64748b; font-size: 0.85rem; margin-top: 3px; max-width: 320px; word-break: break-word;">
                                                <?= htmlspecialchars($n['message']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($n['url'])): ?>
                                                <a href="<?= htmlspecialchars($n['url']) ?>" target="_blank" style="color: var(--portal-blue); font-weight: 600; text-decoration: none;">
                                                    <i class="fas fa-external-link-alt"></i> Open
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #cbd5e1;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($n['status'] == 1): ?>
                                                <span class="status-badge status-active"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 0.8rem; white-space: nowrap;">
                                            <?= date('M d, Y H:i', strtotime($n['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px; flex-wrap: nowrap;">
                                                <?php if ($n['status'] == 1): ?>
                                                    <a href="../notifications/status.php?id=<?= $n['id'] ?>&status=0" class="btn-portal btn-warning btn-sm" title="Deactivate">Disable</a>
                                                <?php else: ?>
                                                    <a href="../notifications/status.php?id=<?= $n['id'] ?>&status=1" class="btn-portal btn-success btn-sm" title="Activate">Enable</a>
                                                <?php endif; ?>
                                                <a href="notifications.php?edit=<?= $n['id'] ?>" class="btn-portal btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                                                <a href="../notifications/delete.php?id=<?= $n['id'] ?>" class="btn-portal btn-danger btn-sm" onclick="return confirm('Delete this notification?');"><i class="fas fa-trash-alt"></i></a>
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
    </main>
</body>
</html>
