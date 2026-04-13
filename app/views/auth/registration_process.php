<?php
require_once __DIR__ . '/../../../config.php';

// ── Only accept POST requests ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('auth/registration'));
    exit;
}

// ── Sanitize helper ───────────────────────────────────────────
function clean(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// ── Collect common fields ─────────────────────────────────────
$role     = clean('role');
$username = clean('username');
$email    = clean('email');
$password = clean('password');
$passconf = clean('password_confirm');

// ── Basic validation ──────────────────────────────────────────
$errors = [];

if (!in_array($role, ['patient', 'doctor', 'admin', 'nurse'])) {
    $errors[] = 'Invalid role selected.';
}
if (empty($username)) {
    $errors[] = 'Username is required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $passconf) {
    $errors[] = 'Passwords do not match.';
}

// If validation fails, go back with errors
if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    header('Location: ' . url('auth/registration'));
    exit;
}

// ── Check for existing email/username ─────────────────────────
try {
    $db = new Database();
    
    $existing = $db->table('users')
        ->where('username', $username)
        ->or_where('email', $email)
        ->get();
    
    if ($existing) {
        $_SESSION['reg_errors'] = ['This email or username is already registered. Please use a different one.'];
        header('Location: ' . url('auth/registration'));
        exit;
    }
} catch (Exception $e) {
    error_log('Duplicate check error: ' . $e->getMessage());
}

// ── Begin registration ────────────────────────────────────────────
try {
    $db = new Database();

    // ── 1. INSERT into users ──────────────────────────────────
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    error_log("Creating account - email: $email, role: $role");
    error_log("Password hashed: " . substr($passwordHash, 0, 20) . "...");
    
    $db->table('users')->insert([
        'username'          => $username,
        'email'             => $email,
        'password_hash'     => $passwordHash,
        'role'              => $role,
        'security_question' => clean('security_q') ?: null,
        'security_answer'   => clean('security_a') ? password_hash(clean('security_a'), PASSWORD_BCRYPT) : null,
        'agreed_terms'      => isset($_POST['agree_terms']) ? 1 : 0,
    ]);
    
    error_log("User inserted successfully");

    $userId = (int) $db->last_id();

    // ── 2. INSERT into user_profiles ─────────────────────────
    $db->table('user_profiles')->insert([
        'user_id'     => $userId,
        'first_name'  => clean('first_name'),
        'last_name'   => clean('last_name'),
        'date_of_birth' => clean('dob'),
        'gender'      => clean('gender'),
        'phone'       => clean('phone'),
        'nationality' => clean('nationality') ?: null,
        'address'     => clean('address'),
    ]);

    // ── 3. INSERT role-specific table ────────────────────────
    switch ($role) {

        case 'patient':
            $db->table('patients')->insert([
                'user_id'            => $userId,
                'blood_type'         => clean('blood_type') ?: null,
                'civil_status'       => clean('civil_status') ?: null,
                'height_cm'          => clean('height_cm') ?: null,
                'weight_kg'          => clean('weight_kg') ?: null,
                'allergies'          => clean('allergies') ?: null,
                'medical_conditions' => clean('conditions') ?: null,
                'emergency_name'     => clean('emergency_name') ?: null,
                'emergency_relation' => clean('emergency_relation') ?: null,
                'emergency_phone'    => clean('emergency_phone') ?: null,
                'insurance_number'   => clean('insurance_no') ?: null,
                'insurance_provider' => clean('insurance_provider') ?: null,
            ]);
            break;

        case 'doctor':
            $db->table('doctors')->insert([
                'user_id'          => $userId,
                'prc_license_no'   => clean('prc_license'),
                'license_expiry'   => clean('license_expiry'),
                'specialization'   => clean('specialization'),
                'fellowship'       => clean('fellowship') ?: null,
                'medical_school'   => clean('med_school'),
                'years_experience' => clean('years_exp') ?: null,
                'affiliation'      => clean('affiliation') ?: null,
                'bio'              => clean('doctor_bio') ?: null,
                'consult_days'     => clean('consult_days') ?: null,
                'consult_hours'    => clean('consult_hours') ?: null,
            ]);
            break;

        case 'admin':
            $db->table('admins')->insert([
                'user_id'        => $userId,
                'employee_id'    => clean('employee_id'),
                'department'     => clean('department'),
                'job_title'      => clean('job_title'),
                'access_level'   => (int) clean('access_level') ?: 1,
                'hire_date'      => clean('hire_date') ?: null,
                'supervisor'     => clean('supervisor') ?: null,
                'auth_code_hash' => clean('admin_code') ? password_hash(clean('admin_code'), PASSWORD_BCRYPT) : null,
            ]);
            break;

        case 'nurse':
            $db->table('nurses')->insert([
                'user_id'           => $userId,
                'prc_license_no'    => clean('nurse_license'),
                'license_expiry'    => clean('nurse_license_expiry'),
                'years_experience'  => clean('nurse_exp') ?: null,
                'shift_preference'  => clean('shift') ?: null,
                'ward_department'   => clean('ward') ?: null,
                'employee_id'       => clean('nurse_emp_id') ?: null,
                'supervisor'        => clean('nurse_supervisor') ?: null,
                'certifications'    => clean('nurse_certs') ?: null,
            ]);
            break;
    }

    // ── Success: store message in session and redirect ────────
    $_SESSION['reg_success'] = true;
    $_SESSION['reg_role']    = $role;
    header('Location: ' . url('auth/registration?success=1'));
    exit;

} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());

    // Check for duplicate username/email errors
    if (strpos($e->getMessage(), 'UNIQUE') || strpos($e->getMessage(), 'Duplicate')) {
        $_SESSION['reg_errors'] = ['That username or email is already registered.'];
    } else {
        $_SESSION['reg_errors'] = ['Registration failed. Please try again.'];
    }

    header('Location: ' . url('auth/registration'));
    exit;
}