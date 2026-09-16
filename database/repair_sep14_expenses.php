<?php
// database/repair_sep14_expenses.php
require_once __DIR__ . '/../config/db.php';
$conn = getDB();

$conn->begin_transaction();
try {
    echo "Starting Database Repair for September 14, 2026 expenses...\n\n";

    // 1. Rishi Kumar (SID 9) - FG #100
    $conn->query("UPDATE fees_generated SET amount = 20.00, remark = 'Daily Expense. Medicine (Expense): ₹20.00' WHERE id = 100 AND student_id = 9");
    echo "1. Rishi Kumar: FG #100 corrected to ₹20.00\n";

    // 2. Sashi Kumar (SID 10) - FG #101
    $conn->query("UPDATE fees_generated SET amount = 250.00, remark = 'Daily Expense. Hindusthan Olympiad-2026 (Expense): ₹250.00' WHERE id = 101 AND student_id = 10");
    echo "2. Sashi Kumar: FG #101 corrected to ₹250.00\n";

    // 3. Aditya Chandan (SID 25) - FG #98
    $conn->query("UPDATE fees_generated SET amount = 20.00, remark = 'Daily Expense. Medicine (Expense): ₹20.00' WHERE id = 98 AND student_id = 25");
    echo "3. Aditya Chandan: FG #98 corrected to ₹20.00\n";

    // 4. Sagar Kumar (SID 38) - FG #99
    $conn->query("UPDATE fees_generated SET amount = 20.00, remark = 'Daily Expense. Medicine (Expense): ₹20.00' WHERE id = 99 AND student_id = 38");
    echo "4. Sagar Kumar: FG #99 corrected to ₹20.00\n";

    // 5. Yuvraj Kumar (SID 11) - FG #42 (add ₹20 + ₹250 = ₹270)
    $fg42 = $conn->query("SELECT remark FROM fees_generated WHERE id = 42")->fetch_assoc();
    $rem42 = trim($fg42['remark']);
    if (strpos($rem42, 'Hindusthan Olympiad-2026') === false) {
        $rem42 .= " | Medicine (Expense): ₹20.00 | Hindusthan Olympiad-2026 (Expense): ₹250.00";
        $conn->query("UPDATE fees_generated SET amount = 8370.00, remark = '" . $conn->real_escape_string($rem42) . "' WHERE id = 42");
        echo "5. Yuvraj Kumar: FG #42 updated to ₹8,370.00\n";
    }

    // 6. Satyam Kumar (SID 6) - FG #79 (add ₹20 + ₹250 = ₹270)
    $fg79 = $conn->query("SELECT remark FROM fees_generated WHERE id = 79")->fetch_assoc();
    $rem79 = trim($fg79['remark']);
    if (strpos($rem79, 'Hindusthan Olympiad-2026') === false) {
        $rem79 .= " | Medicine (Expense): ₹20.00 | Hindusthan Olympiad-2026 (Expense): ₹250.00";
        $conn->query("UPDATE fees_generated SET amount = 8162.00, remark = '" . $conn->real_escape_string($rem79) . "' WHERE id = 79");
        echo "6. Satyam Kumar: FG #79 updated to ₹8,162.00\n";
    }

    // 7. Sudrashan Kumar (SID 2) - FG #31 (add ₹20 + ₹250 = ₹270)
    $fg31 = $conn->query("SELECT remark FROM fees_generated WHERE id = 31")->fetch_assoc();
    $rem31 = trim($fg31['remark']);
    if (strpos($rem31, 'Hindusthan Olympiad-2026') === false) {
        $rem31 .= " | Medicine (Expense): ₹20.00 | Hindusthan Olympiad-2026 (Expense): ₹250.00";
        $conn->query("UPDATE fees_generated SET amount = 15410.00, remark = '" . $conn->real_escape_string($rem31) . "' WHERE id = 31");
        echo "7. Sudrashan Kumar: FG #31 updated to ₹15,410.00\n";
    }

    // 8. Sashi Raj (SID 23) - FG #84 (add ₹20)
    $fg84 = $conn->query("SELECT remark FROM fees_generated WHERE id = 84")->fetch_assoc();
    $rem84 = trim($fg84['remark']);
    if (substr_count($rem84, 'Medicine') < 2) {
        $rem84 .= " | Medicine (Expense): ₹20.00";
        $conn->query("UPDATE fees_generated SET amount = 2554.00, remark = '" . $conn->real_escape_string($rem84) . "' WHERE id = 84");
        echo "8. Sashi Raj: FG #84 updated to ₹2,554.00\n";
    }

    // 9. Ayush Raj (SID 12) - FG #53 (add ₹20)
    $fg53 = $conn->query("SELECT remark FROM fees_generated WHERE id = 53")->fetch_assoc();
    $rem53 = trim($fg53['remark']);
    if (substr_count($rem53, 'Medicine') < 2) {
        $rem53 .= " | Medicine (Expense): ₹20.00";
        $conn->query("UPDATE fees_generated SET amount = 2795.00, remark = '" . $conn->real_escape_string($rem53) . "' WHERE id = 53");
        echo "9. Ayush Raj: FG #53 updated to ₹2,795.00\n";
    }

    // 10. Pari Kumari (SID 27) - FG #58 (add Olympiad ₹250)
    $fg58 = $conn->query("SELECT remark FROM fees_generated WHERE id = 58")->fetch_assoc();
    $rem58 = trim($fg58['remark']);
    if (strpos($rem58, 'Hindusthan Olympiad-2026') === false) {
        // Insert Olympiad before the last payment
        $payment_needle = "| Payment received on 2026-09-14 (-₹250.00) (Rcpt #85)";
        if (strpos($rem58, $payment_needle) !== false) {
            $rem58 = str_replace($payment_needle, "| Hindusthan Olympiad-2026 (Expense): ₹250.00 " . $payment_needle, $rem58);
        } else {
            $rem58 .= " | Hindusthan Olympiad-2026 (Expense): ₹250.00";
        }
        $conn->query("UPDATE fees_generated SET amount = 1012.00, remark = '" . $conn->real_escape_string($rem58) . "' WHERE id = 58");
        echo "10. Pari Kumari: FG #58 updated to ₹1,012.00 and remark includes Hindusthan Olympiad-2026\n";
    }

    // Update payment #85 month_for / description note to clearly show Hindusthan Olympiad-2026
    $conn->query("UPDATE fee_payments SET month_for = 'September (Hindusthan Olympiad-2026)' WHERE id = 85 AND student_id = 27");
    echo "11. Pari Kumari Payment #85 updated to 'September (Hindusthan Olympiad-2026)'\n";

    $conn->commit();
    echo "\nAll 10 students repaired successfully in database!\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error during repair: " . $e->getMessage() . "\n";
}
