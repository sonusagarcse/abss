<?php
// includes/security.php - Global Security Configuration

// Prevent framing to mitigate Clickjacking
header("X-Frame-Options: SAMEORIGIN");
// Enable Cross-Site Scripting (XSS) filter
header("X-XSS-Protection: 1; mode=block");
// Prevent MIME-sniffing
header("X-Content-Type-Options: nosniff");

// Start secure sessions with 1-year persistence for Web & App
if (session_status() === PHP_SESSION_NONE) {
    // 1 Year Session Lifetime (31536000 seconds)
    $oneYear = 31536000;
    @ini_set('session.gc_maxlifetime', (string)$oneYear);
    @ini_set('session.cookie_lifetime', (string)$oneYear);

    // Robust HTTPS detection (handles reverse proxies, Cloudflare, cPanel, Nginx)
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    // Cookie params: empty domain allows browser to automatically bind to the exact host (works on localhost, subdomains, ports)
    session_set_cookie_params([
        'lifetime' => $oneYear,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Generate a CSRF token and store it in the session
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the provided CSRF token against the session
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Generate the token immediately so it's ready for forms
generate_csrf_token();

/**
 * Secure File Upload Validator & Handler
 * Prevents malicious file execution, MIME spoofing, directory traversal, and oversized payloads.
 *
 * @param array $file Array from $_FILES['key']
 * @param string $destination_dir Relative or absolute directory path (e.g. __DIR__ . '/../uploads/inquiries/')
 * @param string $public_prefix Prefix path stored in DB (e.g. 'uploads/inquiries/')
 * @param array $allowed_extensions Allowed extensions whitelist (lowercase)
 * @param int $max_size_bytes Maximum file size in bytes (default 5MB)
 * @return array ['success' => bool, 'file_path' => string|null, 'error' => string|null, 'original_name' => string|null]
 */
function validate_and_save_upload($file, $destination_dir, $public_prefix = 'uploads/', $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'], $max_size_bytes = 5242880) {
    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return ['success' => false, 'error' => 'No file was selected for upload.', 'file_path' => null];
    }

    // 1. Check for standard PHP upload error codes
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
        ];
        return ['success' => false, 'error' => $error_messages[$file['error']] ?? 'Unknown upload error occurred.', 'file_path' => null];
    }

    // 2. Check File Size
    if ($file['size'] > $max_size_bytes || $file['size'] <= 0) {
        $max_mb = round($max_size_bytes / (1024 * 1024), 1);
        return ['success' => false, 'error' => "File exceeds the allowed size limit of {$max_mb} MB.", 'file_path' => null];
    }

    // 3. Extension Validation & Path Sanitization
    $original_name = basename($file['name']);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    // Disallow dangerous extensions / multiple dots with dangerous extensions
    $forbidden_patterns = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'vbs', 'scr', 'cgi', 'pl', 'py', 'svg', 'html', 'htm', 'asp', 'aspx'];
    $name_parts = explode('.', strtolower($original_name));
    foreach ($name_parts as $part) {
        if (in_array($part, $forbidden_patterns, true)) {
            return ['success' => false, 'error' => 'Security Warning: Execution of script or unsafe files is prohibited.', 'file_path' => null];
        }
    }

    if (!in_array($extension, array_map('strtolower', $allowed_extensions), true)) {
        $allowed_str = strtoupper(implode(', ', $allowed_extensions));
        return ['success' => false, 'error' => "Invalid file format. Only {$allowed_str} files are accepted.", 'file_path' => null];
    }

    // 4. Validate MIME Type via finfo / mime_content_type
    $tmp_file = $file['tmp_name'];
    if (!file_exists($tmp_file) || !is_uploaded_file($tmp_file)) {
        return ['success' => false, 'error' => 'Security Error: Invalid uploaded temporary file.', 'file_path' => null];
    }

    $detected_mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $tmp_file);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $detected_mime = mime_content_type($tmp_file);
    }

    $mime_map = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png', 'image/x-png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-word', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream']
    ];

    if (!empty($detected_mime) && isset($mime_map[$extension])) {
        if (!in_array($detected_mime, $mime_map[$extension], true)) {
            return ['success' => false, 'error' => 'Security Error: File MIME type does not match its extension signature.', 'file_path' => null];
        }
    }

    // 5. If it is an image, verify image header integrity
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        $image_info = @getimagesize($tmp_file);
        if ($image_info === false) {
            return ['success' => false, 'error' => 'Corrupt or invalid image file detected.', 'file_path' => null];
        }
    }

    // 6. Ensure destination directory exists
    $clean_dest_dir = rtrim($destination_dir, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($clean_dest_dir)) {
        if (!@mkdir($clean_dest_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Server error: Unable to initialize upload directory.', 'file_path' => null];
        }
    }

    // 7. Generate a secure, unique, unguessable filename
    try {
        $random_bytes = bin2hex(random_bytes(10));
    } catch (Exception $e) {
        $random_bytes = substr(md5(uniqid(mt_rand(), true)), 0, 20);
    }
    $safe_filename = 'doc_' . date('Ymd_His') . '_' . $random_bytes . '.' . $extension;
    $target_filepath = $clean_dest_dir . $safe_filename;

    // 8. Move uploaded file
    if (move_uploaded_file($tmp_file, $target_filepath)) {
        $public_path = rtrim($public_prefix, '/\\') . '/' . $safe_filename;
        return [
            'success'       => true,
            'file_path'     => $public_path,
            'filename'      => $safe_filename,
            'original_name' => $original_name,
            'error'         => null
        ];
    } else {
        return ['success' => false, 'error' => 'Server error: Failed to store the uploaded file.', 'file_path' => null];
    }
}
?>
