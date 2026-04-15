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
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <style>
    :root{--sidebar-w:260px;--teal:#0d9488;--teal-light:#ccfbf1;--navy:#0f172a;--muted:#64748b;--surface:#f8fafc;--card:#ffffff;--border:#e2e8f0;--accent:#f59e0b;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:var(--surface);color:var(--navy);display:flex;min-height:100vh;}

    /* Sidebar */
    .sidebar{width:var(--sidebar-w);background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;transition:transform .3s;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;padding:28px 24px 24px;border-bottom:1px solid rgba(255,255,255,.08);}
    .sidebar-logo .icon-box{width:36px;height:36px;border-radius:10px;background:var(--teal);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .sidebar-logo span{font-weight:700;font-size:16px;color:#fff;}
    .sidebar-section{padding:20px 16px 8px;font-size:10px;font-weight:700;letter-spacing:1.5px;color:#475569;text-transform:uppercase;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:10px 16px;margin:2px 8px;border-radius:10px;color:#94a3b8;font-size:14px;font-weight:500;text-decoration:none;transition:background .2s,color .2s;}
    .nav-link i{width:18px;text-align:center;font-size:14px;}
    .nav-link:hover{background:rgba(255,255,255,.07);color:#e2e8f0;}
    .nav-link.active{background:var(--teal);color:#fff;}
    .badge-count{margin-left:auto;background:var(--accent);color:#fff;font-size:10px;font-weight:700;border-radius:20px;padding:1px 7px;}
    .sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid rgba(255,255,255,.08);}
    .user-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;background:rgba(255,255,255,.05);margin-bottom:10px;}
    .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--teal),#0ea5e9);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;}
    .user-name{font-size:13px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .user-role{font-size:11px;color:#64748b;text-transform:capitalize;}
    .logout-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px;border-radius:10px;background:rgba(239,68,68,.12);color:#f87171;font-size:13px;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:background .2s;}
    .logout-btn:hover{background:rgba(239,68,68,.22);}

    /* Main */
    .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:20px 32px;background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:40;}
    .topbar-left h2{font-size:20px;font-weight:700;}
    .topbar-left p{font-size:13px;color:var(--muted);margin-top:2px;}
    .topbar-right{display:flex;align-items:center;gap:14px;}
    .icon-btn{width:38px;height:38px;border-radius:10px;border:1px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;color:var(--muted);cursor:pointer;font-size:15px;position:relative;transition:border-color .2s,color .2s;}
    .icon-btn:hover{border-color:var(--teal);color:var(--teal);}
    .icon-btn .dot{position:absolute;top:7px;right:7px;width:7px;height:7px;border-radius:50%;background:var(--accent);border:2px solid #fff;}
    .hamburger{display:none;}
    .content{padding:32px;flex:1;}

    /* Stats */
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:transform .2s,box-shadow .2s;}
    .stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.07);}
    .stat-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
    .stat-value{font-size:24px;font-weight:700;line-height:1;}
    .stat-label{font-size:12px;color:var(--muted);margin-top:3px;}

    /* Role badge */
    .role-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:700;margin-bottom:20px;}

    /* Section */
    .section{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;margin-bottom:20px;overflow:hidden;}
    .section-title{font-size:14px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px;}

    /* Appointment item */
    .appt-item{padding:12px 0;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:12px;font-size:13px;}
    .appt-item:last-child{border-bottom:none;}
    .appt-time{width:60px;font-weight:700;color:var(--teal);flex-shrink:0;}
    .appt-patient{font-weight:600;color:var(--navy);}
    .appt-reason{font-size:12px;color:var(--muted);margin-top:2px;}

    /* Quick action */
    .quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-top:14px;}
    .quick-link{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--surface);font-size:12px;font-weight:600;color:var(--navy);text-decoration:none;transition:border-color .2s,background .2s;}
    .quick-link:hover{border-color:var(--teal);background:var(--teal-light);color:var(--teal);}

    /* Empty */
    .empty-state{text-align:center;padding:32px 24px;color:var(--muted);}
    .empty-state i{font-size:32px;margin-bottom:10px;color:var(--border);display:block;}

    /* Mobile */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45;}
    .overlay.open{display:block;}
    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}
      .main{margin-left:0;}.hamburger{display:flex;}
      .content{padding:20px 16px;}.topbar{padding:16px 20px;}
    }
    @keyframes fadein{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    .fade-up{animation:fadein .4s ease both;}
    .d1{animation-delay:.05s}.d2{animation-delay:.10s}.d3{animation-delay:.15s}.d4{animation-delay:.20s}
  </style>
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
