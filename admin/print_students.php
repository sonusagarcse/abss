<?php
// admin/print_students.php - Printable Student Directory Report & PDF Generation View
require_once 'includes/auth.php';

// Site Settings
$settings_list = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$site_settings = [];
if ($settings_list) {
    while($set = $settings_list->fetch_assoc()) {
        $site_settings[$set['setting_key']] = $set['setting_value'];
    }
}

$school_name = $site_settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan';
$school_address = $site_settings['contact_address'] ?? 'Lok Kala Bhavan, Main Road, Imamganj, Gaya, Bihar - 824206';
$school_phone = $site_settings['contact_phone'] ?? '+91 9523012888';
$school_email = $site_settings['contact_email'] ?? 'abssimamganj@gmail.com';

// URL Filters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'active';
$mode_filter = isset($_GET['mode']) ? trim($_GET['mode']) : '';
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$where_clauses = [];
if ($status_filter !== '' && $status_filter !== 'all') {
    $safe_status = $conn->real_escape_string($status_filter);
    $where_clauses[] = "s.status = '$safe_status'";
}
if ($mode_filter !== '' && $mode_filter !== 'all') {
    $safe_mode = $conn->real_escape_string($mode_filter);
    if (stripos($safe_mode, 'tuition') !== false || stripos($safe_mode, 'tution') !== false) {
        $where_clauses[] = "(s.scholar_mode LIKE '%tuition%' OR s.scholar_mode LIKE '%tution%')";
    } else {
        $where_clauses[] = "s.scholar_mode = '$safe_mode'";
    }
}
if ($class_filter !== '' && $class_filter !== 'all') {
    $safe_class = $conn->real_escape_string($class_filter);
    $where_clauses[] = "s.class_admitted = '$safe_class'";
}
if ($search_query !== '') {
    $safe_q = $conn->real_escape_string($search_query);
    $where_clauses[] = "(s.name LIKE '%$safe_q%' OR s.reg_no LIKE '%$safe_q%' OR s.parent_name LIKE '%$safe_q%' OR s.phone LIKE '%$safe_q%' OR s.home_address LIKE '%$safe_q%')";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "
    SELECT 
        s.*,
        p.parent_name AS parent_profile_name
    FROM students s
    LEFT JOIN parents p ON s.parent_id = p.id
    $where_sql
    ORDER BY s.id ASC
";
$students_res = $conn->query($query);
$students = [];
$unique_classes = [];
if ($students_res) {
    while($row = $students_res->fetch_assoc()) {
        $students[] = $row;
        if (!empty($row['class_admitted'])) $unique_classes[$row['class_admitted']] = true;
    }
}

// Fetch all available classes for dropdown filter
$all_classes_res = $conn->query("SELECT DISTINCT class_admitted FROM students WHERE class_admitted IS NOT NULL AND class_admitted != '' ORDER BY class_admitted ASC");
$filter_classes = [];
if ($all_classes_res) {
    while($c = $all_classes_res->fetch_assoc()) {
        $filter_classes[] = $c['class_admitted'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory Report (Print / PDF) | <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            padding: 20px;
            font-size: 13px;
        }

        /* Top Action Bar (Web Screen Only) */
        .no-print-bar {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            padding: 14px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }
        .action-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-act:hover {
            transform: translateY(-1px);
        }
        .btn-print { background: #0f172a; color: #ffffff; }
        .btn-excel { background: #107c41; color: #ffffff; }
        .btn-back { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            background: #ffffff;
            color: #334155;
            outline: none;
        }

        /* Printable Paper Container */
        .paper-container {
            background: #ffffff;
            max-width: 1050px;
            margin: 0 auto;
            padding: 32px 36px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        /* Report Header */
        .report-header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }
        .school-title {
            font-size: 1.55rem;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }
        .school-meta {
            font-size: 0.82rem;
            color: #475569;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .report-title-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.82rem;
            padding: 4px 14px;
            border-radius: 50px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Meta Information Strip */
        .meta-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 14px;
            padding: 6px 10px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }

        /* Roster Table */
        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }
        .roster-table th {
            background: #0f172a;
            color: #ffffff;
            padding: 9px 10px;
            text-align: left;
            font-weight: 800;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid #0f172a;
        }
        .roster-table td {
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            color: #1e293b;
        }
        .roster-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .reg-badge-print {
            display: inline-block;
            font-family: 'Consolas', monospace;
            font-weight: 900;
            color: #0f172a;
            font-size: 0.88rem;
            letter-spacing: 0.03em;
        }

        .class-pill {
            display: inline-block;
            padding: 2px 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 800;
            border-radius: 5px;
            font-size: 0.78rem;
            border: 1px solid #bfdbfe;
        }

        /* Signatures Section */
        .signatures-grid {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 50px;
            padding-top: 20px;
        }
        .sig-block {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-top: 1.5px dashed #475569;
            margin-bottom: 6px;
        }
        .sig-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
        }

        /* Media Print Rules */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
                color: #000000;
            }
            .no-print {
                display: none !important;
            }
            .paper-container {
                max-width: 100%;
                padding: 0;
                box-shadow: none;
                border: none;
            }
            .roster-table th {
                background: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .roster-table td {
                border-color: #94a3b8 !important;
            }
            .roster-table tbody tr:nth-child(even) {
                background: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .class-pill {
                border: 1px solid #94a3b8;
                background: #ffffff !important;
                color: #000000 !important;
            }
            .report-title-badge {
                background: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr {
                page-break-inside: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Web Navigation & Action Bar -->
    <div class="no-print-bar no-print">
        <div class="action-group">
            <a href="students.php" class="btn-act btn-back">
                <i class="fas fa-arrow-left"></i> Student Registry
            </a>
            <button onclick="window.print()" class="btn-act btn-print">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <a href="export_students_excel.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="btn-act btn-excel">
                <i class="fas fa-file-excel"></i> Download Excel
            </a>
        </div>

        <!-- Filter Selects -->
        <form method="GET" action="" class="action-group" id="filterForm">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Students</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Students</option>
            </select>

            <select name="mode" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $mode_filter === 'all' || empty($mode_filter) ? 'selected' : ''; ?>>All Modes</option>
                <option value="Day Scholar" <?php echo $mode_filter === 'Day Scholar' ? 'selected' : ''; ?>>Day Scholar</option>
                <option value="Hostler" <?php echo $mode_filter === 'Hostler' ? 'selected' : ''; ?>>Hostler</option>
                <option value="Tuition" <?php echo stripos($mode_filter, 'tuition') !== false || stripos($mode_filter, 'tution') !== false ? 'selected' : ''; ?>>Tuition Only</option>
            </select>

            <select name="class" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $class_filter === 'all' || empty($class_filter) ? 'selected' : ''; ?>>All Classes</option>
                <?php foreach($filter_classes as $fc): ?>
                    <option value="<?php echo htmlspecialchars($fc); ?>" <?php echo $class_filter === $fc ? 'selected' : ''; ?>><?php echo htmlspecialchars($fc); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="q" placeholder="Filter keyword..." value="<?php echo htmlspecialchars($search_query); ?>" class="filter-select" style="width: 150px;">
            <button type="submit" class="btn-act" style="background: #3b82f6; color: #fff; padding: 8px 12px;"><i class="fas fa-search"></i></button>
            <?php if(!empty($mode_filter) || $status_filter !== 'active' || !empty($class_filter) || !empty($search_query)): ?>
                <a href="print_students.php" class="btn-act" style="background: #f1f5f9; color: #64748b; padding: 8px 12px;" title="Reset Filters"><i class="fas fa-undo"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="paper-container">
        <!-- Official Institute Header -->
        <div class="report-header">
            <h1 class="school-title"><?php echo htmlspecialchars($school_name); ?></h1>
            <div class="school-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($school_address); ?></span>
                <span style="margin: 0 8px;">•</span>
                <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($school_phone); ?></span>
                <span style="margin: 0 8px;">•</span>
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($school_email); ?></span>
            </div>
            <div class="report-title-badge">STUDENT REGISTRY & PROFILE DIRECTORY</div>
        </div>

        <!-- Meta Information Strip -->
        <div class="meta-strip">
            <div>
                <b>Total Students:</b> <?php echo count($students); ?> Candidates
                <?php if ($mode_filter && $mode_filter !== 'all'): ?>
                    <span style="margin-left: 8px; color: #2563eb;">[Mode: <?php echo htmlspecialchars($mode_filter); ?>]</span>
                <?php endif; ?>
                <?php if ($class_filter && $class_filter !== 'all'): ?>
                    <span style="margin-left: 8px; color: #16a34a;">[Class: <?php echo htmlspecialchars($class_filter); ?>]</span>
                <?php endif; ?>
            </div>
            <div>
                <b>Printed On:</b> <?php echo date('d M Y, h:i A'); ?>
            </div>
        </div>

        <!-- Details Table -->
        <table class="roster-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">#</th>
                    <th style="width: 110px;">Reg No.</th>
                    <th>Student Name</th>
                    <th>Father's Name</th>
                    <th>Village</th>
                    <th style="width: 90px; text-align: center;">Class</th>
                    <th style="width: 110px;">Mobile Number</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8; font-weight: 700;">
                            No student records found matching the selected criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $sno = 1;
                    foreach($students as $st): 
                        $parent = $st['parent_name'] ?: ($st['parent_profile_name'] ?: '—');
                        $village = $st['home_address'] ?: ($st['city'] ?: '—');
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700; color: #64748b;"><?php echo $sno++; ?></td>
                            <td>
                                <span class="reg-badge-print"><?php echo htmlspecialchars($st['reg_no'] ?: '—'); ?></span>
                            </td>
                            <td>
                                <strong style="text-transform: uppercase; color: #0f172a; font-weight: 800;">
                                    <?php echo htmlspecialchars($st['name']); ?>
                                </strong>
                            </td>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars($parent); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($village); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="class-pill"><?php echo htmlspecialchars($st['class_admitted'] ?: '—'); ?></span>
                            </td>
                            <td style="font-family: monospace; font-weight: 700; color: #0f172a;">
                                <?php echo htmlspecialchars($st['phone'] ?: '—'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Signatures & Official Endorsement -->
        <div class="signatures-grid">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Prepared By</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Verified By</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Principal / Director</div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => { window.print(); }, 400);
            });
        </script>
    <?php endif; ?>
</body>
</html>
