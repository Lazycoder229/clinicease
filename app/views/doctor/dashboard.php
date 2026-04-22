<?php
// Doctor Dashboard
// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ' . url('auth/login'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$db = new Database();

/* ─── Resolve doctor_id ─── */
$doctorRow = $db->table('doctors')->where('user_id', $userId)->get();
if (!$doctorRow) die('Doctor record not found.');
$doctorId = (int) $doctorRow['doctor_id'];

/* ─── Get doctor name ─── */
$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($me['full_name'] ?? 'Doctor');

/* ─── Dashboard Stats ─── */
$totalPatients = $db->table('appointments a')
    ->select('COUNT(DISTINCT a.patient_id) AS count')
    ->where('a.doctor_id', $doctorId)
    ->get();
$totalPatients = $totalPatients['count'] ?? 0;

$todayAppointments = $db->table('appointments')
    ->where('doctor_id', $doctorId)
    ->where('appointment_date', '=', 'CURDATE()')
    ->count();

$activeRx = $db->table('prescriptions')
    ->where('doctor_id', $doctorId)
    ->where('status', 'Active')
    ->count();

$upcomingAppointments = $db->table('appointments a')
    ->select('a.appointment_id, a.appointment_date, a.appointment_time, CONCAT(p.first_name, \' \', p.last_name) AS patient_name, a.type AS reason')
    ->join('patients pt', 'a.patient_id = pt.patient_id')
    ->join('user_profiles p', 'pt.user_id = p.user_id')
    ->where('a.doctor_id', $doctorId)
    ->where('a.appointment_date', '>=', 'CURDATE()')
    ->order_by('a.appointment_date ASC')
    ->limit(5)
    ->get_all();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Doctor Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>"
</head>
<body>

<?php include 'aside.php'; ?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<main class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="icon-btn hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-left">
        <h2>Good morning, Dr. <?= htmlspecialchars(explode(' ', $full_name)[0]) ?> 👋</h2>
        <p><?= date('l, F j, Y') ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn"><i class="fa-regular fa-bell"></i></div>
      <div class="icon-btn"><i class="fa-regular fa-circle-question"></i></div>
    </div>
  </div>

  <div class="content">

    <!-- Role badge -->
    <div class="role-badge fade-up d1"><i class="fa-solid fa-user-doctor"></i>Doctor — Full dashboard access</div>

    <!-- Stats -->
    <div class="stats-grid fade-up d1">
      <div class="stat-card">
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;"><i class="fa-solid fa-users"></i></div>
        <div><div class="stat-value"><?= $totalPatients ?></div><div class="stat-label">Total Patients</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#0ea5e9;"><i class="fa-solid fa-calendar-check"></i></div>
        <div><div class="stat-value"><?= $todayAppointments ?></div><div class="stat-label">Today's Appointments</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-pills"></i></div>
        <div><div class="stat-value"><?= $activeRx ?></div><div class="stat-label">Active Prescriptions</div></div>
      </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="section fade-up d2">
      <div class="section-title"><i class="fa-solid fa-calendar-alt" style="color:var(--teal);"></i>Upcoming Appointments</div>
      <?php if (empty($upcomingAppointments)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-calendar"></i>
          <p>No appointments scheduled</p>
        </div>
      <?php else: ?>
        <?php foreach ($upcomingAppointments as $appt): ?>
          <div class="appt-item">
            <div class="appt-time"><?= substr($appt['appointment_time'], 0, 5) ?></div>
            <div>
              <div class="appt-patient"><?= htmlspecialchars($appt['patient_name']) ?></div>
              <div class="appt-reason"><?= htmlspecialchars($appt['reason'] ?? 'General Checkup') ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <div class="quick-actions">
        <a href="<?= url('doctor/appointments') ?>" class="quick-link"><i class="fa-solid fa-arrow-right"></i>View All</a>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section fade-up d3">
      <div class="section-title"><i class="fa-solid fa-bolt" style="color:var(--accent);"></i>Quick Actions</div>
      <div class="quick-actions">
        <a href="<?= url('doctor/appointments') ?>" class="quick-link"><i class="fa-solid fa-calendar-plus"></i>New Appointment</a>
        <a href="<?= url('doctor/prescriptions') ?>" class="quick-link"><i class="fa-solid fa-prescription-bottle"></i>New Prescription</a>
        <a href="<?= url('doctor/records') ?>" class="quick-link"><i class="fa-solid fa-file-medical"></i>View Records</a>
        <a href="<?= url('doctor/patients') ?>" class="quick-link"><i class="fa-solid fa-stethoscope"></i>My Patients</a>
        <a href="<?= url('doctor/messages') ?>" class="quick-link"><i class="fa-solid fa-envelope"></i>Messages</a>
        <a href="<?= url('doctor/profile') ?>" class="quick-link"><i class="fa-solid fa-user"></i>Profile</a>
      </div>
    </div>

  </div>
</main>

<script>
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('open');
  document.querySelector('.overlay').classList.toggle('open');
}
function closeSidebar(){
  document.querySelector('.sidebar').classList.remove('open');
  document.querySelector('.overlay').classList.remove('open');
}
</script>
</body>
</html>
