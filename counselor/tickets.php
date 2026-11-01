<?php
// counselor/tickets.php - Full-Featured Module Bridge with Permission Gatekeeper
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('tickets');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/tickets.php';
