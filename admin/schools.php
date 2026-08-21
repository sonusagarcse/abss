<?php
// admin/schools.php - Manage Entrance Coaching Programs & Target Schools
require_once 'includes/auth.php';

$msg = '';
$err = '';

// Handle Homepage Section Title & Subtitle Settings Save
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_section_header'])) {
    $subtitle = trim($_POST['coaching_section_subtitle'] ?? 'Competitive Preparation');
    $title    = trim($_POST['coaching_section_title'] ?? 'Entrance Coaching Programs');
    $desc     = trim($_POST['coaching_section_desc'] ?? '');

    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('coaching_section_subtitle', '" . $conn->real_escape_string($subtitle) . "') ON DUPLICATE KEY UPDATE setting_value = '" . $conn->real_escape_string($subtitle) . "'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('coaching_section_title', '" . $conn->real_escape_string($title) . "') ON DUPLICATE KEY UPDATE setting_value = '" . $conn->real_escape_string($title) . "'");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('coaching_section_desc', '" . $conn->real_escape_string($desc) . "') ON DUPLICATE KEY UPDATE setting_value = '" . $conn->real_escape_string($desc) . "'");

    if (function_exists('log_activity')) {
        log_activity('settings_updated', "Updated Entrance Coaching homepage section header");
    }
    $msg = "Homepage section header settings saved successfully.";
}

// Handle Add / Edit Program
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_program'])) {
    $id          = (int)($_POST['program_id'] ?? 0);
    $school_name = trim($_POST['school_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon'] ?? 'fas fa-graduation-cap');
    $badge_text  = trim($_POST['badge_text'] ?? '');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($icon)) $icon = 'fas fa-graduation-cap';

    if (!empty($school_name)) {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE schools SET school_name=?, description=?, icon=?, badge_text=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssssiii", $school_name, $description, $icon, $badge_text, $sort_order, $is_active, $id);
            if ($stmt->execute()) {
                $msg = "Program '<strong>" . htmlspecialchars($school_name) . "</strong>' updated successfully.";
                if (function_exists('log_activity')) {
                    log_activity('school_updated', "Updated coaching program: $school_name");
                }
            } else {
                $err = "Failed to update program: " . $conn->error;
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO schools (school_name, description, icon, badge_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $school_name, $description, $icon, $badge_text, $sort_order, $is_active);
            if ($stmt->execute()) {
                $msg = "New Coaching Program '<strong>" . htmlspecialchars($school_name) . "</strong>' added successfully.";
                if (function_exists('log_activity')) {
                    log_activity('school_added', "Added coaching program: $school_name");
                }
            } else {
                $err = "Failed to add program: " . $conn->error;
            }
        }
    } else {
        $err = "Program name cannot be empty.";
    }
}

// Handle Quick Toggle Status
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $conn->query("UPDATE schools SET is_active = IF(is_active=1, 0, 1) WHERE id = $id");
    header("Location: schools.php?msg=" . urlencode("Status updated successfully."));
    exit();
}

// Handle Delete Program
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del_q = $conn->prepare("SELECT school_name FROM schools WHERE id = ?");
    $del_q->bind_param("i", $id);
    $del_q->execute();
    $del_row = $del_q->get_result()->fetch_assoc();
    $del_name = $del_row['school_name'] ?? 'Program';

    $conn->query("DELETE FROM schools WHERE id = $id");
    if (function_exists('log_activity')) {
        log_activity('school_deleted', "Deleted coaching program: $del_name");
    }
    header("Location: schools.php?msg=" . urlencode("Program '$del_name' deleted successfully."));
    exit();
}

