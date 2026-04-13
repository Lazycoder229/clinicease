<?php
/**
 * All routes here
 */

// ── Auth Routes ──
$router->get('/', 'app/views/auth/login');
$router->get('auth/login', 'app/views/auth/login');
$router->post('auth/login_process', 'app/views/auth/login_process');
$router->get('auth/registration', 'app/views/auth/registration');
$router->post('auth/registration_process', 'app/views/auth/registration_process');
$router->get('auth/logout', 'app/views/auth/logout');

// ── Patient Routes ──
$router->get('patient/dashboard', 'app/views/patient/dashboard');
$router->any('patient/appointments', 'app/views/patient/appointments');
$router->any('patient/prescriptions', 'app/views/patient/prescriptions');
$router->get('patient/records', 'app/views/patient/records');
$router->get('patient/labresult', 'app/views/patient/labresult');

// ── Doctor Routes ──
$router->any('doctor/prescriptions', 'app/views/doctor/prescriptions');

// ── Nurse Routes ──
$router->get('nurse/prescriptions', 'app/views/nurse/prescriptions');

$router->any('/logout', function() {
    session_start();
    session_destroy();
    header('Location: ' . url('auth/login'));
    exit;
});