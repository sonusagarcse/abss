<?php
// admin/export_students_excel.php - Export Complete Student Directory to Excel (CSV with UTF-8 BOM)
require_once 'includes/auth.php';

// Check filters if passed
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$mode_filter = isset($_GET['mode']) ? trim($_GET['mode']) : '';
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';

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

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "
    SELECT 
        s.*,
        p.parent_name AS parent_profile_name,
        p.email AS parent_account_email
    FROM students s
    LEFT JOIN parents p ON s.parent_id = p.id
    $where_sql
    ORDER BY s.id ASC
";
$result = $conn->query($query);

$filename = "ABSS_Students_Complete_Details_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header with Complete Details
fputcsv($output, [
    'S.No.',
    'Registration No',
    'Student Full Name',
    'Father / Parent Name',
    'Guardian Relationship',
    'Mobile / Phone Number',
    'Parent Email',
    'Village / Home Address',
    'City',
    'State',
    'PIN Code',
    'Class Admitted',
    'Scholar Mode',
    'Target School / Program',
    'Academic Group',
    'Date of Birth',
    'Gender',
    'Previous School',
    'Admission Date',
    'Base Monthly Fee (Rs.)',
    'Monthly Discount (Rs.)',
    'Net Monthly Fee (Rs.)',
    'Security Deposit (Rs.)',
    'Registration Fee (Rs.)',
    'Admission Fee (Rs.)',
    'Advance Amount (Rs.)',
    'Emergency Contact Name',
    'Emergency Phone',
    'Allergies Detail',
    'Medical Condition',
    'Status',
    'Registration Timestamp'
]);

$sno = 1;
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $base = (float)($row['base_fee'] ?? 0);
        $disc = (float)($row['monthly_discount'] ?? 0);
        $net = max(0, $base - $disc);

        fputcsv($output, [
            $sno++,
            $row['reg_no'] ?: '—',
            $row['name'] ?: '—',
            $row['parent_name'] ?: ($row['parent_profile_name'] ?: '—'),
            $row['guardian_relationship'] ?: 'Father',
            $row['phone'] ?: '—',
            $row['guardian_email'] ?: ($row['parent_account_email'] ?: '—'),
            $row['home_address'] ?: '—',
            $row['city'] ?: '—',
            $row['state'] ?: 'Bihar',
            $row['zip_code'] ?: '—',
            $row['class_admitted'] ?: '—',
            $row['scholar_mode'] ?: 'Day Scholar',
            $row['target_school'] ?: 'Standard',
            $row['academic_group'] ?: 'Group A',
            $row['dob'] ?: '—',
            $row['gender'] ?: '—',
            $row['prev_school'] ?: '—',
            $row['admission_date'] ?: '—',
            number_format($base, 2, '.', ''),
            number_format($disc, 2, '.', ''),
            number_format($net, 2, '.', ''),
            number_format((float)($row['security_amount'] ?? 0), 2, '.', ''),
            number_format((float)($row['registration_fee'] ?? 0), 2, '.', ''),
            number_format((float)($row['admission_fee'] ?? 0), 2, '.', ''),
            number_format((float)($row['advance_amount'] ?? 0), 2, '.', ''),
            $row['emergency_contact_name'] ?: '—',
            $row['emergency_phone'] ?: '—',
            $row['has_allergies'] ? ($row['allergies_detail'] ?: 'Yes') : 'None',
            $row['has_medical_condition'] ? ($row['medical_condition_detail'] ?: 'Yes') : 'None',
            strtoupper($row['status'] ?? 'active'),
            $row['created_at'] ?: '—'
        ]);
    }
}

fclose($output);
exit();
