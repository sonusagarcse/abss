<?php
// admin/counselors.php - Counselor Management & Granular Module Access Control Console
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Available Modules Catalog from auth helper
$modules_catalog = get_counselor_modules_catalog();

// Group modules by category for clean UI display
$modules_by_category = [];
foreach ($modules_catalog as $m_key => $m_data) {
    $cat = $m_data['category'] ?? 'General';
    $modules_by_category[$cat][$m_key] = $m_data;
}

// ----------------------------------------------------
// 1. Handle POST Actions
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $err = "Security token validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';

        // SAVE DEFAULT MODULE PERMISSIONS
        if ($action === 'save_defaults') {
            $default_mods = isset($_POST['default_modules']) && is_array($_POST['default_modules']) ? $_POST['default_modules'] : [];
            // Filter only valid modules from catalog
            $sanitized_defaults = array_values(array_intersect($default_mods, array_keys($modules_catalog)));
            $json_defaults = json_encode($sanitized_defaults);
            saveSetting('counselor_default_modules', $json_defaults);
            $msg = "Default counselor module access permissions updated successfully!";
            if (function_exists('log_activity')) {
                log_activity('counselor_defaults_updated', "Admin updated default counselor module permissions (" . count($sanitized_defaults) . " modules enabled)");
            }
        }

        // ADD NEW COUNSELOR
        elseif ($action === 'add_counselor') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $designation = trim($_POST['designation'] ?? 'Admission Counselor');
            $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';
            $password = trim($_POST['password'] ?? '');

            $selected_mods = isset($_POST['modules']) && is_array($_POST['modules']) ? $_POST['modules'] : [];
            $sanitized_mods = array_values(array_intersect($selected_mods, array_keys($modules_catalog)));
            $json_mods = json_encode($sanitized_mods);

            if (empty($name) || empty($email) || empty($phone) || empty($password)) {
                $err = "Please complete all required fields (Name, Email, Mobile Phone, and Password).";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Please enter a valid email address.";
            } else {
                // Check if email already registered
                $chk = $conn->prepare("SELECT id FROM counselors WHERE LOWER(email) = LOWER(?) LIMIT 1");
                $chk->bind_param("s", $email);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $err = "A counselor with email '$email' is already registered.";
                } else {
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $conn->prepare("INSERT INTO counselors (name, email, phone, password, designation, permissions, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("sssssss", $name, $email, $phone, $pass_hash, $designation, $json_mods, $status);
                    if ($ins->execute()) {
                        $msg = "Counselor <strong>" . htmlspecialchars($name) . "</strong> created successfully with " . count($sanitized_mods) . " assigned module(s).";
                        if (function_exists('log_activity')) {
                            log_activity('counselor_created', "Created counselor account: $name ($email)");
                        }
                    } else {
                        $err = "Database error while adding counselor: " . $conn->error;
                    }
                }
            }
        }

        // EDIT COUNSELOR & PERMISSIONS
        elseif ($action === 'edit_counselor') {
            $cid = (int)($_POST['counselor_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $designation = trim($_POST['designation'] ?? 'Admission Counselor');
            $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';
            $new_password = trim($_POST['password'] ?? '');

            $selected_mods = isset($_POST['modules']) && is_array($_POST['modules']) ? $_POST['modules'] : [];
            $sanitized_mods = array_values(array_intersect($selected_mods, array_keys($modules_catalog)));
            $json_mods = json_encode($sanitized_mods);

            if ($cid <= 0 || empty($name) || empty($email) || empty($phone)) {
                $err = "Please enter valid counselor information.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $err = "Please enter a valid email address.";
            } else {
                // Ensure email uniqueness excluding self
                $chk = $conn->prepare("SELECT id FROM counselors WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
                $chk->bind_param("si", $email, $cid);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $err = "Email '$email' is already in use by another counselor.";
                } else {
                    if (!empty($new_password)) {
                        $pass_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $upd = $conn->prepare("UPDATE counselors SET name = ?, email = ?, phone = ?, password = ?, designation = ?, permissions = ?, status = ? WHERE id = ?");
                        $upd->bind_param("sssssssi", $name, $email, $phone, $pass_hash, $designation, $json_mods, $status, $cid);
                    } else {
                        $upd = $conn->prepare("UPDATE counselors SET name = ?, email = ?, phone = ?, designation = ?, permissions = ?, status = ? WHERE id = ?");
                        $upd->bind_param("ssssssi", $name, $email, $phone, $designation, $json_mods, $status, $cid);
                    }

                    if ($upd->execute()) {
                        $msg = "Counselor <strong>" . htmlspecialchars($name) . "</strong> updated successfully!";
                        if (function_exists('log_activity')) {
                            log_activity('counselor_updated', "Updated counselor #$cid ($name), permissions: " . count($sanitized_mods) . " modules");
                        }
                    } else {
                        $err = "Database error while updating counselor: " . $conn->error;
                    }
                }
            }
        }
    }
}

