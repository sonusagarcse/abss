<?php
// counselor/fees.php - Student Dues & Fee Arrears Calling Desk Bridge
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('fees');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/student_dues.php';
