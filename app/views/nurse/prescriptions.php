<?php
// Session already started in helpers.php

$userId = 4;

/* ─── Database connection ─── */
$db = new Database();

/* ─── Resolve nurse_id ─── */
$nurseRow = $db->table('nurses')->where('user_id', $userId)->get();
if (!$nurseRow) die('Nurse record not found.');
$nurseId = (int) $nurseRow['nurse_id'];
$ward    = $nurseRow['ward_department'] ?? 'General Ward';

/* ─── Nurse display name ─── */
$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($me['full_name'] ?? '');

/* ═══════════════════════════════════════════════════════
   POST HANDLER — PRG pattern
════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    /* ── Log administration ── */
    if ($action === 'log_admin' && isset($_POST['prescription_id'])) {
        $rxId = (int)$_POST['prescription_id'];
        $note = trim($_POST['admin_note'] ?? 'Medication administered by nurse.');

        try {
            $db->table('prescriptions')
                ->where('prescription_id', $rxId)
                ->where('status', 'Active')
                ->update(['instructions' => 'CONCAT(COALESCE(instructions, \'\'), \'\n[ADMINISTERED: \', NOW(), \' by Nurse ' . addslashes($full_name) . ' — \', ?, \']\')']);
            
            $_SESSION['rx_success'] = 'Administration logged successfully.';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['rx_error'] = 'Failed to log administration.';
        }
        header('Location: ' . url('nurse/prescriptions'));
        exit;
    }

    /* ── Flag concern ── */
    if ($action === 'flag' && isset($_POST['prescription_id'])) {
        $rxId    = (int)$_POST['prescription_id'];
        $concern = trim($_POST['concern'] ?? '');
        if ($concern) {
            try {
                $db->table('prescriptions')
                    ->where('prescription_id', $rxId)
                    ->update(['instructions' => 'CONCAT(COALESCE(instructions, \'\'), \'\n[⚠ NURSE FLAG: \', NOW(), \' — \', ?, \']\')']);
                
                $_SESSION['rx_success'] = 'Concern flagged for the attending doctor.';
            } catch (Exception $e) {
                $_SESSION['rx_error'] = 'Failed to flag concern.';
            }
        }
        header('Location: ' . url('nurse/prescriptions'));
        exit;
    }

    header('Location: ' . url('nurse/prescriptions'));
    exit;
}

/* ═══════════════════════════════════════════════════════
   GET — Flash + data
═══════════════════════════════════════════════════════ */
$success = $_SESSION['rx_success'] ?? '';
$error   = $_SESSION['rx_error'] ?? '';
unset($_SESSION['rx_success'], $_SESSION['rx_error']);

