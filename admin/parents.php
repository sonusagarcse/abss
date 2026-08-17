<?php
// admin/parents.php - Parent Registry & Portal Access Management

require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Reset Password to Mobile Number
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_to_phone'])) {
    $parent_id = (int)$_POST['parent_id'];
    $stmt = $conn->prepare("SELECT id, parent_name, phone, email FROM parents WHERE id = ?");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();

    if ($p && !empty($p['phone'])) {
        $clean_phone = trim($p['phone']);
        $new_hash = password_hash($clean_phone, PASSWORD_DEFAULT);
        $u_stmt = $conn->prepare("UPDATE parents SET password = ? WHERE id = ?");
        $u_stmt->bind_param("si", $new_hash, $parent_id);
        if ($u_stmt->execute()) {
            $msg = "Password for <strong>" . htmlspecialchars($p['parent_name']) . "</strong> has been reset to default Mobile Number (<strong>" . htmlspecialchars($clean_phone) . "</strong>).";
            if (function_exists('log_activity')) {
                log_activity('parent_pass_reset', "Reset password to mobile number for " . $p['parent_name'] . " (" . $p['email'] . ")");
            }
        } else {
            $err = "Database error while resetting password.";
        }
    } else {
        $err = "Cannot reset password: No contact mobile number found on this parent profile.";
    }
}

// Handle Add/Edit Parent
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_parent'])) {
    $parent_name = trim($_POST['parent_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $selected_students = isset($_POST['students']) && is_array($_POST['students']) ? $_POST['students'] : [];

    // Auto-generate placeholder email if only phone is provided
    if (empty($email) && !empty($phone)) {
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        $email = "parent_" . $clean_phone . "@abss.in";
    }

    if (empty($parent_name)) {
        $err = "Parent / Guardian Name is a required field.";
    } elseif (empty($email) && empty($phone)) {
        $err = "Please provide at least a Mobile Number or an Email address for parent login.";
    } else {
        if ($id > 0) {
            // Edit Parent
            $check = $conn->prepare("SELECT id FROM parents WHERE (email = ? OR (phone = ? AND phone != '')) AND id != ?");
            $check->bind_param("ssi", $email, $phone, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "Another parent account with this email or mobile number already exists.";
            } else {
                if (!empty($password)) {
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE parents SET parent_name = ?, email = ?, password = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $parent_name, $email, $pass_hash, $phone, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE parents SET parent_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $parent_name, $email, $phone, $id);
                }
                
                if ($stmt->execute()) {
                    // Update student linkages
                    $conn->query("UPDATE students SET parent_id = NULL WHERE parent_id = $id");
                    if (!empty($selected_students)) {
                        $ids_str = implode(',', array_map('intval', $selected_students));
                        $conn->query("UPDATE students SET parent_id = $id WHERE id IN ($ids_str)");
                    }
                    $msg = "Parent account & student linkages updated successfully.";
                    if (function_exists('log_activity')) {
                        log_activity('parent_updated', "Updated parent credentials & linkages for $parent_name ($email)");
                    }
                } else {
                    $err = "Error updating parent account.";
                }
            }
        } else {
            // New Parent - Auto-set Mobile Number as Password if not specified
            $check = $conn->prepare("SELECT id FROM parents WHERE email = ? OR (phone = ? AND phone != '')");
            $check->bind_param("ss", $email, $phone);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $err = "A parent account with this email or mobile number already exists.";
            } else {
                // If password is not explicitly set, use the mobile number (phone) as default password
                if (empty($password)) {
                    $password = !empty($phone) ? $phone : '123456';
                }
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO parents (parent_name, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $parent_name, $email, $pass_hash, $phone);
                
                if ($stmt->execute()) {
                    $new_parent_id = $conn->insert_id;
                    if (!empty($selected_students)) {
                        $ids_str = implode(',', array_map('intval', $selected_students));
                        $conn->query("UPDATE students SET parent_id = $new_parent_id WHERE id IN ($ids_str)");
                    }
                    $msg = "Parent portal account created successfully! Mobile number has been set as both Login ID & Password.";
                    if (function_exists('log_activity')) {
                        log_activity('parent_created', "Created parent account for $parent_name ($email)");
                    }
                } else {
                    $err = "Error creating parent account.";
                }
            }
        }
    }
}

