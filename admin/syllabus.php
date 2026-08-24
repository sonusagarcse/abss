<?php
require_once 'includes/auth.php';

$msg = '';
$msg_type = 'success';

// Handle POST Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_syllabus'])) {
    $group_key = $_POST['group_key'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $badge_text = trim($_POST['badge_text'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fas fa-book');
    $accent_color = trim($_POST['accent_color'] ?? '#2563eb');
    $overview = trim($_POST['overview'] ?? '');

    // Process Subjects Array
    $subjects_input = $_POST['subjects'] ?? [];
    $formatted_subjects = [];

    if (is_array($subjects_input)) {
        foreach ($subjects_input as $sub) {
            $sub_name = trim($sub['subject_name'] ?? '');
            if (empty($sub_name)) continue;

            $sub_icon = trim($sub['icon'] ?? 'fas fa-book');
            $raw_topics = trim($sub['topics'] ?? '');

            // Split topics by newline or comma if entered line by line
            $topics_list = [];
            if (!empty($raw_topics)) {
                $lines = preg_split('/\r\n|\r|\n/', $raw_topics);
                foreach ($lines as $line) {
                    $cleaned = trim($line, " \t\n\r\0\x0B•-");
                    if (!empty($cleaned)) {
                        $topics_list[] = $cleaned;
                    }
                }
            }

            $formatted_subjects[] = [
                'subject_name' => $sub_name,
                'icon' => $sub_icon,
                'topics' => $topics_list
            ];
        }
    }

    $subjects_json = json_encode($formatted_subjects, JSON_UNESCAPED_UNICODE);

    if (!empty($group_key) && !empty($title)) {
        $stmt = $conn->prepare("
            INSERT INTO syllabus_cards (group_key, title, subtitle, badge_text, icon, accent_color, overview, subjects_json) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                title = VALUES(title), 
                subtitle = VALUES(subtitle), 
                badge_text = VALUES(badge_text), 
                icon = VALUES(icon), 
                accent_color = VALUES(accent_color), 
                overview = VALUES(overview), 
                subjects_json = VALUES(subjects_json)
        ");
        $stmt->bind_param("ssssssss", $group_key, $title, $subtitle, $badge_text, $icon, $accent_color, $overview, $subjects_json);
        
        if ($stmt->execute()) {
            $_GET['tab'] = $group_key;
            $msg = "Syllabus details for <strong>" . htmlspecialchars($group_key) . "</strong> updated successfully!";
            $msg_type = 'success';
            
            // Log Activity
            if (function_exists('logActivity')) {
                logActivity('admin', $_SESSION['admin_id'] ?? 1, $_SESSION['admin_username'] ?? 'admin', 'syllabus_updated', "Updated $group_key syllabus card content");
            }
        } else {
            $msg = "Database Error: " . $stmt->error;
            $msg_type = 'danger';
        }
    } else {
        $msg = "Please provide valid title and group information.";
        $msg_type = 'warning';
    }
}

// Fetch all 4 syllabus cards
$cards_query = $conn->query("SELECT * FROM syllabus_cards ORDER BY id ASC");
$syllabus_cards = [];
if ($cards_query && $cards_query->num_rows > 0) {
    while ($row = $cards_query->fetch_assoc()) {
        $syllabus_cards[$row['group_key']] = $row;
    }
}

// Active Tab (preserve selected group tab on POST save)
$active_tab = $_POST['group_key'] ?? ($_GET['tab'] ?? 'Group A');
if (!array_key_exists($active_tab, $syllabus_cards) && !empty($syllabus_cards)) {
    $active_tab = array_key_first($syllabus_cards);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Syllabus | ABSS Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .syllabus-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .group-nav-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1px;
            overflow-x: auto;
        }

        .group-tab-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            padding: 12px 24px;
            border-radius: 12px 12px 0 0;
            font-weight: 800;
            color: #475569;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .group-tab-btn:hover {
            background: #ffffff;
            color: #2563eb;
        }

        .group-tab-btn.active {
            background: #ffffff;
            color: #2563eb;
            border-color: #2563eb #2563eb #ffffff #2563eb;
            box-shadow: 0 -4px 12px rgba(37, 99, 235, 0.08);
            position: relative;
            z-index: 2;
        }

        .edit-card-panel {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        .subject-editor-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.2s ease;
        }

        .subject-editor-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .remove-subject-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 800;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .remove-subject-btn:hover {
            background: #fca5a5;
        }

        .add-subject-btn {
            background: #eff6ff;
            color: #2563eb;
            border: 2px dashed #bfdbfe;
            border-radius: 14px;
            width: 100%;
            padding: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-subject-btn:hover {
            background: #dbeafe;
            border-color: #2563eb;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="syllabus-container">
                    
                    <!-- Page Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h1 style="font-size: 1.8rem; font-weight: 900; color: var(--portal-dark); margin: 0;">
                                <i class="fas fa-book-open" style="color: #2563eb; margin-right: 8px;"></i> Manage Academic Syllabus Cards
                            </h1>
                            <p style="color: #64748b; margin: 4px 0 0 0; font-size: 0.92rem; font-weight: 500;">
                                Customize Group A, B, C & D card content, subject headings, and detailed topics for the main website.
                            </p>
                        </div>
                        
                        <a href="../index.php#academic-syllabus" target="_blank" class="btn" style="background: #0f172a; color: #ffffff; font-weight: 800; border-radius: 12px; padding: 10px 18px; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-external-link-alt"></i> Preview on Home Page
                        </a>
                    </div>

                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-<?php echo $msg_type; ?>" style="border-radius: 14px; padding: 15px 20px; font-weight: 600; margin-bottom: 25px;">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Group Selector Tabs -->
                    <div class="group-nav-tabs">
                        <?php 
                        $group_keys = ['Group A', 'Group B', 'Group C', 'Group D'];
                        foreach ($group_keys as $g_key):
                            $card_data = $syllabus_cards[$g_key] ?? null;
                            $tab_accent = $card_data['accent_color'] ?? '#2563eb';
                            $tab_icon = $card_data['icon'] ?? 'fas fa-book';
                            $is_active = ($active_tab === $g_key);
                        ?>
                            <a href="syllabus.php?tab=<?php echo urlencode($g_key); ?>" class="group-tab-btn <?php echo $is_active ? 'active' : ''; ?>">
                                <i class="<?php echo htmlspecialchars($tab_icon); ?>" style="color: <?php echo htmlspecialchars($tab_accent); ?>;"></i>
                                <?php echo htmlspecialchars($g_key); ?>
                                <?php if ($card_data): ?>
                                    <span style="font-size: 0.72rem; opacity: 0.8; font-weight: 700; background: rgba(0,0,0,0.06); padding: 2px 8px; border-radius: 50px;">
                                        <?php echo htmlspecialchars($card_data['badge_text'] ?? ''); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Current Selected Group Editor -->
                    <?php 
                    $current_card = $syllabus_cards[$active_tab] ?? [
                        'group_key' => $active_tab,
                        'title' => "$active_tab - Academic Syllabus",
                        'subtitle' => 'Target Entrance Examination',
                        'badge_text' => 'Standard Batch',
                        'icon' => 'fas fa-book',
                        'accent_color' => '#2563eb',
                        'overview' => 'Comprehensive syllabus for entrance preparation.',
                        'subjects_json' => '[]'
                    ];
                    
                    $subjects_arr = json_decode($current_card['subjects_json'] ?? '[]', true);
                    if (!is_array($subjects_arr)) $subjects_arr = [];
                    ?>

                    <form action="syllabus.php?tab=<?php echo urlencode($active_tab); ?>" method="POST" class="edit-card-panel">
                        <input type="hidden" name="save_syllabus" value="1">
                        <input type="hidden" name="group_key" value="<?php echo htmlspecialchars($active_tab); ?>">

                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <span style="background: #eff6ff; color: #2563eb; font-size: 0.8rem; font-weight: 800; padding: 4px 14px; border-radius: 50px; text-transform: uppercase;">
                                    Editing Card: <?php echo htmlspecialchars($active_tab); ?>
                                </span>
                                <h2 style="font-size: 1.4rem; font-weight: 900; color: #0f172a; margin: 8px 0 0 0;">
                                    <?php echo htmlspecialchars($current_card['title']); ?>
                                </h2>
                            </div>

                            <button type="submit" class="btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; font-weight: 900; padding: 12px 28px; border-radius: 12px; border: none; cursor: pointer; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                                <i class="fas fa-save" style="margin-right: 6px;"></i> Save <?php echo htmlspecialchars($active_tab); ?> Changes
                            </button>
                        </div>

                        <!-- Card Configuration Grid -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 25px;">
                            <div>
                                <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                    Card Title <span style="color: #dc2626;">*</span>
                                </label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($current_card['title']); ?>" required style="width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 0.95rem;">
                            </div>

                            <div>
                                <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                    Subtitle / Subheading
                                </label>
                                <input type="text" name="subtitle" value="<?php echo htmlspecialchars($current_card['subtitle'] ?? ''); ?>" style="width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.95rem;">
                            </div>

                            <div>
                                <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                    Badge Pill Text
                                </label>
                                <input type="text" name="badge_text" value="<?php echo htmlspecialchars($current_card['badge_text'] ?? ''); ?>" placeholder="e.g. Foundation Batch" style="width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.95rem;">
                            </div>

                            <div>
                                <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                    Header FontAwesome Icon Class
                                </label>
                                <input type="text" name="icon" value="<?php echo htmlspecialchars($current_card['icon'] ?? 'fas fa-book'); ?>" placeholder="fas fa-cubes" style="width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.95rem;">
                            </div>

                            <div>
                                <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                    Accent Theme Color (Hex)
                                </label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="accentColorPicker" value="<?php echo htmlspecialchars($current_card['accent_color'] ?? '#2563eb'); ?>" onchange="document.getElementById('accentColorText').value = this.value;" style="width: 48px; height: 44px; border: none; border-radius: 10px; cursor: pointer;">
                                    <input type="text" id="accentColorText" name="accent_color" value="<?php echo htmlspecialchars($current_card['accent_color'] ?? '#2563eb'); ?>" onchange="document.getElementById('accentColorPicker').value = this.value;" style="flex: 1; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 0.95rem;">
                                </div>
                            </div>
                        </div>

                        <!-- Card Overview Text -->
                        <div style="margin-bottom: 30px;">
                            <label style="font-weight: 800; color: #334155; font-size: 0.88rem; display: block; margin-bottom: 6px;">
                                Group Overview / Short Summary
                            </label>
                            <textarea name="overview" rows="3" style="width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 0.95rem; line-height: 1.5;"><?php echo htmlspecialchars($current_card['overview'] ?? ''); ?></textarea>
                        </div>

                        <!-- Subjects & Topics Breakdown Section -->
                        <div style="margin-bottom: 25px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                                <div>
                                    <h3 style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin: 0;">
                                        <i class="fas fa-layer-group" style="color: #2563eb; margin-right: 6px;"></i> Subjects & Topic Headings
                                    </h3>
                                    <p style="color: #64748b; font-size: 0.85rem; margin: 3px 0 0 0; font-weight: 500;">
                                        Define the subjects displayed inside this card. Each subject includes an icon, title, and topic list.
                                    </p>
                                </div>
                            </div>

                            <div id="subjectsListContainer">
                                <?php if (!empty($subjects_arr)): ?>
                                    <?php foreach ($subjects_arr as $index => $sub): ?>
                                        <div class="subject-editor-card" id="subject_card_<?php echo $index; ?>">
                                            <button type="button" class="remove-subject-btn" onclick="removeSubjectCard('subject_card_<?php echo $index; ?>')">
                                                <i class="fas fa-trash-alt"></i> Remove Subject
                                            </button>

                                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px; padding-right: 140px;" class="subj-grid-resp">
                                                <div>
                                                    <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                                                        Subject Heading Name
                                                    </label>
                                                    <input type="text" name="subjects[<?php echo $index; ?>][subject_name]" value="<?php echo htmlspecialchars($sub['subject_name'] ?? ''); ?>" placeholder="e.g. Mathematics & Arithmetic" required style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 0.9rem;">
                                                </div>

                                                <div>
                                                    <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                                                        Icon Class
                                                    </label>
                                                    <input type="text" name="subjects[<?php echo $index; ?>][icon]" value="<?php echo htmlspecialchars($sub['icon'] ?? 'fas fa-book'); ?>" placeholder="fas fa-calculator" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.9rem;">
                                                </div>
                                            </div>

                                            <div>
                                                <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                                                    Syllabus Topics (Enter one topic per line)
                                                </label>
                                                <?php 
                                                $topics_text = "";
                                                if (!empty($sub['topics']) && is_array($sub['topics'])) {
                                                    $topics_text = implode("\n", $sub['topics']);
                                                }
                                                ?>
                                                <textarea name="subjects[<?php echo $index; ?>][topics]" rows="4" placeholder="Topic 1&#10;Topic 2&#10;Topic 3" style="width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 0.9rem; line-height: 1.5; font-family: monospace;"><?php echo htmlspecialchars($topics_text); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="add-subject-btn" onclick="addNewSubjectCard()">
                                <i class="fas fa-plus-circle" style="font-size: 1.1rem;"></i> Add Another Subject to <?php echo htmlspecialchars($active_tab); ?>
                            </button>
                        </div>

                        <!-- Submit Footer -->
                        <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: right;">
                            <button type="submit" class="btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff; font-weight: 900; padding: 14px 36px; border-radius: 12px; border: none; cursor: pointer; font-size: 1rem; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                                <i class="fas fa-save" style="margin-right: 8px;"></i> Save <?php echo htmlspecialchars($active_tab); ?> Changes
                            </button>
                        </div>
                    </form>

                </div>
    </main>

    <script>
        let subjectCount = <?php echo count($subjects_arr); ?>;

        function addNewSubjectCard() {
            const container = document.getElementById('subjectsListContainer');
            const newIndex = subjectCount++;
            const cardId = `subject_card_${newIndex}`;

            const html = `
                <div class="subject-editor-card" id="${cardId}">
                    <button type="button" class="remove-subject-btn" onclick="removeSubjectCard('${cardId}')">
                        <i class="fas fa-trash-alt"></i> Remove Subject
                    </button>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px; padding-right: 140px;" class="subj-grid-resp">
                        <div>
                            <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                                Subject Heading Name
                            </label>
                            <input type="text" name="subjects[${newIndex}][subject_name]" placeholder="e.g. Mental Ability & Reasoning" required style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 0.9rem;">
                        </div>

                        <div>
                            <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                                Icon Class
                            </label>
                            <input type="text" name="subjects[${newIndex}][icon]" value="fas fa-book" placeholder="fas fa-brain" style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.9rem;">
                        </div>
                    </div>

                    <div>
                        <label style="font-weight: 800; color: #475569; font-size: 0.82rem; display: block; margin-bottom: 4px;">
                            Syllabus Topics (Enter one topic per line)
                        </label>
                        <textarea name="subjects[${newIndex}][topics]" rows="4" placeholder="Topic 1&#10;Topic 2&#10;Topic 3" style="width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 0.9rem; line-height: 1.5; font-family: monospace;"></textarea>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', html);
        }

        function removeSubjectCard(cardId) {
            const card = document.getElementById(cardId);
            if (card) {
                card.remove();
            }
        }
    </script>
</body>
</html>
