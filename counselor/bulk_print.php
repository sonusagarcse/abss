<?php
// counselor/bulk_print.php - Bulk Print Invoices Bridge
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('fees');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/bulk_print.php';