// Handle Delete Parent
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $p_stmt = $conn->query("SELECT parent_name, email FROM parents WHERE id = $id");
    $p_row = $p_stmt ? $p_stmt->fetch_assoc() : null;
    $del_pname = $p_row['parent_name'] ?? 'ID ' . $id;
    
    $conn->query("DELETE FROM parents WHERE id = $id");
    if (function_exists('log_activity')) {
        log_activity('parent_deleted', "Deleted parent account for $del_pname");
    }
    
    header("Location: parents.php");
    exit();
}

// Fetch all parents with aggregate child data
$parents_query = $conn->query("
    SELECT 
        p.*, 
        COUNT(s.id) AS children_count,
        GROUP_CONCAT(DISTINCT s.name ORDER BY s.name ASC SEPARATOR '||') AS children_names_raw,
        GROUP_CONCAT(DISTINCT CONCAT(s.name, ' (', COALESCE(s.class_admitted, 'Class 5'), ')') ORDER BY s.name ASC SEPARATOR '||') AS children_details_raw
    FROM parents p
    LEFT JOIN students s ON s.parent_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

$parents_data = [];
$total_parents_count = 0;
$with_children_count = 0;
$with_phone_count = 0;

if ($parents_query) {
    while ($row = $parents_query->fetch_assoc()) {
        $row['children_count'] = (int)$row['children_count'];
        $parents_data[] = $row;
        $total_parents_count++;
        if ($row['children_count'] > 0) $with_children_count++;
        if (!empty($row['phone'])) $with_phone_count++;
    }
}

// Fetch active students for the modal linkage selector
$students_list = $conn->query("SELECT id, name, class_admitted, reg_no, parent_id FROM students ORDER BY name ASC");
$all_students = [];
if ($students_list) {
    while ($s = $students_list->fetch_assoc()) {
        $all_students[] = $s;
    }
}

// Determine portal URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'abss.lkvmbihar.in';
$base_app_url = (strpos($host, 'localhost') !== false) ? "http://localhost/abss" : "$protocol://$host";
$parent_portal_url = "$base_app_url/parent/login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Registry & Credentials | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        /* Modern Header & Grid */
        .parents-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        /* Filter Console */
        .search-filter-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .filter-controls-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 14px;
            align-items: center;
        }

        .search-field-wrapper {
            position: relative;
            width: 100%;
        }
        .search-field-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }
        .search-field-wrapper input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: 0.25s;
            box-sizing: border-box;
        }
        .search-field-wrapper input:focus {
            border-color: var(--portal-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .filter-select {
            width: 100%;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            border: 2px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            color: #334155;
            outline: none;
            box-sizing: border-box;
            transition: 0.25s;
        }
        .filter-select:focus {
            border-color: var(--portal-blue);
        }

        /* Parent Table Styling */
        .parents-table {
            width: 100%;
            min-width: 750px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .parents-table th {
            text-align: left;
            padding: 8px 18px;
            color: var(--portal-blue);
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: transparent;
            border: none;
        }
        .parent-row td {
            padding: 18px 20px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #334155;
            vertical-align: middle;
            transition: background 0.2s;
        }
        .parent-row:hover td {
            background: #f8fafc;
        }
        .parent-row td:first-child {
            border-left: 1px solid #f1f5f9;
            border-radius: 14px 0 0 14px;
        }
        .parent-row td:last-child {
            border-right: 1px solid #f1f5f9;
            border-radius: 0 14px 14px 0;
        }

        .parent-avatar-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.05rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .credential-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: var(--portal-blue);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 800;
            font-family: monospace;
            border: 1px solid #dbeafe;
        }
        .pass-default-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            color: #166534;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 800;
            border: 1px solid #bbf7d0;
        }

        .child-pill {
            background: #f1f5f9;
            color: var(--portal-dark);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 2px 4px 2px 0;
            border: 1px solid #e2e8f0;
        }

        /* Action Buttons */
        .act-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .act-btn-wa { background: #dcfce7; color: #15803d; }
        .act-btn-wa:hover { background: #22c55e; color: #fff; transform: scale(1.05); }
        .act-btn-key { background: #fef3c7; color: #b45309; }
        .act-btn-key:hover { background: #f59e0b; color: #fff; transform: scale(1.05); }
        .act-btn-edit { background: #eff6ff; color: var(--portal-blue); }
        .act-btn-edit:hover { background: var(--portal-blue); color: #fff; transform: scale(1.05); }
        .act-btn-del { background: #fee2e2; color: #dc2626; }
        .act-btn-del:hover { background: #dc2626; color: #fff; transform: scale(1.05); }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(8px);
            z-index: 4000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 30px 15px;
            box-sizing: border-box;
        }
        .modal-content {
            background: #ffffff;
            padding: 40px;
            border-radius: 28px;
            width: 100%;
            max-width: 680px;
            box-shadow: 0 40px 100px rgba(15, 23, 42, 0.2);
            border: 1px solid #e2e8f0;
            margin: auto;
            max-height: 90vh;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .students-checklist-box {
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 14px;
            background: #f8fafc;
            max-height: 190px;
            overflow-y: auto;
        }
        .student-chk-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            color: var(--portal-dark);
            font-weight: 700;
            font-size: 0.9rem;
        }
        .student-chk-item:hover {
            background: #eff6ff;
        }
        .student-chk-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--portal-blue);
        }

        .notice-info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.86rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 800px) {
            .filter-controls-row {
                grid-template-columns: 1fr;
            }
            .modal-content {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Top Action & Navigation Header -->
        <div class="parents-header-row">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-friends" style="color: #10b981;"></i> Parent Registry & Portal Access
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">Automated parent account provisioning — mobile number serves as both Login ID and default Password.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <button class="btn-portal" onclick="showCreateModal()">
                    <i class="fas fa-user-plus"></i> Create Parent Login
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($msg)): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 14px; margin-bottom: 25px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                <span><?php echo $err; ?></span>
            </div>
        <?php endif; ?>

        <!-- KPI Metrics Overview -->
        <div class="stats-kpi-grid">
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-user-friends"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_parents_count); ?></h3>
                    <span>Total Parent Accounts</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($with_children_count); ?></h3>
                    <span>Accounts With Children</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-mobile-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($with_phone_count); ?></h3>
                    <span>Direct Mobile Login Ready</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-key"></i></div>
                <div class="stat-info">
                    <h3 style="font-size: 1.25rem;">Phone = ID / Pass</h3>
                    <span>Default Auth Protocol</span>
                </div>
            </div>
        </div>

        <!-- Search & Filter Console -->
        <div class="search-filter-card">
            <div class="filter-controls-row">
                <div class="search-field-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="parentSearchInput" placeholder="Search by parent name, mobile number, email, or student name..." onkeyup="filterParents()">
                </div>

                <div>
                    <select id="parentTypeFilter" class="filter-select" onchange="filterParents()">
                        <option value="">-- All Parent Profiles --</option>
                        <option value="with_children">With Linked Children</option>
                        <option value="without_children">Without Linked Children</option>
                        <option value="has_phone">Has Mobile Login Number</option>
                    </select>
                </div>

                <div>
                    <button type="button" class="btn-portal" onclick="resetFilters()" style="background: #f1f5f9; color: #475569; border: 2px solid #cbd5e1; box-shadow: none; padding: 12px 18px;">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 0.85rem; color: #64748b; font-weight: 700;">
                <div>
                    Showing <span id="visibleCount" style="color: var(--portal-blue); font-weight: 800;"><?php echo $total_parents_count; ?></span> of <?php echo $total_parents_count; ?> registered parent profiles
                </div>
                <div>
                    <span style="color: #166534;"><i class="fas fa-shield-alt"></i> Mobile Number Auto-Login Ready</span>
                </div>
            </div>
        </div>

        <!-- Parents List Table -->
        <div class="portal-card" style="padding: 20px;">
            <div class="portal-table-container">
                <table class="parents-table" id="parentsTable">
                    <thead>
                        <tr>
                            <th>Parent Profile</th>
                            <th>Portal Login ID</th>
                            <th>Auth Password</th>
                            <th>Linked Student(s)</th>
                            <th style="text-align: right; width: 140px;">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parents_data)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-user-friends" style="font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                                    <b>No parent accounts created yet.</b>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parents_data as $row): 
                                $phone = !empty($row['phone']) ? $row['phone'] : '';
                                $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                                $email = !empty($row['email']) ? $row['email'] : '';
                                $children_raw = !empty($row['children_details_raw']) ? explode('||', $row['children_details_raw']) : [];
                                $children_names_simple = !empty($row['children_names_raw']) ? str_replace('||', ', ', $row['children_names_raw']) : 'Candidate';

                                $initials = strtoupper(substr($row['parent_name'], 0, 2));

                                // Fetch linked student IDs for modal editing
                                $pid = (int)$row['id'];
                                $c_res = $conn->query("SELECT id FROM students WHERE parent_id = $pid");
                                $c_ids = [];
                                if ($c_res) {
                                    while($cr = $c_res->fetch_assoc()) $c_ids[] = (int)$cr['id'];
                                }
                                $row['student_ids'] = $c_ids;

                                // Compiled WhatsApp credentials message
                                $wa_parent = (string)($row['parent_name'] ?? 'Parent');
                                $wa_child = (string)($children_names_simple ?? 'Student');
                                $wa_login_msg = "*AWASIYA BAL SHIKSHAN SANSTHAN (ABSS)*\n"
                                              . "📍 Imamganj, Gaya (Bihar)\n"
                                              . "------------------------------------------\n"
                                              . "🔐 *PARENT PORTAL LOGIN CREDENTIALS*\n\n"
                                              . "Respected *" . addslashes($wa_parent) . "* (Parent of *" . addslashes($wa_child) . "*),\n\n"
                                              . "Your ABSS Parent Portal account is active with instant mobile access:\n\n"
                                              . "🔗 *Portal Login Link:* " . $parent_portal_url . "\n"
                                              . "👤 *Login ID / Username:* " . ($phone ?: $email) . "\n"
                                              . "🔑 *Password:* " . ($phone ?: 'Your Mobile Number') . "\n\n"
                                              . "Check daily attendance, test marks, rank report cards, and fee receipts on your mobile phone.\n\n"
                                              . "_ABSS Administration Desk_";
                                $encoded_wa_msg = rawurlencode($wa_login_msg);
                            ?>
                                <tr class="parent-row"
                                    data-name="<?php echo strtolower(htmlspecialchars((string)($row['parent_name'] ?? ''))); ?>"
                                    data-phone="<?php echo strtolower(htmlspecialchars((string)($phone ?? ''))); ?>"
                                    data-email="<?php echo strtolower(htmlspecialchars((string)($email ?? ''))); ?>"
                                    data-children="<?php echo strtolower(htmlspecialchars((string)($children_names_simple ?? ''))); ?>"
                                    data-has-children="<?php echo $row['children_count'] > 0 ? 'yes' : 'no'; ?>"
                                    data-has-phone="<?php echo !empty($phone) ? 'yes' : 'no'; ?>">

                                    <td>
                                        <div style="display: flex; align-items: center; gap: 14px;">
                                            <div class="parent-avatar-badge"><?php echo $initials; ?></div>
                                            <div>
                                                <div style="font-weight: 800; color: var(--portal-dark); font-size: 1.02rem;">
                                                    <?php echo htmlspecialchars($row['parent_name']); ?>
                                                </div>
                                                <small style="color: #64748b; font-weight: 600;">
                                                    Registered: <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($phone)): ?>
                                            <div><span class="credential-pill"><i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($phone); ?></span></div>
                                            <?php if (!empty($email) && strpos($email, '@abss.in') === false && strpos($email, '@abss.local') === false): ?>
                                                <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 2px;"><?php echo htmlspecialchars($email); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="credential-pill"><?php echo htmlspecialchars($email); ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="pass-default-badge" title="Default password matches contact mobile number">
                                            <i class="fas fa-key"></i> <?php echo !empty($phone) ? htmlspecialchars($phone) : 'Default ID'; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (empty($children_raw)): ?>
                                            <span style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">No students linked</span>
                                        <?php else: ?>
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                <?php foreach ($children_raw as $ch): ?>
                                                    <span class="child-pill">
                                                        <i class="fas fa-user-graduate" style="color: var(--portal-blue);"></i>
                                                        <?php echo htmlspecialchars($ch); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <!-- WhatsApp Credentials Dispatch -->
                                            <?php if (!empty($clean_phone)): ?>
                                                <a href="https://api.whatsapp.com/send?phone=<?php echo (strlen($clean_phone) == 10 ? '91' . $clean_phone : $clean_phone); ?>&text=<?php echo $encoded_wa_msg; ?>" 
                                                   target="_blank" 
                                                   class="act-btn act-btn-wa" 
                                                   title="Send Login Credentials via WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Reset to Mobile Number Password -->
                                            <button type="button" 
                                                    class="act-btn act-btn-key" 
                                                    title="Reset Password to Mobile Number"
                                                    onclick="confirmResetPassword(<?php echo (int)$row['id']; ?>, '<?php echo addslashes((string)($row['parent_name'] ?? 'Parent')); ?>', '<?php echo addslashes((string)($phone ?? '')); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>

                                            <!-- Edit Account -->
                                            <button type="button" 
                                                    class="act-btn act-btn-edit" 
                                                    title="Edit Parent Profile & Students"
                                                    onclick='editParent(<?php echo json_encode($row); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete Account -->
                                            <a href="parents.php?delete=<?php echo (int)$row['id']; ?>" 
                                               class="act-btn act-btn-del" 
                                               title="Delete Parent Account"
                                               onclick="return confirm('Are you sure you want to delete account for <?php echo addslashes((string)($row['parent_name'] ?? 'Parent')); ?>? Students will be unlinked but not deleted.')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hidden Form for Reset Password -->
        <form id="resetPassForm" method="POST" action="" style="display: none;">
            <input type="hidden" name="reset_to_phone" value="1">
            <input type="hidden" name="parent_id" id="reset_parent_id">
        </form>

        <!-- ============================================== -->
        <!-- ADD / EDIT PARENT CREDENTIALS MODAL            -->
        <!-- ============================================== -->
        <div class="modal" id="parentModal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                    <div>
                        <h2 id="modalTitle" style="color: var(--portal-dark); font-weight: 800; font-size: 1.5rem; margin: 0;">Create Parent Login</h2>
                        <small style="color: #64748b; font-weight: 600;">Automated Mobile Portal Account Setup</small>
                    </div>
                    <button type="button" onclick="hideModal()" style="background: #f1f5f9; border: none; font-size: 1.2rem; cursor: pointer; color: #475569; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">✕</button>
                </div>

                <div class="notice-info-box">
                    <i class="fas fa-mobile-alt" style="font-size: 1.4rem; color: #16a34a; flex-shrink: 0;"></i>
                    <div>
                        <b>Instant Auto-Credentials:</b> The parent's mobile phone number automatically acts as both their <b>Login ID</b> and default <b>Password</b> for the Parent Portal.
                    </div>
                </div>

                <form action="" method="POST" id="parentForm">
                    <input type="hidden" name="id" id="parent_id">

                    <div class="portal-input-group">
                        <label for="parent_name"><i class="fas fa-user"></i> Parent / Guardian Full Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="parent_name" id="parent_name" placeholder="Father's or Guardian's name" required>
                    </div>

                    <div class="portal-form-row">
                        <div class="portal-input-group">
                            <label for="phone"><i class="fas fa-phone-alt"></i> Mobile Number (Login ID & Pass) <span style="color: #ef4444;">*</span></label>
                            <input type="tel" name="phone" id="phone" placeholder="10-digit mobile number" required oninput="syncDefaultPasswordHint(this.value)">
                        </div>

                        <div class="portal-input-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email (Optional)</label>
                            <input type="email" name="email" id="email" placeholder="Optional email address">
                        </div>
                    </div>

                    <div class="portal-input-group">
                        <label for="password"><i class="fas fa-lock"></i> Custom Password (Optional)</label>
                        <input type="text" name="password" id="password" placeholder="Leave empty to use Mobile Number as password">
                        <small id="passHelpText" style="color: #64748b; font-weight: 600; display: block; margin-top: 4px;">
                            💡 Default password will automatically be set to the mobile number above.
                        </small>
                    </div>

                    <div class="portal-input-group">
                        <label><i class="fas fa-user-graduate"></i> Link Student / Child Candidate(s)</label>
                        <div class="students-checklist-box">
                            <?php if (empty($all_students)): ?>
                                <div style="color: #94a3b8; font-size: 0.85rem; padding: 10px;">No students enrolled yet.</div>
                            <?php else: ?>
                                <?php foreach ($all_students as $student): ?>
                                    <label class="student-chk-item">
                                        <input type="checkbox" name="students[]" value="<?php echo $student['id']; ?>" class="student-chk" id="student_chk_<?php echo $student['id']; ?>">
                                        <span>
                                            <?php echo htmlspecialchars($student['name']); ?> 
                                            <small style="color: var(--portal-blue); font-weight: 800;">
                                                (<?php echo htmlspecialchars($student['class_admitted'] ?: 'Class 5'); ?><?php echo !empty($student['reg_no']) ? ' - ' . htmlspecialchars($student['reg_no']) : ''; ?>)
                                            </small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 14px; margin-top: 25px;">
                        <button type="submit" name="save_parent" class="btn-portal" style="flex: 1; padding: 15px;">
                            <i class="fas fa-check-circle"></i> Save & Provision Login
                        </button>
                        <button type="button" class="btn-portal" onclick="hideModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; box-shadow: none; padding: 15px 22px;">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Interactive Search & Modal Scripts -->
    <script>
        function showCreateModal() {
            document.getElementById('parentModal').style.display = 'flex';
            document.getElementById('parent_id').value = '';
            document.getElementById('modalTitle').innerText = 'Create Parent Login';
            document.getElementById('passHelpText').innerText = '💡 Default password will automatically be set to the mobile number above.';
            document.querySelector('#parentModal form').reset();
            
            // Clear checkmarks
            document.querySelectorAll('.student-chk').forEach(c => c.checked = false);
        }

        function hideModal() {
            document.getElementById('parentModal').style.display = 'none';
        }

        function editParent(data) {
            document.getElementById('parentModal').style.display = 'flex';
            document.getElementById('parent_id').value = data.id;
            document.getElementById('modalTitle').innerText = 'Edit Parent Credentials';
            document.getElementById('passHelpText').innerText = '💡 Leave empty to keep current password unchanged.';
            document.getElementById('parent_name').value = data.parent_name || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('password').value = '';

            // Reset checks
            document.querySelectorAll('.student-chk').forEach(c => c.checked = false);

            // Set checks
            if (data.student_ids && Array.isArray(data.student_ids)) {
                data.student_ids.forEach(id => {
                    const chk = document.getElementById('student_chk_' + id);
                    if (chk) chk.checked = true;
                });
            }
        }

        function syncDefaultPasswordHint(val) {
            const passInput = document.getElementById('password');
            if (val && !document.getElementById('parent_id').value) {
                passInput.placeholder = 'Default: ' + val;
            }
        }

        function confirmResetPassword(parentId, parentName, phone) {
            if (!phone) {
                alert('This parent has no mobile number registered. Please edit the parent profile and add a mobile number first.');
                return;
            }
            if (confirm('Reset password for ' + parentName + ' to their mobile number (' + phone + ')?')) {
                document.getElementById('reset_parent_id').value = parentId;
                document.getElementById('resetPassForm').submit();
            }
        }

        // Live Real-Time Filtering
        function filterParents() {
            const query = document.getElementById('parentSearchInput').value.toLowerCase().trim();
            const filterType = document.getElementById('parentTypeFilter').value;
            const rows = document.querySelectorAll('#parentsTable tbody tr.parent-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const phone = row.getAttribute('data-phone') || '';
                const email = row.getAttribute('data-email') || '';
                const children = row.getAttribute('data-children') || '';
                const hasChildren = row.getAttribute('data-has-children') || '';
                const hasPhone = row.getAttribute('data-has-phone') || '';

                const matchesQuery = query === '' || 
                    name.includes(query) || 
                    phone.includes(query) || 
                    email.includes(query) || 
                    children.includes(query);

                let matchesFilter = true;
                if (filterType === 'with_children') matchesFilter = hasChildren === 'yes';
                else if (filterType === 'without_children') matchesFilter = hasChildren === 'no';
                else if (filterType === 'has_phone') matchesFilter = hasPhone === 'yes';

                if (matchesQuery && matchesFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        function resetFilters() {
            document.getElementById('parentSearchInput').value = '';
            document.getElementById('parentTypeFilter').value = '';
            filterParents();
        }
    </script>
</body>
</html>
