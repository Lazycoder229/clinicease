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
$router->any('patient/profile', 'app/views/patient/profile');
$router->any('patient/messages', 'app/views/patient/messages');

// ── Doctor Routes ──
$router->get('doctor/dashboard', 'app/views/doctor/dashboard');
$router->any('doctor/prescriptions', 'app/views/doctor/prescriptions');
$router->get('doctor/patients', 'app/views/doctor/patients');
$router->any('doctor/appointments', 'app/views/doctor/appointments');
$router->get('doctor/records', 'app/views/doctor/records');
$router->any('doctor/messages', 'app/views/doctor/messages');
$router->get('doctor/profile', 'app/views/doctor/profile');
$router->get('doctor/settings', 'app/views/doctor/settings');

// ── Nurse Routes ──
$router->get('nurse/prescriptions', 'app/views/nurse/prescriptions');

// ── API Routes — Doctor Records ──
$router->post('api/doctor/records/create', 'app/api/doctor_records_create');
$router->post('api/doctor/records/update', 'app/api/doctor_records_update');
$router->post('api/doctor/records/delete', 'app/api/doctor_records_delete');

$router->any('/logout', function() {
    session_start();
    session_destroy();
    header('Location: ' . url('auth/login'));
    exit;
});