<?php
require_once __DIR__ . '/../../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('auth/login'));
    exit;
}

// ── Sanitize helper ───────────────────────────────────────────
function clean(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// ── Collect fields ────────────────────────────────────────────
$email = clean('email');
$password = clean('password');

// ── Basic validation ──────────────────────────────────────────
$errors = [];

if (empty($email)) {
    $errors[] = 'Email is required.';
}
if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

// ── If validation fails, go back with errors ──────────────────
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header('Location: ' . url('auth/login'));
    exit;
}

// ── Query & verify ────────────────────────────────────────────
try {
    $db = new Database();
    error_log("Database instance created successfully");
    
    $user = $db->table('users u')
        ->select('u.user_id, u.username, u.email, u.password_hash, u.role, p.first_name, p.last_name')
        ->inner_join('user_profiles p', 'u.user_id = p.user_id')
        ->where('u.email', $email)
        ->get();
    
    error_log("Query executed for email: $email, Result: " . ($user ? 'Found' : 'Not found'));

    if ($user) {
        error_log("User found - ID: " . $user['user_id']);
        error_log("Password hash stored: " . substr($user['password_hash'], 0, 20) . "...");
        error_log("Password provided: $password");
        error_log("Password length: " . strlen($password));
        error_log("Hash length: " . strlen($user['password_hash']));
        
        $passwordVerified = password_verify($password, $user['password_hash']);
        error_log("Password verification result: " . ($passwordVerified ? 'TRUE' : 'FALSE'));
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        error_log("Password verification successful for user: $email");
        // ── Regenerate session ID to prevent session fixation ──
        session_regenerate_id(true);

        $_SESSION['user_id']  = (int) $user['user_id'];  
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name']  = $user['last_name'] ?? '';
        $_SESSION['role']     = $user['role'];

        // ── Role-based redirect ────────────────────────────────
        $redirect = match($user['role']) {
            'admin'   => url('admin/dashboard'),
            'doctor'  => url('doctor/dashboard'),
            'nurse'   => url('nurse/dashboard'),
            'patient' => url('patient/dashboard'),
            default   => url('auth/login'),
        };

        error_log("Login successful for user: $email, Redirecting to: $redirect");
        header('Location: ' . $redirect);
        exit;

    } else {
        // ── Log failed attempt ────────────────────────────────
        if (!$user) {
            error_log("Login attempt: Email '$email' not found");
        } else {
            error_log("Login attempt: Password mismatch for email '$email'");
        }
        // ── Generic error (don't reveal which field is wrong) ──
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ' . url('auth/login'));
        exit;
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
    
    error_log('Login exception caught');
    error_log('Error message: ' . $errorMsg);
    error_log('Stack trace: ' . $errorTrace);
    
    // Store error for display
    $_SESSION['login_error'] = 'Error: ' . $errorMsg;
    
    header('Location: ' . url('auth/login'));
    exit;
}
?>