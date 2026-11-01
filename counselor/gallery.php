<?php
// counselor/gallery.php - Full-Featured Module Bridge with Permission Gatekeeper
require_once __DIR__ . '/includes/auth.php';
require_counselor_module('gallery');

define('IS_COUNSELOR_VIEW', true);
require __DIR__ . '/../admin/gallery.php';
