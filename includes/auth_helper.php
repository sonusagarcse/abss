<?php
// includes/auth_helper.php - Modular Authentication Helper Library

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Authenticate Administrator Login
 */
function authenticate_admin($username, $password) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(false);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            if (function_exists('log_activity')) {
                log_activity('login', "Admin successfully logged in: " . $user['username']);
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed admin login: incorrect password for " . $username);
            }
            return ['success' => false, 'error' => 'Invalid password. Please try again.'];
        }
    }
    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed admin login: username not found " . $username);
    }
    return ['success' => false, 'error' => 'No admin account found with this username.'];
}

/**
 * Authenticate Parent Login
 */
function authenticate_parent($username, $password) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, parent_name, password, email FROM parents WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($parent = $result->fetch_assoc()) {
        if (password_verify($password, $parent['password'])) {
            session_regenerate_id(false);
            $_SESSION['parent_id'] = $parent['id'];
            $_SESSION['parent_name'] = $parent['parent_name'];
            $_SESSION['parent_email'] = $parent['email'];

            // Persistent 1-year Remember Cookie for App & Web auto-login
            $secret_key = defined('DB_PASS') ? DB_PASS . '_ABSS_AUTH_SECRET' : 'ABSS_AUTH_SECRET';
            $token_hash = hash_hmac('sha256', $parent['id'] . '|' . $parent['email'], $secret_key);
            $cookie_val = $parent['id'] . ':' . $token_hash;
            setcookie('abss_parent_remember', $cookie_val, [
                'expires' => time() + 31536000, // 1 year
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            if (function_exists('log_activity')) {
                log_activity('login', "Parent successfully logged in: " . $parent['email']);
            }
            return ['success' => true, 'redirect' => 'dashboard.php'];
        } else {
            if (function_exists('log_activity')) {
                log_activity('login_failed', "Failed parent login: incorrect password for " . $username);
            }
            return ['success' => false, 'error' => 'Invalid password. Please try again.'];
        }
    }
    if (function_exists('log_activity')) {
        log_activity('login_failed', "Failed parent login: not found " . $username);
    }
    return ['success' => false, 'error' => 'No parent account found with this email or mobile number.'];
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
