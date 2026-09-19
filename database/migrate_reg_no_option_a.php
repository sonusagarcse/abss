<?php
/**
 * Migration Script: Option A
 * Renumber student registration numbers from 'ABSS-2026-XXXX' to 'IMG26XXXX'
 * starting with IMG260001 in order of student ID.
 * Preserves backup in `old_reg_no`.
 */
require_once __DIR__ . '/../config/db.php';
$conn = getDB();

echo "Starting Migration: Student Registration Number to IMG26XXXX...\n";

// 1. Ensure `old_reg_no` column exists in students table
$checkCol = $conn->query("SHOW COLUMNS FROM students LIKE 'old_reg_no'");
if ($checkCol && $checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE students ADD COLUMN old_reg_no VARCHAR(30) NULL AFTER reg_no");
    echo "Added column 'old_reg_no' to students table.\n";
} else {
    echo "Column 'old_reg_no' already exists.\n";
}

// 2. Backup current reg_no into old_reg_no if not already populated
$conn->query("UPDATE students SET old_reg_no = reg_no WHERE (old_reg_no IS NULL OR old_reg_no = '') AND reg_no IS NOT NULL");
echo "Backed up existing registration numbers into 'old_reg_no'.\n";

// 3. Fetch all students ordered by ID ASC
$studentsRes = $conn->query("SELECT id, name, reg_no, old_reg_no FROM students ORDER BY id ASC");
$students = [];
while ($row = $studentsRes->fetch_assoc()) {
    $students[] = $row;
}

echo "Found " . count($students) . " students to update.\n\n";

$conn->begin_transaction();
try {
    $seq = 1;
    $prefix = 'IMG' . date('y'); // IMG26

    $stmt = $conn->prepare("UPDATE students SET reg_no = ? WHERE id = ?");

    foreach ($students as $st) {
        $new_reg = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $stmt->bind_param("si", $new_reg, $st['id']);
        $stmt->execute();
        
        echo sprintf("ID: %-3d | %-25s | Old: %-15s => New: %-12s\n", $st['id'], $st['name'], $st['reg_no'], $new_reg);
        $seq++;
    }

    $conn->commit();
    echo "\nSuccessfully updated all " . count($students) . " student registration numbers!\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "\nError occurred during migration: " . $e->getMessage() . "\n";
    exit(1);
}