// ----------------------------------------------------
// 2. Handle GET Actions (Toggle Status, Delete)
// ----------------------------------------------------
if (isset($_GET['toggle_status'])) {
    $cid = (int)$_GET['toggle_status'];
    $target_status = $_GET['status'] === 'active' ? 'active' : 'inactive';
    if ($cid > 0) {
        $stmt = $conn->prepare("UPDATE counselors SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $target_status, $cid);
        if ($stmt->execute()) {
            $msg = "Counselor status changed to " . strtoupper($target_status) . ".";
            if (function_exists('log_activity')) {
                log_activity('counselor_status_toggle', "Counselor #$cid status changed to $target_status");
            }
        }
    }
}

if (isset($_GET['delete_counselor'])) {
    $cid = (int)$_GET['delete_counselor'];
    if ($cid > 0) {
        $stmt = $conn->prepare("DELETE FROM counselors WHERE id = ?");
        $stmt->bind_param("i", $cid);
        if ($stmt->execute()) {
            $msg = "Counselor deleted successfully.";
            if (function_exists('log_activity')) {
                log_activity('counselor_deleted', "Deleted counselor #$cid");
            }
        }
    }
}

// ----------------------------------------------------
// 3. Fetch Counselors & Current Default Settings
// ----------------------------------------------------
$counselors_res = $conn->query("SELECT * FROM counselors ORDER BY id ASC");
$counselors = [];
$total_counselors = 0;
$active_counselors = 0;

if ($counselors_res) {
    while ($row = $counselors_res->fetch_assoc()) {
        $row['permissions_array'] = !empty($row['permissions']) ? json_decode($row['permissions'], true) : [];
        if (!is_array($row['permissions_array'])) $row['permissions_array'] = [];
        $counselors[] = $row;
        $total_counselors++;
        if ($row['status'] === 'active') $active_counselors++;
    }
}

// Default permissions setting
$all_settings = function_exists('getAllSettings') ? getAllSettings() : [];
$raw_defaults = $all_settings['counselor_default_modules'] ?? (function_exists('getSetting') ? getSetting('counselor_default_modules') : '');
$default_modules = !empty($raw_defaults) ? json_decode($raw_defaults, true) : ['inquiries', 'admissions', 'students', 'visitors', 'tickets', 'notices'];
if (!is_array($default_modules)) $default_modules = ['inquiries', 'admissions', 'students', 'visitors', 'tickets', 'notices'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselors &amp; Module Access Control | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-subtitle {
            color: #64748b;
            font-size: 0.88rem;
            margin: 4px 0 0 0;
        }
        .stats-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .stat-meta h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-meta p {
            margin: 2px 0 0 0;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .portal-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .custom-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.76rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.05em;
            padding: 14px 18px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        .custom-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
            vertical-align: middle;
        }
        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .counselor-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            color: #334155;
            padding: 4px 9px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 700;
            margin: 2px 3px 2px 0;
            border: 1px solid #e2e8f0;
        }
        .counselor-pill i {
            font-size: 0.72rem;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-status.active {
            background: #dcfce7;
            color: #166534;
        }
        .badge-status.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 820px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-body {
            padding: 24px;
        }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f8fafc;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }
        .module-checkbox-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .module-checkbox-card:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .module-checkbox-card input[type="checkbox"] {
            margin-top: 3px;
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: #2563eb;
        }
        .module-card-info {
            flex: 1;
        }
        .module-card-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .module-card-desc {
            font-size: 0.74rem;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.35;
        }

        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
        }
        .btn-primary:hover {
            opacity: 0.94;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.78rem;
            border-radius: 8px;
        }
        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-danger:hover {
            background: #fecaca;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
            font-family: inherit;
            color: #0f172a;
            box-sizing: border-box;
            background: #f8fafc;
            transition: border-color 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #0d9488;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1300px; margin: 0 auto;">

            <!-- Header Section -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-user-tie" style="color: #0d9488;"></i> Counselors &amp; Module Access Control
                    </h1>
                    <p class="page-subtitle">
                        Manage counselor profiles and decide precisely which portal modules are accessible to each counselor.
                    </p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="btn-portal btn-secondary" onclick="openModal('defaultsModal')">
                        <i class="fas fa-sliders-h"></i> Default Permissions
                    </button>
                    <button class="btn-portal btn-primary" onclick="openAddModal()">
                        <i class="fas fa-user-plus"></i> Add New Counselor
                    </button>
                    <a href="../counselor/login.php" target="_blank" class="btn-portal btn-secondary" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                        <i class="fas fa-external-link-alt"></i> Counselor Login
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($msg)): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: #16a34a; font-size: 1.1rem;"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($err)): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="color: #dc2626; font-size: 1.1rem;"></i>
                    <span><?php echo $err; ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats Ribbon -->
            <div class="stats-ribbon">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-meta">
                        <h3><?php echo $total_counselors; ?></h3>
                        <p>Total Counselors</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-meta">
                        <h3><?php echo $active_counselors; ?></h3>
                        <p>Active Staff</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ccfbf1; color: #0d9488;">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-meta">
                        <h3><?php echo count($modules_catalog); ?></h3>
                        <p>Catalog Modules</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-meta">
                        <h3><?php echo count($default_modules); ?></h3>
                        <p>Default Enabled</p>
                    </div>
                </div>
            </div>

            <!-- Counselors List Table -->
            <div class="portal-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Registered Counselors</h2>
                        <p style="color: #64748b; font-size: 0.82rem; margin: 3px 0 0 0;">Each counselor only sees modules authorized below in their dedicated panel.</p>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Counselor Profile</th>
                                <th>Contact Information</th>
                                <th>Status</th>
                                <th style="min-width: 300px;">Permitted Modules (Decided by Admin)</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($counselors)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <i class="fas fa-user-slash" style="font-size: 2.5rem; margin-bottom: 12px; display: block;"></i>
                                        No counselors found. Click "Add New Counselor" above to set up your counseling team.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($counselors as $c): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(13,148,136,0.2);">
                                                    <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;">
                                                        <?php echo htmlspecialchars($c['name']); ?>
                                                    </div>
                                                    <div style="font-size: 0.78rem; color: #64748b;">
                                                        <?php echo htmlspecialchars($c['designation'] ?: 'Admission Counselor'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #334155;">
                                                <i class="fas fa-envelope" style="color: #94a3b8; width: 14px;"></i> <?php echo htmlspecialchars($c['email']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 3px;">
                                                <i class="fas fa-phone" style="color: #94a3b8; width: 14px;"></i> <?php echo htmlspecialchars($c['phone']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($c['status'] === 'active'): ?>
                                                <span class="badge-status active"><i class="fas fa-check-circle"></i> Active</span>
                                            <?php else: ?>
                                                <span class="badge-status inactive"><i class="fas fa-ban"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                <?php 
                                                $p_count = count($c['permissions_array']);
                                                if ($p_count === 0): 
                                                ?>
                                                    <span style="color: #dc2626; font-size: 0.78rem; font-weight: 700;">
                                                        <i class="fas fa-lock"></i> No modules permitted
                                                    </span>
                                                <?php else: ?>
                                                    <?php foreach ($c['permissions_array'] as $mod_key): ?>
                                                        <?php if (isset($modules_catalog[$mod_key])): ?>
                                                            <span class="counselor-pill" title="<?php echo htmlspecialchars($modules_catalog[$mod_key]['desc']); ?>">
                                                                <i class="<?php echo $modules_catalog[$mod_key]['icon']; ?>" style="color: <?php echo $modules_catalog[$mod_key]['color']; ?>;"></i>
                                                                <?php echo htmlspecialchars($modules_catalog[$mod_key]['label']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <button type="button" class="btn-portal btn-secondary btn-sm" onclick='openEditModal(<?php echo json_encode($c); ?>)'>
                                                <i class="fas fa-edit"></i> Edit Access
                                            </button>
                                            <?php if ($c['status'] === 'active'): ?>
                                                <a href="counselors.php?toggle_status=<?php echo $c['id']; ?>&status=inactive" class="btn-portal btn-secondary btn-sm" title="Deactivate counselor" onclick="return confirm('Deactivate this counselor account?')">
                                                    <i class="fas fa-pause"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="counselors.php?toggle_status=<?php echo $c['id']; ?>&status=active" class="btn-portal btn-primary btn-sm" title="Activate counselor">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="counselors.php?delete_counselor=<?php echo $c['id']; ?>" class="btn-portal btn-danger btn-sm" title="Delete counselor" onclick="return confirm('Permanently remove this counselor?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- ============================================== -->
    <!-- MODAL: ADD COUNSELOR                           -->
    <!-- ============================================== -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Add New Counselor</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Create login credentials and choose permitted modules.</p>
                </div>
                <button type="button" onclick="closeModal('addModal')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="counselors.php">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <input type="hidden" name="action" value="add_counselor">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Anjali Sharma">
                        </div>
                        <div class="form-group">
                            <label>Email Address (Login Username) *</label>
                            <input type="email" name="email" class="form-control" required placeholder="e.g. anjali@abss.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Mobile Number *</label>
                            <input type="text" name="phone" class="form-control" required placeholder="e.g. 9876543210">
                        </div>
                        <div class="form-group">
                            <label>Initial Login Password *</label>
                            <input type="text" name="password" class="form-control" required value="counselor123" placeholder="Set secure password">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Designation / Role Title</label>
                            <input type="text" name="designation" class="form-control" value="Admission Counselor" placeholder="e.g. Senior Admission Counselor">
                        </div>
                        <div class="form-group">
                            <label>Account Status</label>
                            <select name="status" class="form-control">
                                <option value="active">Active (Can log in)</option>
                                <option value="inactive">Inactive (Access suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <label style="font-weight: 800; color: #0f172a; font-size: 0.95rem; margin: 0; text-transform: none;">
                                    <i class="fas fa-lock-open" style="color: #0d9488;"></i> Accessible Modules in Counselor Panel
                                </label>
                                <p style="font-size: 0.78rem; color: #64748b; margin: 2px 0 0 0;">Check modules this counselor is authorized to view and manage.</p>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('addModal', true)">Select All</button>
                                <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('addModal', false)">Deselect All</button>
                            </div>
                        </div>

                        <?php foreach ($modules_by_category as $category => $cat_modules): ?>
                            <div style="margin-bottom: 14px;">
                                <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">
                                    <?php echo htmlspecialchars($category); ?>
                                </span>
                                <div class="modules-grid">
                                    <?php foreach ($cat_modules as $mod_key => $mod): ?>
                                        <?php $is_checked = in_array($mod_key, $default_modules); ?>
                                        <label class="module-checkbox-card">
                                            <input type="checkbox" name="modules[]" value="<?php echo $mod_key; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                            <div class="module-card-info">
                                                <div class="module-card-title">
                                                    <i class="<?php echo $mod['icon']; ?>" style="color: <?php echo $mod['color']; ?>; width: 16px;"></i>
                                                    <span><?php echo htmlspecialchars($mod['label']); ?></span>
                                                </div>
                                                <div class="module-card-desc"><?php echo htmlspecialchars($mod['desc']); ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-portal btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-portal btn-primary">
                        <i class="fas fa-check"></i> Create Counselor Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: EDIT COUNSELOR & PERMISSIONS            -->
    <!-- ============================================== -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Edit Counselor &amp; Module Access</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Update account details or toggle accessible modules.</p>
                </div>
                <button type="button" onclick="closeModal('editModal')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="counselors.php">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <input type="hidden" name="action" value="edit_counselor">
                <input type="hidden" name="counselor_id" id="edit_counselor_id" value="">
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Mobile Number *</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Reset Password (Leave blank to keep current)</label>
                            <input type="text" name="password" id="edit_password" class="form-control" placeholder="Enter new password if changing">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" id="edit_designation" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="active">Active (Can log in)</option>
                                <option value="inactive">Inactive (Access suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <label style="font-weight: 800; color: #0f172a; font-size: 0.95rem; margin: 0; text-transform: none;">
                                    <i class="fas fa-lock-open" style="color: #0d9488;"></i> Accessible Modules for this Counselor
                                </label>
                                <p style="font-size: 0.78rem; color: #64748b; margin: 2px 0 0 0;">Check which modules this specific counselor is allowed to open.</p>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('editModal', true)">Select All</button>
                                <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('editModal', false)">Deselect All</button>
                            </div>
                        </div>

                        <?php foreach ($modules_by_category as $category => $cat_modules): ?>
                            <div style="margin-bottom: 14px;">
                                <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">
                                    <?php echo htmlspecialchars($category); ?>
                                </span>
                                <div class="modules-grid">
                                    <?php foreach ($cat_modules as $mod_key => $mod): ?>
                                        <label class="module-checkbox-card">
                                            <input type="checkbox" name="modules[]" value="<?php echo $mod_key; ?>" class="edit-module-cb" id="edit_mod_<?php echo $mod_key; ?>">
                                            <div class="module-card-info">
                                                <div class="module-card-title">
                                                    <i class="<?php echo $mod['icon']; ?>" style="color: <?php echo $mod['color']; ?>; width: 16px;"></i>
                                                    <span><?php echo htmlspecialchars($mod['label']); ?></span>
                                                </div>
                                                <div class="module-card-desc"><?php echo htmlspecialchars($mod['desc']); ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-portal btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-portal btn-primary">
                        <i class="fas fa-save"></i> Save Changes &amp; Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL: DEFAULT MODULE PERMISSIONS              -->
    <!-- ============================================== -->
    <div id="defaultsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">Configure Default Counselor Permissions</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Set which modules are enabled by default whenever a new counselor is added.</p>
                </div>
                <button type="button" onclick="closeModal('defaultsModal')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="counselors.php">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <input type="hidden" name="action" value="save_defaults">
                
                <div class="modal-body">
                    <div style="background: #f0fdfa; border: 1px solid #99f6e4; color: #0f766e; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-size: 0.84rem;">
                        <i class="fas fa-info-circle"></i> These defaults will pre-populate the module selection when creating any new counselor. You can still customize permissions individually for each counselor.
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 10px;">
                        <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('defaultsModal', true)">Select All</button>
                        <button type="button" class="btn-portal btn-secondary btn-sm" onclick="toggleAllCheckboxes('defaultsModal', false)">Deselect All</button>
                    </div>

                    <?php foreach ($modules_by_category as $category => $cat_modules): ?>
                        <div style="margin-bottom: 14px;">
                            <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">
                                <?php echo htmlspecialchars($category); ?>
                            </span>
                            <div class="modules-grid">
                                <?php foreach ($cat_modules as $mod_key => $mod): ?>
                                    <?php $is_checked = in_array($mod_key, $default_modules); ?>
                                    <label class="module-checkbox-card">
                                        <input type="checkbox" name="default_modules[]" value="<?php echo $mod_key; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                                        <div class="module-card-info">
                                            <div class="module-card-title">
                                                <i class="<?php echo $mod['icon']; ?>" style="color: <?php echo $mod['color']; ?>; width: 16px;"></i>
                                                <span><?php echo htmlspecialchars($mod['label']); ?></span>
                                            </div>
                                            <div class="module-card-desc"><?php echo htmlspecialchars($mod['desc']); ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-portal btn-secondary" onclick="closeModal('defaultsModal')">Cancel</button>
                    <button type="submit" class="btn-portal btn-primary">
                        <i class="fas fa-save"></i> Save Global Defaults
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function openAddModal() {
            openModal('addModal');
        }

        function openEditModal(counselor) {
            document.getElementById('edit_counselor_id').value = counselor.id;
            document.getElementById('edit_name').value = counselor.name;
            document.getElementById('edit_email').value = counselor.email;
            document.getElementById('edit_phone').value = counselor.phone;
            document.getElementById('edit_designation').value = counselor.designation || 'Admission Counselor';
            document.getElementById('edit_status').value = counselor.status || 'active';
            document.getElementById('edit_password').value = '';

            // Reset all edit module checkboxes
            var cbs = document.querySelectorAll('.edit-module-cb');
            cbs.forEach(function(cb) { cb.checked = false; });

            // Check assigned permissions
            if (counselor.permissions_array && Array.isArray(counselor.permissions_array)) {
                counselor.permissions_array.forEach(function(mod) {
                    var el = document.getElementById('edit_mod_' + mod);
                    if (el) el.checked = true;
                });
            }

            openModal('editModal');
        }

        function toggleAllCheckboxes(modalId, checkStatus) {
            var modal = document.getElementById(modalId);
            if (modal) {
                var cbs = modal.querySelectorAll('input[type="checkbox"]');
                cbs.forEach(function(cb) {
                    cb.checked = checkStatus;
                });
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>