// Handle Seed Defaults
if (isset($_GET['seed_defaults'])) {
    $default_programs = [
        ["Netarhat Residential", "Class 6 Entrance Batch", "fas fa-graduation-cap", "State Premier", 1],
        ["Sainik School (AISSEE)", "All India Sainik School", "fas fa-shield-alt", "National Merit", 2],
        ["Navodaya Vidyalaya", "JNVST Entrance Batch", "fas fa-award", "Top Selection", 3],
        ["Simultala Residential", "State Merit Batch", "fas fa-book-reader", "Merit Program", 4],
        ["BHU CHS Entrance", "Banaras Hindu University", "fas fa-university", "Central School", 5],
        ["Rashtriya Military School", "RMS Entrance Batch", "fas fa-medal", "Defense Wings", 6]
    ];

    foreach ($default_programs as $dp) {
        $check = $conn->prepare("SELECT id FROM schools WHERE school_name = ?");
        $check->bind_param("s", $dp[0]);
        $check->execute();
        if ($check->get_result()->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO schools (school_name, description, icon, badge_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $ins->bind_param("ssssi", $dp[0], $dp[1], $dp[2], $dp[3], $dp[4]);
            $ins->execute();
        } else {
            $upd = $conn->prepare("UPDATE schools SET description=?, icon=?, badge_text=?, sort_order=? WHERE school_name=?");
            $upd->bind_param("sssis", $dp[1], $dp[2], $dp[3], $dp[4], $dp[0]);
            $upd->execute();
        }
    }
    header("Location: schools.php?msg=" . urlencode("Standard 6 target coaching programs seeded successfully."));
    exit();
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

$settings = getAllSettings();
$sec_subtitle = $settings['coaching_section_subtitle'] ?? 'Competitive Preparation';
$sec_title    = $settings['coaching_section_title'] ?? 'Entrance Coaching Programs';
$sec_desc     = $settings['coaching_section_desc'] ?? 'Specialized coaching & residential batches for prestigious national & state residential school entrance examinations.';

$schools_query = $conn->query("SELECT * FROM schools ORDER BY sort_order ASC, id ASC");
$programs_list = [];
$active_count = 0;
if ($schools_query) {
    while ($row = $schools_query->fetch_assoc()) {
        $programs_list[] = $row;
        if ($row['is_active'] == 1) $active_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrance Coaching Programs & Target Schools | ABSS Admin</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .page-header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .stat-box-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .program-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .program-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08);
            border-color: #cbd5e1;
        }
        .program-card.inactive-card {
            background: #f8fafc;
            opacity: 0.8;
            border-style: dashed;
        }

        .program-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .program-icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: #eff6ff;
            color: var(--portal-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            border: 1px solid #dbeafe;
        }

        .action-btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .icon-action-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: #fff; }
        .btn-toggle-on { background: #dcfce7; color: #15803d; }
        .btn-toggle-on:hover { background: #15803d; color: #fff; }
        .btn-toggle-off { background: #fee2e2; color: #dc2626; }
        .btn-toggle-off:hover { background: #dc2626; color: #fff; }
        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-delete:hover { background: #ef4444; color: #fff; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            z-index: 4000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 24px;
            max-width: 580px;
            width: 100%;
            padding: 32px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.25);
            border: 1px solid #e2e8f0;
            max-height: 90vh;
            overflow-y: auto;
        }

        .icon-picker-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .icon-choice {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.2s;
        }
        .icon-choice:hover, .icon-choice.selected {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header-container">
            <div>
                <h1 style="font-size: 1.85rem; font-weight: 900; margin: 0; color: var(--portal-dark); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-graduation-cap" style="color: var(--portal-blue);"></i> Entrance Coaching Programs
                </h1>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.95rem;">
                    Manage the target schools and entrance examination batches displayed on the Homepage and Admission Form.
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?seed_defaults=1" class="btn-portal" style="background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; box-shadow: none;" onclick="return confirm('Sync standard 6 residential school programs (Netarhat, Sainik, Navodaya, Simultala, BHU, RMS)?')">
                    <i class="fas fa-sync-alt"></i> Reset Standard Programs
                </a>
                <button type="button" class="btn-portal" onclick="openProgramModal()">
                    <i class="fas fa-plus-circle"></i> Add Coaching Program
                </button>
            </div>
        </div>

        <!-- Alert Notifications -->
        <?php if($msg): ?>
            <div style="background:#f0fdf4; color:#166534; padding:14px 20px; border-radius:14px; margin-bottom:20px; font-weight:700; border:1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <div style="background:#fef2f2; color:#dc2626; padding:14px 20px; border-radius:14px; margin-bottom:20px; font-weight:700; border:1px solid #fecaca; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i> <?php echo $err; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Metrics Grid -->
        <div class="stat-banner-grid">
            <div class="stat-box">
                <div class="stat-box-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fas fa-school"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.4rem; font-weight: 900; color: #0f172a;"><?php echo count($programs_list); ?></h3>
                    <small style="color: #64748b; font-weight: 700;">Total Programs Configured</small>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box-icon" style="background: #f0fdf4; color: #16a34a;">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.4rem; font-weight: 900; color: #16a34a;"><?php echo $active_count; ?></h3>
                    <small style="color: #64748b; font-weight: 700;">Active on Homepage</small>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-box-icon" style="background: #faf5ff; color: #7c3aed;">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #7c3aed;">Homepage Section</h3>
                    <a href="../index.php#exams" target="_blank" style="color: #7c3aed; font-size: 0.82rem; font-weight: 700; text-decoration: none;">View Live on Website <i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
        </div>

        <!-- SECTION 1: HOMEPAGE SECTION HEADER CUSTOMIZER -->
        <div class="portal-card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--portal-dark);">
                    <i class="fas fa-heading" style="color: var(--portal-blue); margin-right: 8px;"></i> Homepage Section Titles & Tagline
                </h3>
                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Controls the #exams section on index.php</span>
            </div>

            <form action="" method="POST">
                <div class="portal-form-row">
                    <div class="portal-input-group">
                        <label>Section Subtitle / Eyebrow Text</label>
                        <input type="text" name="coaching_section_subtitle" value="<?php echo htmlspecialchars($sec_subtitle); ?>" placeholder="e.g. Competitive Preparation" required>
                    </div>

                    <div class="portal-input-group">
                        <label>Section Main Heading</label>
                        <input type="text" name="coaching_section_title" value="<?php echo htmlspecialchars($sec_title); ?>" placeholder="e.g. Entrance Coaching Programs" required>
                    </div>
                </div>

                <div class="portal-input-group">
                    <label>Section Description / Introductory Text</label>
                    <input type="text" name="coaching_section_desc" value="<?php echo htmlspecialchars($sec_desc); ?>" placeholder="Brief description displayed under the heading...">
                </div>

                <button type="submit" name="save_section_header" class="btn-portal" style="padding: 12px 24px;">
                    <i class="fas fa-save"></i> Save Section Header
                </button>
            </form>
        </div>

        <!-- SECTION 2: COACHING PROGRAMS LIST -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 900; color: var(--portal-dark);">
                <i class="fas fa-th-large" style="color: var(--portal-blue); margin-right: 8px;"></i> Program Cards
            </h3>
            <button type="button" class="btn-portal" onclick="openProgramModal()" style="font-size: 0.88rem; padding: 10px 18px;">
                <i class="fas fa-plus"></i> Add New Program
            </button>
        </div>

        <?php if (empty($programs_list)): ?>
            <div class="portal-card" style="text-align: center; padding: 50px 20px;">
                <i class="fas fa-graduation-cap" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                <h3 style="color: #475569; margin: 0 0 10px;">No Coaching Programs Added</h3>
                <p style="color: #94a3b8; max-width: 450px; margin: 0 auto 20px;">Click the button below to load the 6 standard residential school programs or add custom batches.</p>
                <a href="?seed_defaults=1" class="btn-portal"><i class="fas fa-magic"></i> Load Standard 6 Programs</a>
            </div>
        <?php else: ?>
            <div class="programs-grid">
                <?php foreach ($programs_list as $prog): 
                    $is_act = (int)$prog['is_active'];
                    $icon_c = !empty($prog['icon']) ? $prog['icon'] : 'fas fa-graduation-cap';
                ?>
                    <div class="program-card <?php echo $is_act ? '' : 'inactive-card'; ?>">
                        <div>
                            <div class="program-card-header">
                                <div class="program-icon-badge">
                                    <i class="<?php echo htmlspecialchars($icon_c); ?>"></i>
                                </div>

                                <div class="action-btn-group">
                                    <a href="?toggle_status=<?php echo $prog['id']; ?>" class="icon-action-btn <?php echo $is_act ? 'btn-toggle-on' : 'btn-toggle-off'; ?>" title="<?php echo $is_act ? 'Active (Click to Hide)' : 'Hidden (Click to Activate)'; ?>">
                                        <i class="fas <?php echo $is_act ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                    </a>
                                    <button type="button" class="icon-action-btn btn-edit" onclick='editProgram(<?php echo json_encode($prog); ?>)' title="Edit Program">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $prog['id']; ?>" class="icon-action-btn btn-delete" onclick="return confirm('Delete program \'<?php echo addslashes($prog['school_name']); ?>\'?')" title="Delete Program">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                                    <?php echo htmlspecialchars($prog['school_name']); ?>
                                </h3>
                                <?php if (!empty($prog['badge_text'])): ?>
                                    <span style="background: #eff6ff; color: #2563eb; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 50px; border: 1px solid #dbeafe; white-space: nowrap;">
                                        <?php echo htmlspecialchars($prog['badge_text']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p style="margin: 0 0 15px; color: #64748b; font-size: 0.88rem; font-weight: 600;">
                                <?php echo htmlspecialchars($prog['description'] ?: 'Class 6 / Entrance Batch'); ?>
                            </p>
                        </div>

                        <div style="border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; font-weight: 700; color: #94a3b8;">
                            <span><i class="fas fa-sort-numeric-down"></i> Order: <?php echo (int)$prog['sort_order']; ?></span>
                            <span>
                                <?php if ($is_act): ?>
                                    <span style="color: #16a34a;"><i class="fas fa-check-circle"></i> Visible on Web</span>
                                <?php else: ?>
                                    <span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Hidden</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal for Adding / Editing Coaching Program -->
    <div id="programModal" class="modal" onclick="if(event.target===this) closeProgramModal();">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <h3 id="modalTitle" style="margin: 0; font-size: 1.3rem; font-weight: 900; color: #0f172a;">
                    <i class="fas fa-graduation-cap" style="color: var(--portal-blue);"></i> Add Coaching Program
                </h3>
                <button type="button" onclick="closeProgramModal()" style="background: #f1f5f9; border: none; font-size: 1.2rem; cursor: pointer; color: #475569; width: 36px; height: 36px; border-radius: 50%;">✕</button>
            </div>

            <form action="" method="POST" id="programForm">
                <input type="hidden" name="program_id" id="modal_program_id" value="0">

                <div class="portal-input-group">
                    <label>Target School / Program Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="school_name" id="modal_school_name" placeholder="e.g. Netarhat Residential, Sainik School (AISSEE)" required>
                </div>

                <div class="portal-input-group">
                    <label>Batch Name / Subtitle Tag (Displayed below School Name on Homepage)</label>
                    <input type="text" name="description" id="modal_description" placeholder="e.g. Class 6 / Entrance Batch">
                </div>

                <div class="portal-form-row">
                    <div class="portal-input-group">
                        <label>Badge Tag (Optional)</label>
                        <input type="text" name="badge_text" id="modal_badge_text" placeholder="e.g. State Merit, Top Batch">
                    </div>

                    <div class="portal-input-group">
                        <label>Sort / Display Order</label>
                        <input type="number" name="sort_order" id="modal_sort_order" value="1" min="0" step="1">
                    </div>
                </div>

                <!-- Icon Picker -->
                <div class="portal-input-group">
                    <label>FontAwesome Icon Class</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="icon" id="modal_icon" value="fas fa-graduation-cap" onkeyup="updateIconPreview(this.value)" placeholder="e.g. fas fa-graduation-cap" style="flex: 1;">
                        <div id="modalIconPreview" style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid #dbeafe;">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <small style="color: #64748b; font-weight: 600; display: block; margin-top: 6px;">Choose a preset icon or type custom FontAwesome class:</small>
                    <div class="icon-picker-grid">
                        <div class="icon-choice" onclick="selectIcon('fas fa-graduation-cap')"><i class="fas fa-graduation-cap"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-shield-alt')"><i class="fas fa-shield-alt"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-award')"><i class="fas fa-award"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-book-reader')"><i class="fas fa-book-reader"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-university')"><i class="fas fa-university"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-medal')"><i class="fas fa-medal"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-school')"><i class="fas fa-school"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-user-graduate')"><i class="fas fa-user-graduate"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-atom')"><i class="fas fa-atom"></i></div>
                        <div class="icon-choice" onclick="selectIcon('fas fa-brain')"><i class="fas fa-brain"></i></div>
                    </div>
                </div>

                <div class="portal-input-group" style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #334155;">
                        <input type="checkbox" name="is_active" id="modal_is_active" value="1" checked style="width: 18px; height: 18px; accent-color: var(--portal-blue);">
                        Active & Visible on Homepage & Admission Forms
                    </label>
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="submit" name="save_program" class="btn-portal" style="flex: 1; padding: 14px;">
                        <i class="fas fa-check-circle"></i> Save Program
                    </button>
                    <button type="button" class="btn-portal" onclick="closeProgramModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; box-shadow: none; padding: 14px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openProgramModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-graduation-cap" style="color: var(--portal-blue);"></i> Add Coaching Program';
            document.getElementById('modal_program_id').value = '0';
            document.getElementById('modal_school_name').value = '';
            document.getElementById('modal_description').value = '';
            document.getElementById('modal_badge_text').value = '';
            document.getElementById('modal_sort_order').value = '<?php echo count($programs_list) + 1; ?>';
            document.getElementById('modal_icon').value = 'fas fa-graduation-cap';
            document.getElementById('modal_is_active').checked = true;
            updateIconPreview('fas fa-graduation-cap');
            document.getElementById('programModal').style.display = 'flex';
        }

        function editProgram(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit" style="color: var(--portal-blue);"></i> Edit Coaching Program';
            document.getElementById('modal_program_id').value = data.id;
            document.getElementById('modal_school_name').value = data.school_name || '';
            document.getElementById('modal_description').value = data.description || '';
            document.getElementById('modal_badge_text').value = data.badge_text || '';
            document.getElementById('modal_sort_order').value = data.sort_order || '0';
            const icon = data.icon || 'fas fa-graduation-cap';
            document.getElementById('modal_icon').value = icon;
            document.getElementById('modal_is_active').checked = (data.is_active == 1);
            updateIconPreview(icon);
            document.getElementById('programModal').style.display = 'flex';
        }

        function closeProgramModal() {
            document.getElementById('programModal').style.display = 'none';
        }

        function selectIcon(iconClass) {
            document.getElementById('modal_icon').value = iconClass;
            updateIconPreview(iconClass);
        }

        function updateIconPreview(iconClass) {
            const preview = document.getElementById('modalIconPreview');
            preview.innerHTML = '<i class="' + (iconClass ? iconClass : 'fas fa-graduation-cap') + '"></i>';
        }
    </script>
</body>
</html>
