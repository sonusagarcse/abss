<?php
// includes/auth_helper.php - Modular Authentication Helper Library

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Generate Secure Admin Remember Token Hash
 */
function generate_admin_remember_hash($admin_id, $username) {
    return hash_hmac('sha256', (int)$admin_id . '|' . strtolower(trim($username)), get_parent_auth_secret());
}

/**
 * Set Persistent 1-Year Admin Remember Cookie
 */
function set_admin_remember_cookie($admin_id, $username) {
    $token_hash = generate_admin_remember_hash($admin_id, $username);
    $cookie_val = (int)$admin_id . ':' . $token_hash;
    $oneYear = 31536000;
    
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie('abss_admin_remember', $cookie_val, [
        'expires' => time() + $oneYear,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Clear Persistent Admin Remember Cookie
 */
function clear_admin_remember_cookie() {
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie('abss_admin_remember', '', [
        'expires' => time() - 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (isset($_COOKIE['abss_admin_remember'])) {
        unset($_COOKIE['abss_admin_remember']);
    }
}

/**
 * Verify and Auto-Restore Admin Session from Persistent Cookie
 */
function verify_and_restore_admin_session() {
    if (isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] > 0) {
        return true;
    }

    if (isset($_COOKIE['abss_admin_remember'])) {
        $cookie_data = explode(':', $_COOKIE['abss_admin_remember'], 2);
        if (count($cookie_data) === 2) {
            $admin_id = (int)$cookie_data[0];
            $provided_hash = $cookie_data[1];

            if ($admin_id > 0) {
                $conn = getDB();
                $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($user = $res->fetch_assoc()) {
                    $expected_hash = generate_admin_remember_hash($user['id'], $user['username']);
                    if (hash_equals($expected_hash, $provided_hash)) {
                        $_SESSION['admin_id'] = (int)$user['id'];
                        $_SESSION['username'] = $user['username'];
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

/**
 * Authenticate Administrator Login
 */
function authenticate_admin($username, $password, $remember = true) {
    $conn = getDB();
    $clean_username = trim($username);
    
    // Case-insensitive lookup to handle mobile keyboard auto-capitalization (e.g., "Admin" vs "admin")
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $clean_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(false);
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            
            if ($remember) {
                set_admin_remember_cookie($user['id'], $user['username']);
            }
            
            if (function_exists('log_activity')) {
                log_activity('login', "Admin successfully logged in: " . $user['username']);
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed admin login: incorrect password for " . $clean_username);
            }
            return ['success' => false, 'error' => 'Invalid password. Please verify and try again.'];
        }
    }
    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed admin login: username not found " . $clean_username);
    }
    return ['success' => false, 'error' => 'No admin account found with username "' . htmlspecialchars($clean_username) . '".'];
}

/**
 * Get Parent Secret Key for Auth Signature
 */
function get_parent_auth_secret() {
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    return $dbPass . '_ABSS_AUTH_PERSISTENT_KEY_v2';
}

/**
 * Generate Secure Parent Remember Token Hash
 */
function generate_parent_remember_hash($parent_id, $parent_email) {
    return hash_hmac('sha256', (int)$parent_id . '|' . strtolower(trim($parent_email)), get_parent_auth_secret());
}

/**
 * Set Persistent 1-Year Parent Remember Cookie
 */
function set_parent_remember_cookie($parent_id, $parent_email) {
    $token_hash = generate_parent_remember_hash($parent_id, $parent_email);
    $cookie_val = (int)$parent_id . ':' . $token_hash;
    $oneYear = 31536000;
    
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Host-only cookie without hardcoded domain ensures compatibility across all browsers & environments
    setcookie('abss_parent_remember', $cookie_val, [
        'expires' => time() + $oneYear,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Clear Persistent Parent Remember Cookie
 */
function clear_parent_remember_cookie() {
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie('abss_parent_remember', '', [
        'expires' => time() - 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (isset($_COOKIE['abss_parent_remember'])) {
        unset($_COOKIE['abss_parent_remember']);
    }
}

/**
 * Verify and Auto-Restore Parent Session from Persistent Cookie
 */
function verify_and_restore_parent_session() {
    if (isset($_SESSION['parent_id']) && (int)$_SESSION['parent_id'] > 0) {
        return true;
    }

    if (isset($_COOKIE['abss_parent_remember'])) {
        $cookie_data = explode(':', $_COOKIE['abss_parent_remember'], 2);
        if (count($cookie_data) === 2) {
            $pid = (int)$cookie_data[0];
            $provided_hash = $cookie_data[1];

            if ($pid > 0) {
                $conn = getDB();
                $stmt = $conn->prepare("SELECT id, parent_name, email FROM parents WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $pid);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($parent = $res->fetch_assoc()) {
                    $expected_hash = generate_parent_remember_hash($parent['id'], $parent['email']);
                    
                    // Also support legacy hash format for backward compatibility
                    $legacy_secret = defined('DB_PASS') ? DB_PASS . '_ABSS_AUTH_SECRET' : 'ABSS_AUTH_SECRET';
                    $legacy_hash = hash_hmac('sha256', $parent['id'] . '|' . $parent['email'], $legacy_secret);

                    if (hash_equals($expected_hash, $provided_hash) || hash_equals($legacy_hash, $provided_hash)) {
                        // Check if parent has at least one active student
                        $active_check = $conn->query("SELECT COUNT(*) AS active_count FROM students WHERE parent_id = " . (int)$parent['id'] . " AND status = 'active'");
                        $active_count = ($active_check && $active_check->num_rows > 0) ? (int)$active_check->fetch_assoc()['active_count'] : 0;
                        if ($active_count === 0) {
                            clear_parent_remember_cookie();
                            return false;
                        }

                        // Restore parent session
                        $_SESSION['parent_id'] = (int)$parent['id'];
                        $_SESSION['parent_name'] = $parent['parent_name'];
                        $_SESSION['parent_email'] = $parent['email'];

                        // Refresh persistent cookie expiration for another year
                        set_parent_remember_cookie($parent['id'], $parent['email']);
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

/**
 * Authenticate Parent Login (Mobile Number or Email as ID, Mobile Number as Password)
 */
function authenticate_parent($username, $password) {
    $conn = getDB();
    $clean_user = trim($username);
    $digits_only = preg_replace('/[^0-9]/', '', $clean_user);

    $stmt = $conn->prepare("
        SELECT id, parent_name, password, email, phone 
        FROM parents 
        WHERE email = ? 
           OR phone = ? 
           OR (phone != '' AND REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+91', '') = ?)
        LIMIT 1
    ");
    $stmt->bind_param("sss", $clean_user, $clean_user, $digits_only);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($parent = $result->fetch_assoc()) {
        $clean_phone = preg_replace('/[^0-9]/', '', $parent['phone'] ?? '');
        $input_clean_pass = preg_replace('/[^0-9]/', '', $password);
        
        $is_valid = false;
        if (password_verify($password, $parent['password'])) {
            $is_valid = true;
        } elseif (!empty($parent['phone']) && ($password === $parent['phone'] || ($clean_phone && $input_clean_pass === $clean_phone))) {
            // Rehash plain phone to standard bcrypt hash
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $u_stmt = $conn->prepare("UPDATE parents SET password = ? WHERE id = ?");
            $u_stmt->bind_param("si", $new_hash, $parent['id']);
            $u_stmt->execute();
            $is_valid = true;
        } elseif ($password === $parent['password']) {
            // Direct plain match migration
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $u_stmt = $conn->prepare("UPDATE parents SET password = ? WHERE id = ?");
            $u_stmt->bind_param("si", $new_hash, $parent['id']);
            $u_stmt->execute();
            $is_valid = true;
        }

        if ($is_valid) {
            // Verify that this parent has at least one active student
            $active_check = $conn->query("SELECT COUNT(*) AS active_count FROM students WHERE parent_id = " . (int)$parent['id'] . " AND status = 'active'");
            $active_count = ($active_check && $active_check->num_rows > 0) ? (int)$active_check->fetch_assoc()['active_count'] : 0;
            if ($active_count === 0) {
                if (function_exists('log_activity')) {
                    log_activity('login_failed', "Failed parent login: inactive student account for " . $username);
                }
                return ['success' => false, 'error' => 'Your student account is currently inactive. Please contact the school administration.'];
            }

            session_regenerate_id(false);
            $_SESSION['parent_id'] = (int)$parent['id'];
            $_SESSION['parent_name'] = $parent['parent_name'];
            $_SESSION['parent_email'] = $parent['email'];

            // Set Persistent 1-year Remember Cookie for App & Web
            set_parent_remember_cookie($parent['id'], $parent['email']);

            if (function_exists('log_activity')) {
                log_activity('login', "Parent successfully logged in: " . ($parent['phone'] ?: $parent['email']));
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed parent login: incorrect password for " . $username);
            }
            return ['success' => false, 'error' => 'Invalid password. Your default password is your 10-digit mobile number.'];
        }
    }
    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed parent login: not found " . $username);
    }
    return ['success' => false, 'error' => 'No parent account found with this mobile number or email.'];
}

/**
 * Authenticate Teacher Login
 */
function authenticate_teacher($username, $password) {
    $conn = getDB();

    // Auto-migrate password column in teachers table if not present
    $checkPass = $conn->query("SHOW COLUMNS FROM teachers LIKE 'password'");
    if ($checkPass && $checkPass->num_rows == 0) {
        $conn->query("ALTER TABLE teachers ADD COLUMN password VARCHAR(255) NULL AFTER phone");
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, status FROM teachers WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($teacher = $result->fetch_assoc()) {
        if ($teacher['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your teacher account is inactive. Please contact administration.'];
        }
        
        // If password is not set yet, allow initial login setup or compare
        $isValid = false;
        if (empty($teacher['password'])) {
            // Default initial password is phone number or email prefix if unset
            $defaultPass = !empty($teacher['phone']) ? $teacher['phone'] : explode('@', $teacher['email'])[0];
            if ($password === $defaultPass || password_verify($password, password_hash($defaultPass, PASSWORD_DEFAULT))) {
                $isValid = true;
                // Set password
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $uStmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                $uStmt->bind_param("si", $newHash, $teacher['id']);
                $uStmt->execute();
            }
        } else {
            if (password_verify($password, $teacher['password'])) {
                $isValid = true;
            }
        }

        if ($isValid) {
            session_regenerate_id(false);
            $_SESSION['teacher_id'] = $teacher['id'];
            $_SESSION['teacher_name'] = $teacher['name'];
            $_SESSION['teacher_email'] = $teacher['email'];
            if (function_exists('log_activity')) {
                log_activity('login', "Teacher successfully logged in: " . $teacher['email']);
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed teacher login: incorrect password for " . $username);
            }
            return ['success' => false, 'error' => 'Invalid password. Please try again.'];
        }
    }
    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed teacher login: not found " . $username);
    }
    return ['success' => false, 'error' => 'No teacher account found with this email or mobile number.'];
}


/**
 * Master Catalog of Modules that Admin can permit/grant to Counselors
 */
function get_counselor_modules_catalog() {
    return [
        'inquiries' => [
            'label' => 'Admission Inquiries & Leads',
            'icon' => 'fas fa-envelope-open-text',
            'color' => '#2563eb',
            'category' => 'Admissions & Leads',
            'desc' => 'Manage admission inquiries, prospect queries, and follow-up status'
        ],
        'admissions' => [
            'label' => 'Online Admissions',
            'icon' => 'fas fa-user-plus',
            'color' => '#059669',
            'category' => 'Admissions & Leads',
            'desc' => 'Review online application forms and enroll candidates into the database'
        ],
        'students' => [
            'label' => 'Student Directory',
            'icon' => 'fas fa-user-graduate',
            'color' => '#7c3aed',
            'category' => 'Students & Study',
            'desc' => 'Look up enrolled student profiles, batches, and contact details'
        ],
        'visitors' => [
            'label' => 'Campus Visitors Log',
            'icon' => 'fas fa-chart-line',
            'color' => '#ea580c',
            'category' => 'Admissions & Leads',
            'desc' => 'Track front-desk visits, walk-in consultations, and counselor notes'
        ],
        'tickets' => [
            'label' => 'Support & Helpdesk Tickets',
            'icon' => 'fas fa-ticket-alt',
            'color' => '#0284c7',
            'category' => 'Communication',
            'desc' => 'Handle parent and student grievances and queries'
        ],
        'notices' => [
            'label' => 'Notice Board',
            'icon' => 'fas fa-bullhorn',
            'color' => '#d97706',
            'category' => 'Communication',
            'desc' => 'View institutional announcements, circulars, and notifications'
        ],
        'fees' => [
            'label' => 'Fee Ledger & Dues Lookup',
            'icon' => 'fas fa-file-invoice-dollar',
            'color' => '#16a34a',
            'category' => 'Finance',
            'desc' => 'Check fee balances, dues, and payment receipts for counseling guidance'
        ],
        'attendance' => [
            'label' => 'Student Attendance',
            'icon' => 'fas fa-calendar-check',
            'color' => '#0d9488',
            'category' => 'Students & Study',
            'desc' => 'View student daily attendance records and absence patterns'
        ],
        'results' => [
            'label' => 'Exam Results & Marks',
            'icon' => 'fas fa-award',
            'color' => '#9333ea',
            'category' => 'Students & Study',
            'desc' => 'View examination results and student academic performance'
        ],
        'syllabus' => [
            'label' => 'Academic Syllabus',
            'icon' => 'fas fa-book-open',
            'color' => '#2563eb',
            'category' => 'Academic Programs',
            'desc' => 'Browse curriculum, subject outlines, and test syllabi'
        ],
        'schools' => [
            'label' => 'Coaching Programs',
            'icon' => 'fas fa-graduation-cap',
            'color' => '#4338ca',
            'category' => 'Academic Programs',
            'desc' => 'Reference available coaching courses, fee structures, and target schools'
        ],
        'achievers' => [
            'label' => 'Hall of Excellence',
            'icon' => 'fas fa-trophy',
            'color' => '#eab308',
            'category' => 'Academic Programs',
            'desc' => 'Showcase top achievers and rankers to prospective parents'
        ],
        'gallery' => [
            'label' => 'Campus Media Gallery',
            'icon' => 'fas fa-images',
            'color' => '#0891b2',
            'category' => 'Media',
            'desc' => 'Campus infrastructure and event photography repository'
        ],
        'whatsapp' => [
            'label' => 'WhatsApp Quick Hub',
            'icon' => 'fab fa-whatsapp',
            'color' => '#22c55e',
            'category' => 'Communication',
            'desc' => 'Quick WhatsApp messaging bridge for parents and leads'
        ]
    ];
}

/**
 * Generate Secure Counselor Remember Token Hash
 */
function generate_counselor_remember_hash($counselor_id, $email) {
    return hash_hmac('sha256', (int)$counselor_id . '|' . strtolower(trim($email)), get_parent_auth_secret());
}

/**
 * Set Persistent 1-Year Counselor Remember Cookie
 */
function set_counselor_remember_cookie($counselor_id, $email) {
    $token_hash = generate_counselor_remember_hash($counselor_id, $email);
    $cookie_val = (int)$counselor_id . ':' . $token_hash;
    $oneYear = 31536000;
    
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie('abss_counselor_remember', $cookie_val, [
        'expires' => time() + $oneYear,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Clear Persistent Counselor Remember Cookie
 */
function clear_counselor_remember_cookie() {
    $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    setcookie('abss_counselor_remember', '', [
        'expires' => time() - 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    if (isset($_COOKIE['abss_counselor_remember'])) {
        unset($_COOKIE['abss_counselor_remember']);
    }
}

/**
 * Verify and Auto-Restore Counselor Session from Persistent Cookie
 */
function verify_and_restore_counselor_session() {
    if (isset($_SESSION['counselor_id']) && (int)$_SESSION['counselor_id'] > 0) {
        return true;
    }

    if (isset($_COOKIE['abss_counselor_remember'])) {
        $cookie_data = explode(':', $_COOKIE['abss_counselor_remember'], 2);
        if (count($cookie_data) === 2) {
            $counselor_id = (int)$cookie_data[0];
            $provided_hash = $cookie_data[1];

            if ($counselor_id > 0) {
                $conn = getDB();
                $stmt = $conn->prepare("SELECT id, name, email, designation, permissions, status FROM counselors WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $counselor_id);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($counselor = $res->fetch_assoc()) {
                    if ($counselor['status'] === 'active') {
                        $expected_hash = generate_counselor_remember_hash($counselor['id'], $counselor['email']);
                        if (hash_equals($expected_hash, $provided_hash)) {
                            $_SESSION['counselor_id'] = (int)$counselor['id'];
                            $_SESSION['counselor_name'] = $counselor['name'];
                            $_SESSION['counselor_email'] = $counselor['email'];
                            $_SESSION['counselor_designation'] = $counselor['designation'];
                            
                            $perms = !empty($counselor['permissions']) ? json_decode($counselor['permissions'], true) : [];
                            if (!is_array($perms)) $perms = [];
                            $_SESSION['counselor_permissions'] = $perms;
                            return true;
                        }
                    }
                }
            }
        }
    }
    return false;
}

/**
 * Authenticate Counselor Login
 */
function authenticate_counselor($username, $password, $remember = true) {
    $conn = getDB();
    $clean_user = trim($username);

    $stmt = $conn->prepare("SELECT id, name, email, phone, password, designation, permissions, status FROM counselors WHERE (LOWER(email) = LOWER(?) OR phone = ?) LIMIT 1");
    $stmt->bind_param("ss", $clean_user, $clean_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($counselor = $result->fetch_assoc()) {
        if ($counselor['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your counselor account is inactive. Please contact the administration office.'];
        }

        if (password_verify($password, $counselor['password'])) {
            session_regenerate_id(false);
            $_SESSION['counselor_id'] = (int)$counselor['id'];
            $_SESSION['counselor_name'] = $counselor['name'];
            $_SESSION['counselor_email'] = $counselor['email'];
            $_SESSION['counselor_designation'] = $counselor['designation'] ?: 'Admission Counselor';
            
            $perms = !empty($counselor['permissions']) ? json_decode($counselor['permissions'], true) : [];
            if (!is_array($perms)) $perms = [];
            $_SESSION['counselor_permissions'] = $perms;

            if ($remember) {
                set_counselor_remember_cookie($counselor['id'], $counselor['email']);
            }

            if (function_exists('log_activity')) {
                log_activity('login', "Counselor successfully logged in: " . $counselor['email']);
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed counselor login: incorrect password for " . $clean_user);
            }
            return ['success' => false, 'error' => 'Invalid password. Please verify and try again.'];
        }
    }

    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed counselor login: account not found for " . $clean_user);
    }
    return ['success' => false, 'error' => 'No counselor account found with that email or mobile number.'];
}

/**
 * Get Counselor Permissions array for a given counselor ID
 */
function get_counselor_permissions($counselor_id) {
    $cid = (int)$counselor_id;
    if ($cid <= 0) return [];
    
    $conn = getDB();
    $stmt = $conn->prepare("SELECT permissions FROM counselors WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && !empty($row['permissions'])) {
        $arr = json_decode($row['permissions'], true);
        return is_array($arr) ? $arr : [];
    }
    return [];
}

/**
 * Check if the current (or specified) counselor has permission for a specific module
 */
function counselor_has_permission($module, $counselor_id = null) {
    if ($counselor_id === null) {
        if (!isset($_SESSION['counselor_id'])) return false;
        $counselor_id = (int)$_SESSION['counselor_id'];
        
        // Fast lookup from session if set
        if (isset($_SESSION['counselor_permissions']) && is_array($_SESSION['counselor_permissions'])) {
            return in_array($module, $_SESSION['counselor_permissions'], true);
        }
    }
    
    $perms = get_counselor_permissions($counselor_id);
    return in_array($module, $perms, true);
}