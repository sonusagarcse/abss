<?php
// counselor/ajax_send_due_email.php - Dues Email Dispatch & PDF Download Bridge
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('fees');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/ajax_send_due_email.php';

