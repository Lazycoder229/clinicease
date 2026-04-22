<?php
// Doctor - My Patients Page

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

/* ─── Get patients ─── */
$patients = $db->table('appointments a')
    ->select('p.patient_id, CONCAT(up.first_name, \' \', up.last_name) AS patient_name, u.email, up.phone, COUNT(a.appointment_id) AS total_appointments, MAX(a.appointment_date) AS last_appointment')
    ->join('patients p', 'a.patient_id = p.patient_id')
    ->join('user_profiles up', 'p.user_id = up.user_id')
    ->join('users u', 'p.user_id = u.user_id')
    ->where('a.doctor_id', $doctorId)
    ->group_by('p.patient_id')
    ->order_by('last_appointment DESC')
    ->get_all();

/* ─── Stats ─── */
$totalPatients = count($patients);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Patients - ClinicEase</title>
  <link rel="stylesheet" href="../../public/css/output.css"/>
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

    /* Patient Table */
    .patient-table{width:100%;border-collapse:collapse;}
    .patient-table th{background:var(--surface);padding:12px;text-align:left;font-weight:700;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--border);}
    .patient-table td{padding:14px 12px;border-bottom:1px solid var(--border);font-size:13px;}
    .patient-row:hover{background:var(--surface);}
    .patient-name{font-weight:700;color:var(--navy);}
    .patient-info{font-size:12px;color:var(--muted);}

    /* Empty */
    .empty-state{text-align:center;padding:60px 24px;color:var(--muted);}
    .empty-state i{font-size:48px;margin-bottom:10px;color:var(--border);display:block;}

    /* Section */
    .section{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;margin-bottom:24px;overflow:hidden;}
    .section-title{font-size:16px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;}

    /* Mobile */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45;}
    .overlay.open{display:block;}
    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}
      .main{margin-left:0;}.hamburger{display:flex;}
      .content{padding:20px 16px;}.topbar{padding:16px 20px;}
      .patient-table{font-size:12px;}.patient-table th, .patient-table td{padding:8px;}
    }
    @keyframes fadein{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    .fade-up{animation:fadein .4s ease both;}
  </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<main class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="icon-btn hamburger" id="hamburger" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="topbar-left">
        <h2>My Patients</h2>
        <p><?= date('l, F j, Y') ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn" title="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div class="icon-btn" title="Notifications">
        <i class="fa-solid fa-bell"></i>
        <span class="dot"></span>
      </div>
    </div>
  </div>

  <div class="content">
    <!-- Stats Card -->
    <div class="section fade-up">
      <div class="section-title">
        <div>
          <h3 style="font-size:18px;font-weight:700;color:var(--navy);">Total Patients</h3>
          <p style="margin-top:4px;font-size:12px;color:var(--muted);">Patients under your care</p>
        </div>
        <div style="font-size:32px;font-weight:700;color:var(--teal);"><?= $totalPatients ?></div>
      </div>
    </div>

    <!-- Patients List -->
    <div class="section fade-up">
      <div class="section-title">
        <h3 style="font-size:16px;font-weight:700;color:var(--navy);">Patient Directory</h3>
        <div style="display:flex;gap:8px;">
          <input type="text" placeholder="Search patients..." style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;width:200px;">
          <button style="padding:8px 16px;background:var(--teal);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;transition:background .2s;" onmouseover="this.style.background='#0d8078'" onmouseout="this.style.background='var(--teal)'">
            <i class="fa-solid fa-filter"></i> Filter
          </button>
        </div>
      </div>

      <?php if (count($patients) > 0): ?>
        <div style="overflow-x:auto;">
          <table class="patient-table">
            <thead>
              <tr>
                <th>Patient Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Appointments</th>
                <th>Last Visit</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($patients as $patient): ?>
                <tr class="patient-row fade-up">
                  <td>
                    <div class="patient-name">
                      <i class="fa-solid fa-circle" style="font-size:6px;color:var(--teal);margin-right:8px;"></i>
                      <?= htmlspecialchars($patient['patient_name']) ?>
                    </div>
                    <div class="patient-info">ID: #<?= htmlspecialchars($patient['patient_id']) ?></div>
                  </td>
                  <td>
                    <div class="patient-info"><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <div class="patient-info"><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <span style="background:var(--teal-light);color:var(--teal);padding:4px 8px;border-radius:6px;font-size:12px;font-weight:700;">
                      <?= $patient['total_appointments'] ?>
                    </span>
                  </td>
                  <td>
                    <div class="patient-info">
                      <?= $patient['last_appointment'] ? date('M d, Y', strtotime($patient['last_appointment'])) : 'N/A' ?>
                    </div>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px;">
                      <a href="<?= url('doctor/patient-detail?id=' . $patient['patient_id']) ?>" style="padding:6px 12px;background:var(--teal);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#0d8078'" onmouseout="this.style.background='var(--teal)'">
                        <i class="fa-solid fa-eye"></i> View
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-user-doctor"></i>
          <h4 style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:4px;">No Patients Yet</h4>
          <p style="font-size:13px;">Once you have appointments, patients will appear here</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  }

  function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  }
</script>

</body>
</html>