/* ─── Fetch all ACTIVE prescriptions (nurse sees all patients) ─── */
$activePrescriptions = $db->table('prescriptions rx')
    ->select('rx.prescription_id, rx.medication_name, rx.generic_name, rx.dosage, rx.form, rx.frequency, rx.duration_days, rx.route, rx.instructions, rx.indication, rx.prescribed_date, rx.expiry_date, rx.refills_allowed, rx.refills_used, rx.status, CONCAT(pp.first_name, \' \', pp.last_name) AS patient_name, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('patients pt', 'rx.patient_id = pt.patient_id', 'INNER ')
    ->join('users pu', 'pt.user_id = pu.user_id', 'INNER ')
    ->join('user_profiles pp', 'pt.user_id = pp.user_id', 'INNER ')
    ->join('doctors d', 'rx.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('rx.status', 'Active')
    ->order_by('pp.last_name, rx.medication_name')
    ->get_all();

/* ─── All statuses for history tab ─── */
$historyPrescriptions = $db->table('prescriptions rx')
    ->select('rx.prescription_id, rx.medication_name, rx.dosage, rx.form, rx.frequency, rx.route, rx.instructions, rx.prescribed_date, rx.expiry_date, rx.status, CONCAT(pp.first_name, \' \', pp.last_name) AS patient_name, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name')
    ->join('patients pt', 'rx.patient_id = pt.patient_id', 'INNER ')
    ->join('user_profiles pp', 'pt.user_id = pp.user_id', 'INNER ')
    ->join('doctors d', 'rx.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('rx.status', '!=', 'Active')
    ->order_by('rx.prescribed_date', 'DESC')
    ->limit(60)
    ->get_all();

$formMap = [
    'Tablet'   =>['icon'=>'fa-prescription-bottle-medical','color'=>'#0d9488','bg'=>'#ccfbf1'],
    'Capsule'  =>['icon'=>'fa-capsules','color'=>'#a855f7','bg'=>'#f3e8ff'],
    'Syrup'    =>['icon'=>'fa-bottle-droplet','color'=>'#0ea5e9','bg'=>'#e0f2fe'],
    'Drops'    =>['icon'=>'fa-eye-dropper','color'=>'#3b82f6','bg'=>'#dbeafe'],
    'Injection'=>['icon'=>'fa-syringe','color'=>'#ef4444','bg'=>'#fee2e2'],
    'Inhaler'  =>['icon'=>'fa-lungs','color'=>'#10b981','bg'=>'#d1fae5'],
    'Patch'    =>['icon'=>'fa-bandage','color'=>'#f59e0b','bg'=>'#fef3c7'],
    'Cream'    =>['icon'=>'fa-hand-dots','color'=>'#d97706','bg'=>'#fef9c3'],
    'Ointment' =>['icon'=>'fa-hand-dots','color'=>'#d97706','bg'=>'#fef9c3'],
    'Other'    =>['icon'=>'fa-pills','color'=>'#64748b','bg'=>'#f1f5f9'],
];
$routeColors = [
    'Oral'=>'#0d9488','Topical'=>'#d97706','Intravenous'=>'#ef4444',
    'Intramuscular'=>'#a855f7','Subcutaneous'=>'#3b82f6',
    'Inhalation'=>'#10b981','Sublingual'=>'#0ea5e9','Other'=>'#64748b',
];
$statusMap = [
    'Active'      =>['color'=>'#0d9488','bg'=>'#ccfbf1'],
    'Completed'   =>['color'=>'#64748b','bg'=>'#f1f5f9'],
    'Discontinued'=>['color'=>'#ef4444','bg'=>'#fee2e2'],
    'Expired'     =>['color'=>'#d97706','bg'=>'#fef3c7'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Nurse Prescriptions</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <style>
    :root{--sidebar-w:260px;--teal:#0d9488;--teal-light:#ccfbf1;--navy:#0f172a;--muted:#64748b;--surface:#f8fafc;--card:#ffffff;--border:#e2e8f0;--accent:#f59e0b;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:var(--surface);color:var(--navy);display:flex;min-height:100vh;}

    .sidebar{width:var(--sidebar-w);background:var(--navy);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;transition:transform .3s;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;padding:28px 24px 24px;border-bottom:1px solid rgba(255,255,255,.08);}
    .sidebar-logo .icon-box{width:36px;height:36px;border-radius:10px;background:var(--teal);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .sidebar-logo span{font-weight:700;font-size:16px;color:#fff;}
    .sidebar-section{padding:20px 16px 8px;font-size:10px;font-weight:700;letter-spacing:1.5px;color:#475569;text-transform:uppercase;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:10px 16px;margin:2px 8px;border-radius:10px;color:#94a3b8;font-size:14px;font-weight:500;text-decoration:none;transition:background .2s,color .2s;}
    .nav-link i{width:18px;text-align:center;font-size:14px;}
    .nav-link:hover{background:rgba(255,255,255,.07);color:#e2e8f0;}
    .nav-link.active{background:var(--teal);color:#fff;}
    .sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid rgba(255,255,255,.08);}
    .user-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;background:rgba(255,255,255,.05);margin-bottom:10px;}
    .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#a855f7,#6366f1);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0;}
    .user-name{font-size:13px;font-weight:600;color:#e2e8f0;}
    .user-role{font-size:11px;color:#64748b;text-transform:capitalize;}
    .logout-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px;border-radius:10px;background:rgba(239,68,68,.12);color:#f87171;font-size:13px;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:background .2s;}
    .logout-btn:hover{background:rgba(239,68,68,.22);}

    .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:20px 32px;background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:40;}
    .topbar-left h2{font-size:20px;font-weight:700;}
    .topbar-left p{font-size:13px;color:var(--muted);margin-top:2px;}
    .topbar-right{display:flex;align-items:center;gap:14px;}
    .icon-btn{width:38px;height:38px;border-radius:10px;border:1px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;color:var(--muted);cursor:pointer;font-size:15px;position:relative;transition:border-color .2s,color .2s;}
    .icon-btn:hover{border-color:var(--teal);color:var(--teal);}
    .hamburger{display:none;}
    .content{padding:32px;flex:1;}

    /* Role banner */
    .role-banner{display:flex;align-items:center;gap:14px;padding:16px 20px;background:linear-gradient(135deg,#f3e8ff,#e0e7ff);border:1px solid #c4b5fd;border-radius:14px;margin-bottom:24px;}
    .role-banner .rb-icon{width:44px;height:44px;border-radius:12px;background:#7c3aed;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;flex-shrink:0;}
    .role-banner h3{font-size:14px;font-weight:700;color:#4c1d95;}
    .role-banner p{font-size:12px;color:#6d28d9;margin-top:2px;}
    .permission-pills{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;}
    .perm-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
    .perm-yes{background:#d1fae5;color:#065f46;}
    .perm-no{background:#fee2e2;color:#991b1b;}

    /* Tabs */
    .main-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px;}
    .main-tab{padding:12px 20px;font-size:14px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .2s,border-color .2s;}
    .main-tab.active{color:var(--teal);border-bottom-color:var(--teal);}

    /* Search */
    .search-bar{display:flex;align-items:center;gap:10px;margin-bottom:20px;}
    .search-input-wrap{position:relative;flex:1;max-width:360px;}
    .search-input-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;}
    .search-input{width:100%;padding:9px 14px 9px 36px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;background:var(--card);}
    .search-input:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.12);}
    .count-label{font-size:13px;color:var(--muted);}

    /* Prescription cards grid */
    .rx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;}

    /* Card */
    .rx-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:box-shadow .2s;}
    .rx-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.08);}

    .rx-card-top{padding:18px 18px 0;display:flex;align-items:flex-start;gap:12px;}
    .rx-form-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
    .rx-med-name{font-size:14px;font-weight:700;}
    .rx-generic{font-size:11px;color:var(--muted);font-style:italic;margin-top:2px;}
    .rx-status-badge{margin-left:auto;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;flex-shrink:0;}

    .rx-patient-bar{display:flex;align-items:center;gap:8px;padding:10px 18px;background:#f8fafc;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin-top:12px;font-size:12px;}
    .rx-patient-bar i{color:var(--muted);}
    .rx-patient-name{font-weight:700;}
    .rx-doctor-name{color:var(--muted);margin-left:auto;}

    .rx-card-body{padding:12px 18px 0;}
    .rx-row{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:13px;}
    .rx-row i{width:15px;text-align:center;color:var(--muted);font-size:12px;flex-shrink:0;}
    .rx-row-label{color:var(--muted);flex-shrink:0;}
    .rx-row-value{font-weight:600;}
    .route-tag{display:inline-flex;align-items:center;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;background:#f1f5f9;}

    .rx-card-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--border);margin-top:12px;}
    .rx-actions{display:flex;gap:6px;}
    .rx-btn{display:flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid var(--border);background:var(--card);color:var(--muted);cursor:pointer;transition:all .2s;}
    .rx-btn.log{border-color:var(--teal);color:var(--teal);background:var(--teal-light);}
    .rx-btn.log:hover{background:#99f6e4;}
    .rx-btn.flag-btn:hover{border-color:#f59e0b;color:#d97706;background:#fef3c7;}

    /* History table */
    .table-panel{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
    .hist-table{width:100%;border-collapse:collapse;}
    .hist-table th{padding:11px 18px;font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.7px;text-transform:uppercase;background:#f8fafc;border-bottom:1px solid var(--border);text-align:left;}
    .hist-table td{padding:13px 18px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:middle;}
    .hist-table tr:last-child td{border-bottom:none;}
    .hist-table tr:hover td{background:#f8fafc;}

    /* Read-only notice */
    .readonly-notice{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:8px;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;}

    /* Toast */
    .toast{position:fixed;bottom:28px;right:28px;z-index:300;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.14);display:flex;align-items:center;gap:10px;animation:slideUp .35s ease;transition:opacity .4s;}
    .toast.success{background:#0d9488;color:#fff;}
    .toast.error{background:#ef4444;color:#fff;}
    @keyframes slideUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

    /* Modals */
    .modal-overlay{display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);align-items:center;justify-content:center;padding:32px 16px;}
    .modal-overlay.open{display:flex;}
    .modal-box{background:var(--card);border-radius:20px;padding:32px 28px;width:100%;max-width:460px;box-shadow:0 24px 64px rgba(0,0,0,.18);position:relative;animation:fadeUp .3s ease;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
    .modal-title{font-size:18px;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:10px;}
    .modal-title i{color:var(--teal);}
    .modal-subtitle{font-size:13px;color:var(--muted);margin-bottom:20px;}
    .modal-close{position:absolute;top:18px;right:18px;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);font-size:13px;transition:border-color .2s,color .2s;}
    .modal-close:hover{border-color:#ef4444;color:#ef4444;}
    .form-group{margin-bottom:16px;}
    .form-label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;letter-spacing:.6px;text-transform:uppercase;}
    .form-control{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:var(--surface);transition:border-color .2s;}
    .form-control:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.12);}
    .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;}
    .btn-cancel{padding:9px 18px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;}
    .btn-submit{padding:9px 22px;border-radius:10px;background:var(--teal);color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;}
    .btn-amber{padding:9px 22px;border-radius:10px;background:#f59e0b;color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;}

    /* Empty */
    .empty-state{text-align:center;padding:56px 24px;color:var(--muted);}
    .empty-state i{font-size:44px;margin-bottom:14px;color:var(--border);display:block;}

    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45;}
    .overlay.open{display:block;}

    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}
      .main{margin-left:0;}.hamburger{display:flex;}
      .content{padding:20px 16px;}.topbar{padding:16px 20px;}
      .rx-grid{grid-template-columns:1fr;}
      .hist-table{display:block;overflow-x:auto;}
    }
    @keyframes fadein{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    .fade-up{animation:fadein .4s ease both;}
    .d1{animation-delay:.05s}.d2{animation-delay:.10s}.d3{animation-delay:.15s}
  </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="icon-box"><i class="fa-solid fa-heart-pulse" style="color:#fff;font-size:16px;"></i></div>
    <span>ClinicEase</span>
  </div>
  <div class="sidebar-section">Main Menu</div>
  <a href="dashboard.php"     class="nav-link"><i class="fa-solid fa-house"></i>Dashboard</a>
  <a href="patients.php"      class="nav-link"><i class="fa-solid fa-users"></i>Patients</a>
  <a href="prescriptions.php" class="nav-link active"><i class="fa-solid fa-prescription-bottle-medical"></i>Prescriptions</a>
  <a href="vitals.php"        class="nav-link"><i class="fa-solid fa-heart-pulse"></i>Vitals Log</a>
  <a href="schedule.php"      class="nav-link"><i class="fa-solid fa-calendar-check"></i>Schedule</a>
  <div class="sidebar-section">Account</div>
  <a href="profile.php"  class="nav-link"><i class="fa-solid fa-user"></i>Profile</a>
  <a href="settings.php" class="nav-link"><i class="fa-solid fa-gear"></i>Settings</a>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($full_name,0,1)) ?></div>
      <div><div class="user-name"><?= $full_name ?></div><div class="user-role">Nurse · <?= htmlspecialchars($ward) ?></div></div>
    </div>
    <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i>Sign Out</a>
  </div>
</aside>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<main class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="icon-btn hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-left">
        <h2>Prescriptions</h2>
        <p>Active medications for all patients</p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn"><i class="fa-regular fa-bell"></i></div>
      <div class="icon-btn"><i class="fa-regular fa-circle-question"></i></div>
    </div>
  </div>

  <div class="content">

    <?php if ($success): ?>
    <div class="toast success" id="toast"><i class="fa-solid fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
    <div class="toast error" id="toast"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Role banner -->
    <div class="role-banner fade-up d1">
      <div class="rb-icon"><i class="fa-solid fa-user-nurse"></i></div>
      <div>
        <h3>Nurse View — <?= htmlspecialchars($ward) ?></h3>
        <p>You have read access to all active prescriptions. You can log administration and flag concerns to the doctor.</p>
        <div class="permission-pills">
          <span class="perm-pill perm-yes"><i class="fa-solid fa-check"></i> View prescriptions</span>
          <span class="perm-pill perm-yes"><i class="fa-solid fa-check"></i> Log administration</span>
          <span class="perm-pill perm-yes"><i class="fa-solid fa-check"></i> Flag concerns</span>
          <span class="perm-pill perm-no"><i class="fa-solid fa-xmark"></i> Create / Edit</span>
          <span class="perm-pill perm-no"><i class="fa-solid fa-xmark"></i> Change status</span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="main-tabs fade-up d2">
      <button class="main-tab active" onclick="switchTab('active',this)">
        <i class="fa-solid fa-circle-check" style="margin-right:6px;color:#0d9488;"></i>
        Active Prescriptions
        <span style="background:#ccfbf1;color:#0d9488;font-size:11px;padding:1px 7px;border-radius:20px;margin-left:6px;"><?= count($activePrescriptions) ?></span>
      </button>
      <button class="main-tab" onclick="switchTab('history',this)">
        <i class="fa-solid fa-clock-rotate-left" style="margin-right:6px;color:var(--muted);"></i>
        History
        <span style="background:#f1f5f9;color:var(--muted);font-size:11px;padding:1px 7px;border-radius:20px;margin-left:6px;"><?= count($historyPrescriptions) ?></span>
      </button>
    </div>

    <!-- ── Active tab ── -->
    <div id="tab-active">
      <div class="search-bar fade-up d2">
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" class="search-input" id="searchInput" placeholder="Search by patient or medication…" oninput="searchRx()">
        </div>
        <span class="count-label" id="countLabel"><?= count($activePrescriptions) ?> active prescription(s)</span>
      </div>

      <?php if (empty($activePrescriptions)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-prescription-bottle-medical"></i>
          <p>No active prescriptions found.</p>
        </div>
      <?php else: ?>
      <div class="rx-grid fade-up d3" id="rx-grid">
        <?php foreach ($activePrescriptions as $i => $rx):
          $fm = $formMap[$rx['form']] ?? $formMap['Other'];
          $daysLeft = $rx['expiry_date'] ? ceil((strtotime($rx['expiry_date']) - time()) / 86400) : null;
          $isExpiring = $daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0;
          $rc = $routeColors[$rx['route']] ?? '#64748b';
        ?>
        <div class="rx-card" data-search="<?= strtolower($rx['patient_name'].' '.$rx['medication_name'].' '.$rx['form']) ?>"
             style="animation:fadein .4s ease <?= $i*0.05 ?>s both;<?= $isExpiring ? 'border-color:#fcd34d;':'' ?>">

          <div class="rx-card-top">
            <div class="rx-form-icon" style="background:<?= $fm['bg'] ?>;color:<?= $fm['color'] ?>;">
              <i class="fa-solid <?= $fm['icon'] ?>"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div class="rx-med-name"><?= htmlspecialchars($rx['medication_name']) ?></div>
              <?php if ($rx['generic_name']): ?>
              <div class="rx-generic"><?= htmlspecialchars($rx['generic_name']) ?></div>
              <?php endif; ?>
              <?php if ($isExpiring): ?>
              <span style="font-size:10px;font-weight:700;color:#d97706;background:#fef3c7;padding:2px 7px;border-radius:6px;display:inline-block;margin-top:3px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Expires in <?= $daysLeft ?> day<?= $daysLeft==1?'':'s' ?>
              </span>
              <?php endif; ?>
            </div>
            <span class="rx-status-badge" style="background:#ccfbf1;color:#0d9488;">Active</span>
          </div>

          <div class="rx-patient-bar">
            <i class="fa-solid fa-user"></i>
            <span class="rx-patient-name"><?= htmlspecialchars($rx['patient_name']) ?></span>
            <span class="rx-doctor-name"><i class="fa-solid fa-user-doctor" style="margin-right:4px;"></i>Dr. <?= htmlspecialchars($rx['doctor_name']) ?></span>
          </div>

          <div class="rx-card-body">
            <div class="rx-row">
              <i class="fa-solid fa-pills"></i>
              <span class="rx-row-label">Dose:</span>
              <span class="rx-row-value"><?= htmlspecialchars($rx['dosage']) ?> — <?= htmlspecialchars($rx['form']) ?></span>
            </div>
            <div class="rx-row">
              <i class="fa-solid fa-clock-rotate-left"></i>
              <span class="rx-row-label">Frequency:</span>
              <span class="rx-row-value"><?= htmlspecialchars($rx['frequency']) ?></span>
            </div>
            <div class="rx-row">
              <i class="fa-solid fa-route"></i>
              <span class="rx-row-label">Route:</span>
              <span class="route-tag" style="color:<?= $rc ?>"><?= htmlspecialchars($rx['route']) ?></span>
            </div>
            <?php if ($rx['indication']): ?>
            <div class="rx-row">
              <i class="fa-solid fa-notes-medical"></i>
              <span class="rx-row-label">For:</span>
              <span class="rx-row-value"><?= htmlspecialchars($rx['indication']) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <div class="rx-card-footer">
            <div class="expiry-info" style="font-size:11px;color:var(--muted);">
              <?php if ($rx['expiry_date']): ?>
              <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
              Expires <?= date('M j, Y', strtotime($rx['expiry_date'])) ?>
              <?php else: ?><span>No expiry set</span><?php endif; ?>
            </div>
            <div class="rx-actions">
              <!-- Log administration -->
              <button class="rx-btn log" title="Log administration"
                onclick="openLogAdmin(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-clipboard-check"></i> Log
              </button>
              <!-- Flag concern -->
              <button class="rx-btn flag-btn" title="Flag a concern"
                onclick="openFlag(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-flag"></i>
              </button>
            </div>
          </div>

          <!-- Instructions banner if present -->
          <?php if ($rx['instructions']): ?>
          <div style="background:#fffbeb;border-top:1px solid #fde68a;padding:10px 18px;font-size:12px;color:#78350f;">
            <i class="fa-solid fa-circle-info" style="margin-right:5px;color:#d97706;"></i>
            <?= nl2br(htmlspecialchars($rx['instructions'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── History tab ── -->
    <div id="tab-history" style="display:none;">
      <div class="table-panel fade-up d2">
        <?php if (empty($historyPrescriptions)): ?>
          <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><p>No history found.</p></div>
        <?php else: ?>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Medication</th>
              <th>Patient</th>
              <th>Dosage</th>
              <th>Doctor</th>
              <th>Prescribed</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($historyPrescriptions as $rx):
            $fm = $formMap[$rx['form']] ?? $formMap['Other'];
            $sm = $statusMap[$rx['status']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9'];
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:9px;background:<?= $fm['bg'] ?>;color:<?= $fm['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">
                  <i class="fa-solid <?= $fm['icon'] ?>"></i>
                </div>
                <div>
                  <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($rx['medication_name']) ?></div>
                  <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($rx['dosage']) ?> · <?= htmlspecialchars($rx['form']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-weight:600;font-size:13px;"><?= htmlspecialchars($rx['patient_name']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($rx['frequency']) ?></td>
            <td style="font-size:12px;color:var(--muted);">Dr. <?= htmlspecialchars($rx['doctor_name']) ?></td>
            <td style="font-size:12px;color:var(--muted);"><?= date('M j, Y', strtotime($rx['prescribed_date'])) ?></td>
            <td>
              <span style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">
                <?= $rx['status'] ?>
              </span>
            </td>
            <td><span class="readonly-notice"><i class="fa-solid fa-lock"></i> Read-only</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<!-- ══ MODAL: Log Administration ══ -->
<div class="modal-overlay" id="logModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('logModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title"><i class="fa-solid fa-clipboard-check"></i>Log Administration</div>
    <div class="modal-subtitle">Recording medication given for <strong id="log-name"></strong></div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="log_admin">
      <input type="hidden" name="prescription_id" id="log-id">
      <div class="form-group">
        <label class="form-label">Administration Note</label>
        <textarea name="admin_note" class="form-control" rows="3"
          placeholder="e.g. Given orally with water at 8:00 AM. Patient tolerated well."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('logModal')">Cancel</button>
        <button type="submit" class="btn-submit"><i class="fa-solid fa-clipboard-check" style="margin-right:6px;"></i>Log Administration</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Flag Concern ══ -->
<div class="modal-overlay" id="flagModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('flagModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title" style="color:#d97706;"><i class="fa-solid fa-flag" style="color:#d97706;"></i>Flag a Concern</div>
    <div class="modal-subtitle">Flag an issue with <strong id="flag-name"></strong> for the attending doctor.</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="flag">
      <input type="hidden" name="prescription_id" id="flag-id">
      <div class="form-group">
        <label class="form-label">Concern / Observation</label>
        <textarea name="concern" class="form-control" rows="4" required
          placeholder="e.g. Patient showing signs of adverse reaction. Rash observed on forearm after second dose."></textarea>
      </div>
      <div style="padding:10px 14px;background:#fef3c7;border-radius:10px;font-size:12px;color:#92400e;margin-bottom:4px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>
        This note will be appended to the prescription and visible to the attending doctor. For emergencies, contact the doctor directly.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('flagModal')">Cancel</button>
        <button type="submit" class="btn-amber"><i class="fa-solid fa-flag" style="margin-right:6px;"></i>Submit Flag</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('open');}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>{m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});});

function switchTab(tab, btn) {
  document.querySelectorAll('.main-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-active').style.display  = tab==='active'  ? '' : 'none';
  document.getElementById('tab-history').style.display = tab==='history' ? '' : 'none';
}

function searchRx() {
  const q   = document.getElementById('searchInput').value.toLowerCase();
  const cards = document.querySelectorAll('#rx-grid .rx-card');
  let visible = 0;
  cards.forEach(c => {
    const match = c.dataset.search.includes(q);
    c.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.getElementById('countLabel').textContent = `${visible} active prescription(s)`;
}

function openLogAdmin(id, name) {
  document.getElementById('log-id').value = id;
  document.getElementById('log-name').textContent = name;
  openModal('logModal');
}

function openFlag(id, name) {
  document.getElementById('flag-id').value = id;
  document.getElementById('flag-name').textContent = name;
  openModal('flagModal');
}

const toast = document.getElementById('toast');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>