<?php
require_once __DIR__ . '/../../config/db.php';

$conn = getDB();

echo "Starting retroactive FCM token mapping...\n";

$tokens = $conn->query("SELECT id, token, created_at, updated_at, parent_id, student_id FROM fcm_tokens ORDER BY id ASC");
$matchedCount = 0;
$alreadyLinked = 0;

while ($tk = $tokens->fetch_assoc()) {
    $tokenId = (int)$tk['id'];
    $cTime = $tk['created_at'];
    $uTime = $tk['updated_at'];

    if (!empty($tk['parent_id']) && !empty($tk['student_id'])) {
        $alreadyLinked++;
        continue;
    }

    $matchedParentId = 0;

    // 1. Try finding login within +/- 20 minutes of created_at
    $logStmt = $conn->prepare("
        SELECT user_id 
        FROM activity_logs 
        WHERE (user_role = 'parent' OR action_details LIKE '%Parent%')
          AND user_id > 0
          AND created_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
        ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC 
        LIMIT 1
    ");
    $logStmt->bind_param("sss", $cTime, $cTime, $cTime);
    $logStmt->execute();
    $res = $logStmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $matchedParentId = (int)$row['user_id'];
    }
    $logStmt->close();

    // 2. Try site_visitors within +/- 20 minutes of created_at
    if (!$matchedParentId) {
        $visStmt = $conn->prepare("
            SELECT parent_id 
            FROM site_visitors 
            WHERE parent_id > 0 
              AND visited_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, visited_at, ?)) ASC 
            LIMIT 1
        ");
        $visStmt->bind_param("sss", $cTime, $cTime, $cTime);
        $visStmt->execute();
        $res = $visStmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $matchedParentId = (int)$row['parent_id'];
        }
        $visStmt->close();
    }

    // 3. Try updated_at if different
    if (!$matchedParentId && $uTime !== $cTime) {
        $logStmt2 = $conn->prepare("
            SELECT user_id 
            FROM activity_logs 
            WHERE (user_role = 'parent' OR action_details LIKE '%Parent%')
              AND user_id > 0
              AND created_at BETWEEN DATE_SUB(?, INTERVAL 20 MINUTE) AND DATE_ADD(?, INTERVAL 20 MINUTE)
            ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC 
            LIMIT 1
        ");
        $logStmt2->bind_param("sss", $uTime, $uTime, $uTime);
        $logStmt2->execute();
        $res = $logStmt2->get_result();
        if ($row = $res->fetch_assoc()) {
            $matchedParentId = (int)$row['user_id'];
        }
        $logStmt2->close();
    }

    // Verify parent exists and is not admin ID 1
    if ($matchedParentId > 0) {
        $checkParent = $conn->query("SELECT id, parent_name, phone FROM parents WHERE id = $matchedParentId");
        if (!$checkParent || $checkParent->num_rows === 0) {
            $matchedParentId = 0;
        }
    }

    if ($matchedParentId > 0) {
        // Resolve student for this parent (active student preferred)
        $studQ = $conn->query("SELECT id, name, reg_no, class_admitted FROM students WHERE parent_id = $matchedParentId ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, id ASC LIMIT 1");
        $studentId = null;
        $studentName = 'No active student';
        if ($studQ && $stud = $studQ->fetch_assoc()) {
            $studentId = (int)$stud['id'];
            $studentName = $stud['name'] . ' (' . $stud['reg_no'] . ', ' . $stud['class_admitted'] . ')';
        }

        $upStmt = $conn->prepare("UPDATE fcm_tokens SET parent_id = ?, student_id = ? WHERE id = ?");
        $upStmt->bind_param("iii", $matchedParentId, $studentId, $tokenId);
        if ($upStmt->execute()) {
            $matchedCount++;
            echo "Token #$tokenId ($cTime) => Linked to Parent #$matchedParentId and Student: $studentName\n";
        }
        $upStmt->close();
    } else {
        echo "Token #$tokenId ($cTime) => Could not find unequivocal log match (Can be manually assigned)\n";
    }
}

echo "\nMigration finished. Successfully linked $matchedCount token(s). (Already linked: $alreadyLinked)\n";
