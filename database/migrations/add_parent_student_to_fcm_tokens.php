<?php
require_once __DIR__ . '/../../config/db.php';

$conn = getDB();

$checkP = $conn->query("SHOW COLUMNS FROM fcm_tokens LIKE 'parent_id'");
if ($checkP && $checkP->num_rows === 0) {
    if ($conn->query("ALTER TABLE fcm_tokens ADD COLUMN parent_id INT(11) NULL AFTER app_version, ADD INDEX (parent_id)")) {
        echo "Successfully added parent_id column and index to fcm_tokens.\n";
    } else {
        echo "Error adding parent_id: " . $conn->error . "\n";
    }
} else {
    echo "Column parent_id already exists in fcm_tokens.\n";
}

$checkS = $conn->query("SHOW COLUMNS FROM fcm_tokens LIKE 'student_id'");
if ($checkS && $checkS->num_rows === 0) {
    if ($conn->query("ALTER TABLE fcm_tokens ADD COLUMN student_id INT(11) NULL AFTER parent_id, ADD INDEX (student_id)")) {
        echo "Successfully added student_id column and index to fcm_tokens.\n";
    } else {
        echo "Error adding student_id: " . $conn->error . "\n";
    }
} else {
    echo "Column student_id already exists in fcm_tokens.\n";
}
