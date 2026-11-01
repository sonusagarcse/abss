<?php
// counselor/results.php - Full-Featured Module Bridge with Permission Gatekeeper
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('results');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/results.php';
