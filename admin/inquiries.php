<?php
require_once 'includes/auth.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM inquiries WHERE id = $del_id");
    header("Location: inquiries.php?msg=deleted");
    exit();
}

// Handle Status Update
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE inquiries SET status = '$status' WHERE id = $id");
    header("Location: inquiries.php");
    exit();
}

$status_filter = $_GET['filter'] ?? 'all';
$query_sql = "SELECT * FROM inquiries";
if ($status_filter !== 'all') {
    $escaped_f = $conn->real_escape_string($status_filter);
    $query_sql .= " WHERE status = '$escaped_f'";
}
$query_sql .= " ORDER BY created_at DESC";
$inquiries = $conn->query($query_sql);

// Count summary
$total_inq = 0;
$new_inq = 0;
$contacted_inq = 0;
$admitted_inq = 0;

$count_res = $conn->query("SELECT status, COUNT(*) as cnt FROM inquiries GROUP BY status");
if ($count_res) {
    while($r = $count_res->fetch_assoc()) {
        $st = $r['status'] ?: 'new';
        $cnt = (int)$r['cnt'];
        $total_inq += $cnt;
        if ($st === 'new' || $st === 'pending') $new_inq += $cnt;
        elseif ($st === 'contacted') $contacted_inq += $cnt;
        elseif ($st === 'admitted') $admitted_inq += $cnt;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inquiries & Queries | ABSS Admin Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .stats-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-pill {
            background: #ffffff;
            border: 1px solid #eef2f6;
            border-radius: 18px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .stat-pill .icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .action-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 20px;
            border: 1px solid #eef2f6;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .filter-pill-btn {
            padding: 7px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 800;
            text-decoration: none;
            color: #64748b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .filter-pill-btn:hover { background: #eef2ff; color: var(--portal-blue); }
        .filter-pill-btn.active { background: var(--portal-blue); color: #fff; border-color: var(--portal-blue); box-shadow: 0 4px 12px rgba(13,71,161,0.25); }

        .table-card { background: #fff; border-radius: 24px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #eef2f6; overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        th { text-align: left; padding: 12px 18px; color: var(--portal-blue); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 16px 18px; background: #fff; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; color: #475569; font-weight: 500; vertical-align: middle; }
        td:first-child { border-left: 1px solid #f1f5f9; border-radius: 16px 0 0 16px; }
        td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 16px 16px 0; }
        
        .status-badge { padding: 4px 10px; border-radius: 100px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; display: inline-block; }
        .status-new, .status-pending { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-contacted { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
        .status-admitted { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-closed { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        .btn-sm-action { padding: 6px 12px; font-size: 0.75rem; border-radius: 8px; text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; margin-right: 4px; border: 1px solid #e2e8f0; transition: all 0.2s; }
        .btn-mark { background: #f8fafc; color: var(--portal-blue); }
        .btn-mark:hover { background: var(--portal-blue); color: #fff; border-color: var(--portal-blue); }
        .btn-del { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }
        .btn-del:hover { background: #ef4444; color: #fff; }
        .btn-attach { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; font-weight: 700; }
        .btn-attach:hover { background: #16a34a; color: #fff; }
        .btn-wa { background: #dcfce7; color: #15803d; border-color: #bbf7d0; font-weight: 800; }
        .btn-wa:hover { background: #15803d; color: #fff; }

        /* Modal Styles */
        .msg-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(5px); display: none; align-items: center; justify-content: center; z-index: 99999; padding: 20px; }
        .msg-modal-card { background: #fff; width: 100%; max-width: 580px; border-radius: 24px; padding: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h1 style="font-size: clamp(1.4rem, 2.5vw, 1.8rem); font-weight: 900; color: #0f172a; margin: 0 0 4px 0;">Lead & Contact Queries</h1>
                <p style="color: #64748b; margin: 0; font-size: 0.88rem;">Monitor and manage website queries, admission leads, and contact inquiries.</p>
            </div>
            <a href="../contact.php" target="_blank" class="btn-portal" style="width: auto; padding: 8px 16px; font-size: 0.82rem; background: #eff6ff; color: var(--portal-blue); border: 1px solid #bfdbfe; text-decoration: none;">
                <i class="fas fa-external-link-alt"></i> Open Contact Form
            </a>
        </header>

        <!-- Stats Ribbon -->
        <div class="stats-ribbon">
            <div class="stat-pill">
                <div class="icon" style="background: #eef2ff; color: #0d47a1;"><i class="fas fa-inbox"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #0f172a;"><?= $total_inq ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Total Queries</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #eff6ff; color: #1d4ed8;"><i class="fas fa-bell"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #1d4ed8;"><?= $new_inq ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">New / Pending</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #fff7ed; color: #ea580c;"><i class="fas fa-phone-alt"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #ea580c;"><?= $contacted_inq ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Contacted</span>
                </div>
            </div>
            <div class="stat-pill">
                <div class="icon" style="background: #f0fdf4; color: #16a34a;"><i class="fas fa-user-check"></i></div>
                <div>
                    <strong style="display: block; font-size: 1.1rem; color: #16a34a;"><?= $admitted_inq ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Admitted</span>
                </div>
            </div>
        </div>

        <div class="action-toolbar">
            <div class="filter-pills">
                <a href="inquiries.php?filter=all" class="filter-pill-btn <?= $status_filter === 'all' ? 'active' : '' ?>">All (<?= $total_inq ?>)</a>
                <a href="inquiries.php?filter=new" class="filter-pill-btn <?= $status_filter === 'new' ? 'active' : '' ?>">New (<?= $new_inq ?>)</a>
                <a href="inquiries.php?filter=contacted" class="filter-pill-btn <?= $status_filter === 'contacted' ? 'active' : '' ?>">Contacted (<?= $contacted_inq ?>)</a>
                <a href="inquiries.php?filter=admitted" class="filter-pill-btn <?= $status_filter === 'admitted' ? 'active' : '' ?>">Admitted (<?= $admitted_inq ?>)</a>
            </div>

            <div style="flex: 1; max-width: 300px; min-width: 200px;">
                <input type="text" id="inquirySearch" placeholder="Search by name, phone, message..." style="width: 100%; padding: 9px 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.88rem; outline: none; font-weight: 600;" onkeyup="filterInquiries()">
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name & Contact</th>
                        <th>Category / Exam</th>
                        <th>Subject & Message</th>
                        <th>Attachment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inquiries && $inquiries->num_rows > 0): ?>
                        <?php while($row = $inquiries->fetch_assoc()): 
                            $name = $row['candidate_name'] ?? ($row['name'] ?? 'Visitor');
                            $phone = $row['parent_phone'] ?? ($row['phone'] ?? '');
                            $email = $row['email'] ?? '';
                            $exam = $row['inquiry_type'] ?? ($row['target_exam'] ?? ($row['target_school'] ?? 'General Inquiry'));
                            $subject = $row['subject'] ?? '';
                            $message = $row['message'] ?? '';
                            $attachment = $row['attachment'] ?? '';
                            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                            if (strlen($clean_phone) === 10) $clean_phone = '91' . $clean_phone;
                        ?>
                        <tr class="inquiry-row" data-search="<?= htmlspecialchars(strtolower($name . ' ' . $phone . ' ' . $email . ' ' . $subject . ' ' . $message . ' ' . $exam)) ?>">
                            <td>
                                <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?php echo htmlspecialchars($name); ?></div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 3px;">
                                    <span style="font-size: 0.82rem; color: #059669; font-weight: 700;">
                                        <i class="fas fa-phone-alt" style="font-size:0.75rem;"></i> <?php echo htmlspecialchars($phone); ?>
                                    </span>
                                    <?php if (!empty($clean_phone)): ?>
                                        <a href="https://wa.me/<?= htmlspecialchars($clean_phone) ?>?text=Hello%20<?= urlencode($name) ?>%2C%20regarding%20your%20query%20at%20ABSS%20Imamganj..." target="_blank" class="btn-sm-action btn-wa" title="Reply via WhatsApp">
                                            <i class="fab fa-whatsapp"></i> Chat
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($email)): ?>
                                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                        <i class="fas fa-envelope" style="font-size:0.75rem;"></i> <?php echo htmlspecialchars($email); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-block; background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.78rem;">
                                    <?php echo htmlspecialchars($exam); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($subject)): ?>
                                    <strong style="display: block; color: #1e293b; font-size: 0.88rem; margin-bottom: 2px;"><?php echo htmlspecialchars($subject); ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($message)): ?>
                                    <div style="color: #64748b; font-size: 0.82rem; max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($message); ?>
                                    </div>
                                    <button type="button" onclick="openMsgModal('<?php echo htmlspecialchars(addslashes($name)); ?>', '<?php echo htmlspecialchars(addslashes($subject)); ?>', '<?php echo htmlspecialchars(addslashes($message)); ?>', '<?php echo htmlspecialchars(addslashes($phone)); ?>', '<?php echo htmlspecialchars(addslashes($email)); ?>')" style="background: none; border: none; color: #2563eb; font-size: 0.78rem; font-weight: 800; cursor: pointer; padding: 0; margin-top: 3px;">
                                        View Full Query →
                                    </button>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.82rem;">Direct lead inquiry</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($attachment)): ?>
                                    <a href="../<?php echo htmlspecialchars($attachment); ?>" target="_blank" class="btn-sm-action btn-attach" download>
                                        <i class="fas fa-paperclip"></i> Download File
                                    </a>
                                <?php else: ?>
                                    <span style="color: #cbd5e1; font-size: 0.8rem;">No file</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($row['status'] ?: 'new'); ?>">
                                    <?php echo htmlspecialchars($row['status'] ?: 'new'); ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap; font-size: 0.8rem; color: #64748b;">
                                <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    <?php if($row['status'] == 'new' || $row['status'] == 'pending' || empty($row['status'])): ?>
                                        <a href="?id=<?php echo $row['id']; ?>&status=contacted" class="btn-sm-action btn-mark" title="Mark as Contacted">Contacted</a>
                                    <?php elseif($row['status'] == 'contacted'): ?>
                                        <a href="?id=<?php echo $row['id']; ?>&status=admitted" class="btn-sm-action btn-mark" style="color:#16a34a;" title="Mark as Admitted">Admitted</a>
                                    <?php endif; ?>
                                    <a href="?delete_id=<?php echo $row['id']; ?>" class="btn-sm-action btn-del" onclick="return confirm('Are you sure you want to delete this query?');" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                No inquiries or contact queries found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Message Details Modal -->
    <div class="msg-modal-overlay" id="msgModal">
        <div class="msg-modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px;">
                <h3 id="modalName" style="margin: 0; font-size: 1.2rem; color: #0f172a; font-weight: 800;">Query Details</h3>
                <button type="button" onclick="closeMsgModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin-bottom: 12px; font-size: 0.88rem;">
                <strong style="color: #64748b;">Contact:</strong> <span id="modalContact" style="font-weight: 700; color: #0f172a;"></span>
            </div>
            <div style="margin-bottom: 12px; font-size: 0.88rem;">
                <strong style="color: #64748b;">Subject:</strong> <span id="modalSubject" style="font-weight: 700; color: #2563eb;"></span>
            </div>
            <div style="margin-bottom: 20px;">
                <strong style="display: block; color: #64748b; font-size: 0.85rem; margin-bottom: 6px;">Message Content:</strong>
                <div id="modalMsg" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; font-size: 0.92rem; color: #334155; line-height: 1.6; max-height: 250px; overflow-y: auto;"></div>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="closeMsgModal()" style="background: #0f172a; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openMsgModal(name, subject, message, phone, email) {
            document.getElementById('modalName').innerText = name;
            document.getElementById('modalContact').innerText = phone + (email ? ' (' + email + ')' : '');
            document.getElementById('modalSubject').innerText = subject || 'General Query';
            document.getElementById('modalMsg').innerText = message;
            document.getElementById('msgModal').style.display = 'flex';
        }
        function closeMsgModal() {
            document.getElementById('msgModal').style.display = 'none';
        }
        document.getElementById('msgModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeMsgModal();
        });

        function filterInquiries() {
            const filter = document.getElementById('inquirySearch').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.inquiry-row');
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>


