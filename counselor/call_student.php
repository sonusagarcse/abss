<?php
// counselor/call_student.php - Calling Desk & Follow-up Panel Bridge
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('fees');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/call_student.php';
