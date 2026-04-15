<?php
// Session already started in helpers.php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* ─── Database connection ─── */
$db = new Database();

/* ─── Resolve doctor_id ─── */
$doctorRow = $db->table('doctors')->where('user_id', $userId)->get();
if (!$doctorRow) die('Doctor record not found.');
$doctorId = (int) $doctorRow['doctor_id'];

/* ─── Doctor display name ─── */
$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($me['full_name'] ?? '');

/* ─── Fetch all patients for create form dropdown ─── */
$patients = $db->table('patients pt')
    ->select('pt.patient_id, CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.username')
    ->left_join('users u', 'pt.user_id = u.user_id')
    ->left_join('user_profiles p', 'u.user_id = p.user_id')
    ->order_by('p.last_name')
    ->get_all();

/* ═══════════════════════════════════════════════════════
   POST HANDLER — PRG pattern
═══════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    /* ── Create prescription ── */
    if ($action === 'create') {
        $required = ['patient_id','medication_name','dosage','form','frequency','prescribed_date'];
        $missing  = array_filter($required, fn($f) => empty($_POST[$f]));
        if ($missing) {
            $_SESSION['rx_error'] = 'Please fill in all required fields.';
            header('Location: ' . url('doctor/prescriptions'));
            exit;
        }
        try {
            $db->table('prescriptions')->insert([
                'patient_id' => (int)$_POST['patient_id'],
                'doctor_id' => $doctorId,
                'record_id' => $_POST['record_id'] ?: null,
                'appointment_id' => $_POST['appointment_id'] ?: null,
                'medication_name' => trim($_POST['medication_name']),
                'generic_name' => trim($_POST['generic_name']) ?: null,
                'dosage' => trim($_POST['dosage']),
                'form' => $_POST['form'],
                'frequency' => trim($_POST['frequency']),
                'duration_days' => $_POST['duration_days'] ?: null,
                'quantity' => $_POST['quantity'] ?: null,
                'route' => $_POST['route'] ?? 'Oral',
                'instructions' => trim($_POST['instructions']) ?: null,
                'indication' => trim($_POST['indication']) ?: null,
                'prescribed_date' => $_POST['prescribed_date'],
                'expiry_date' => $_POST['expiry_date'] ?: null,
                'refills_allowed' => (int)($_POST['refills_allowed'] ?? 0),
                'status' => 'Active'
            ]);
            $_SESSION['rx_success'] = 'Prescription created successfully.';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['rx_error'] = 'Failed to create prescription.';
        }
        header('Location: ' . url('doctor/prescriptions'));
        exit;
    }

    /* ── Edit prescription ── */
    if ($action === 'edit' && isset($_POST['prescription_id'])) {
        $rxId = (int)$_POST['prescription_id'];
        try {
            $db->table('prescriptions')
                ->where('prescription_id', $rxId)
                ->where('doctor_id', $doctorId)
                ->update([
                    'medication_name' => trim($_POST['medication_name']),
                    'generic_name' => trim($_POST['generic_name']) ?: null,
                    'dosage' => trim($_POST['dosage']),
                    'form' => $_POST['form'],
                    'frequency' => trim($_POST['frequency']),
                    'duration_days' => $_POST['duration_days'] ?: null,
                    'quantity' => $_POST['quantity'] ?: null,
                    'route' => $_POST['route'],
                    'instructions' => trim($_POST['instructions']) ?: null,
                    'indication' => trim($_POST['indication']) ?: null,
                    'expiry_date' => $_POST['expiry_date'] ?: null,
                    'refills_allowed' => (int)$_POST['refills_allowed'],
                    'status' => $_POST['status']
                ]);
            $_SESSION['rx_success'] = 'Prescription updated.';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['rx_error'] = 'Update failed.';
        }
        header('Location: ' . url('doctor/prescriptions'));
        exit;
    }

    /* ── Approve refill ── */
    if ($action === 'approve_refill' && isset($_POST['prescription_id'])) {
        $rxId = (int)$_POST['prescription_id'];
        $db->table('prescriptions')
            ->where('prescription_id', $rxId)
            ->where('doctor_id', $doctorId)
            ->update(['refills_used' => 'GREATEST(refills_used - 1, 0)']);
        
        $_SESSION['rx_success'] = 'Refill approved.';
        header('Location: ' . url('doctor/prescriptions'));
        exit;
    }

    /* ── Update status ── */
    if ($action === 'set_status' && isset($_POST['prescription_id'], $_POST['status'])) {
        $rxId   = (int)$_POST['prescription_id'];
        $status = $_POST['status'];
        $allowed = ['Active','Completed','Discontinued','Expired'];
        if (in_array($status, $allowed)) {
            $db->table('prescriptions')
                ->where('prescription_id', $rxId)
                ->where('doctor_id', $doctorId)
                ->update(['status' => $status]);
            
            $_SESSION['rx_success'] = "Prescription marked as {$status}.";
        }
        header('Location: ' . url('doctor/prescriptions'));
        exit;
    }

    header('Location: ' . url('doctor/prescriptions'));
    exit;
}

/* ═══════════════════════════════════════════════════════
   GET — Flash + data
═══════════════════════════════════════════════════════ */
$success = $_SESSION['rx_success'] ?? '';
$error   = $_SESSION['rx_error'] ?? '';
unset($_SESSION['rx_success'], $_SESSION['rx_error']);

/* Auto-expire */
$db->table('prescriptions')
    ->where('doctor_id', $doctorId)
    ->where('status', 'Active')
    ->where('expiry_date', '<', 'CURDATE()')
    ->update(['status' => 'Expired']);

/* ─── Fetch all prescriptions written by this doctor ─── */
$prescriptions = $db->table('prescriptions rx')
    ->select('rx.prescription_id, rx.medication_name, rx.generic_name, rx.dosage, rx.form, rx.frequency, rx.duration_days, rx.route, rx.instructions, rx.indication, rx.prescribed_date, rx.expiry_date, rx.refills_allowed, rx.refills_used, rx.status, rx.doctor_id, rx.patient_id, (rx.refills_allowed - rx.refills_used) AS refills_remaining, CONCAT(pp.first_name, \' \', pp.last_name) AS patient_name, pu.username AS patient_username')
    ->join('patients pt', 'rx.patient_id = pt.patient_id', 'INNER ')
    ->join('users pu', 'pt.user_id = pu.user_id', 'INNER ')
    ->join('user_profiles pp', 'pt.user_id = pp.user_id', 'INNER ')
    ->where('rx.doctor_id', $doctorId)
    ->order_by('rx.prescribed_date', 'DESC')
    ->get_all();

/* ─── Count summaries ─── */
$counts = ['Active'=>0,'Completed'=>0,'Discontinued'=>0,'Expired'=>0,'pending_refills'=>0];
foreach ($prescriptions as $rx) {
    if (isset($counts[$rx['status']])) $counts[$rx['status']]++;
    // Pending refill: patient used a refill but doctor hasn't re-approved yet
    // Heuristic: refills_used > 0 and status Active (patient requested)
    if ($rx['status'] === 'Active' && $rx['refills_used'] > 0) $counts['pending_refills']++;
}

$formOptions = ['Tablet','Capsule','Syrup','Drops','Injection','Inhaler','Patch','Cream','Ointment','Other'];
$routeOptions = ['Oral','Topical','Intravenous','Intramuscular','Subcutaneous','Inhalation','Sublingual','Other'];

$statusMap = [
    'Active'       => ['color'=>'#0d9488','bg'=>'#ccfbf1'],
    'Completed'    => ['color'=>'#64748b','bg'=>'#f1f5f9'],
    'Discontinued' => ['color'=>'#ef4444','bg'=>'#fee2e2'],
    'Expired'      => ['color'=>'#d97706','bg'=>'#fef3c7'],
];
$formMap = [
    'Tablet'=>['icon'=>'fa-prescription-bottle-medical','color'=>'#0d9488','bg'=>'#ccfbf1'],
    'Capsule'=>['icon'=>'fa-capsules','color'=>'#a855f7','bg'=>'#f3e8ff'],
    'Syrup'=>['icon'=>'fa-bottle-droplet','color'=>'#0ea5e9','bg'=>'#e0f2fe'],
    'Drops'=>['icon'=>'fa-eye-dropper','color'=>'#3b82f6','bg'=>'#dbeafe'],
    'Injection'=>['icon'=>'fa-syringe','color'=>'#ef4444','bg'=>'#fee2e2'],
    'Inhaler'=>['icon'=>'fa-lungs','color'=>'#10b981','bg'=>'#d1fae5'],
    'Patch'=>['icon'=>'fa-bandage','color'=>'#f59e0b','bg'=>'#fef3c7'],
    'Cream'=>['icon'=>'fa-hand-dots','color'=>'#d97706','bg'=>'#fef9c3'],
    'Ointment'=>['icon'=>'fa-hand-dots','color'=>'#d97706','bg'=>'#fef9c3'],
    'Other'=>['icon'=>'fa-pills','color'=>'#64748b','bg'=>'#f1f5f9'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Doctor Prescriptions</title>
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

    /* Page header */
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:14px;}
    .page-title{font-size:16px;font-weight:700;}
    .page-subtitle{font-size:13px;color:var(--muted);margin-top:3px;}
    .filter-tabs{display:flex;gap:4px;background:var(--border);padding:4px;border-radius:10px;}
    .filter-tab{padding:6px 14px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:transparent;color:var(--muted);transition:background .2s,color .2s;}
    .filter-tab.active{background:var(--card);color:var(--teal);box-shadow:0 1px 4px rgba(0,0,0,.08);}
    .new-btn{display:flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;background:var(--teal);color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:background .2s,transform .15s;}
    .new-btn:hover{background:#0f766e;transform:translateY(-1px);}

    /* Table */
    .table-panel{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
    .rx-table{width:100%;border-collapse:collapse;}
    .rx-table th{padding:12px 20px;font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.8px;text-transform:uppercase;background:#f8fafc;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;}
    .rx-table td{padding:14px 20px;font-size:13px;border-bottom:1px solid var(--border);vertical-align:middle;}
    .rx-table tr:last-child td{border-bottom:none;}
    .rx-table tr:hover td{background:#f8fafc;}

    .med-cell{display:flex;align-items:center;gap:12px;}
    .med-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;}
    .med-name{font-weight:700;font-size:13px;}
    .med-generic{font-size:11px;color:var(--muted);font-style:italic;margin-top:1px;}
    .patient-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;background:#f1f5f9;font-size:12px;font-weight:600;}
    .status-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;}
    .refill-indicator{display:flex;align-items:center;gap:5px;}
    .refill-dot{width:8px;height:8px;border-radius:50%;background:var(--border);}
    .refill-dot.used{background:var(--teal);}
    .pending-refill-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;background:#fef3c7;color:#d97706;font-size:10px;font-weight:700;margin-left:6px;}

    .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:var(--card);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;transition:all .2s;}
    .act-btn:hover{border-color:var(--teal);color:var(--teal);background:var(--teal-light);}
    .act-btn.danger:hover{border-color:#ef4444;color:#ef4444;background:#fee2e2;}
    .act-btn.approve:hover{border-color:#10b981;color:#10b981;background:#d1fae5;}
    .table-actions{display:flex;gap:5px;}

    /* Empty */
    .empty-state{text-align:center;padding:56px 24px;color:var(--muted);}
    .empty-state i{font-size:44px;margin-bottom:14px;color:var(--border);display:block;}

    /* Toast */
    .toast{position:fixed;bottom:28px;right:28px;z-index:300;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.14);display:flex;align-items:center;gap:10px;animation:slideUp .35s ease;transition:opacity .4s;}
    .toast.success{background:#0d9488;color:#fff;}
    .toast.error{background:#ef4444;color:#fff;}
    @keyframes slideUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

    /* Modals */
    .modal-overlay{display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);align-items:flex-start;justify-content:center;padding:32px 16px;overflow-y:auto;}
    .modal-overlay.open{display:flex;}
    .modal-box{background:var(--card);border-radius:20px;padding:32px 28px;width:100%;max-width:580px;box-shadow:0 24px 64px rgba(0,0,0,.18);position:relative;animation:fadeUp .3s ease;margin:auto;}
    .modal-box.sm{max-width:380px;text-align:center;}
    @keyframes fadeUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
    .modal-title{font-size:18px;font-weight:700;margin-bottom:22px;display:flex;align-items:center;gap:10px;}
    .modal-title i{color:var(--teal);}
    .modal-close{position:absolute;top:18px;right:18px;width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--card);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--muted);font-size:13px;transition:border-color .2s,color .2s;}
    .modal-close:hover{border-color:#ef4444;color:#ef4444;}

    .form-group{margin-bottom:15px;}
    .form-label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;letter-spacing:.6px;text-transform:uppercase;}
    .form-label .req{color:#ef4444;}
    .form-control{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--navy);background:var(--surface);transition:border-color .2s,box-shadow .2s;}
    .form-control:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(13,148,136,.12);}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}

    .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:22px;}
    .btn-cancel{padding:9px 18px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;}
    .btn-submit{padding:9px 22px;border-radius:10px;background:var(--teal);color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;}
    .btn-submit:hover{background:#0f766e;}
    .btn-red{padding:9px 22px;border-radius:10px;background:#ef4444;color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;}
    .btn-green{padding:9px 22px;border-radius:10px;background:#10b981;color:#fff;font-size:13px;font-weight:700;border:none;cursor:pointer;}
    .confirm-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px;}

    /* Mobile */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45;}
    .overlay.open{display:block;}
    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);}.sidebar.open{transform:translateX(0);}
      .main{margin-left:0;}.hamburger{display:flex;}
      .content{padding:20px 16px;}.topbar{padding:16px 20px;}
      .form-row,.form-row-3{grid-template-columns:1fr;}
      .rx-table{display:block;overflow-x:auto;}
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
        <h2>Prescriptions</h2>
        <p>Create and manage patient prescriptions</p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn"><i class="fa-regular fa-bell"></i>
        <?php if ($counts['pending_refills'] > 0): ?><span class="dot"></span><?php endif; ?>
      </div>
      <div class="icon-btn"><i class="fa-regular fa-circle-question"></i></div>
    </div>
  </div>

  <div class="content">

    <?php if ($success): ?>
    <div class="toast success" id="toast"><i class="fa-solid fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
    <div class="toast error" id="toast"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Role badge -->
    <div class="role-badge fade-up d1"><i class="fa-solid fa-user-doctor"></i>Doctor — Full prescription access</div>

    <!-- Stats -->
    <div class="stats-grid fade-up d1">
      <div class="stat-card">
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-value"><?= $counts['Active'] ?></div><div class="stat-label">Active</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-rotate"></i></div>
        <div><div class="stat-value"><?= $counts['pending_refills'] ?></div><div class="stat-label">Pending Refills</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="stat-value"><?= $counts['Completed'] ?></div><div class="stat-label">Completed</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#ef4444;"><i class="fa-solid fa-ban"></i></div>
        <div><div class="stat-value"><?= $counts['Discontinued'] + $counts['Expired'] ?></div><div class="stat-label">Stopped / Expired</div></div>
      </div>
    </div>

    <!-- Page header -->
    <div class="page-header fade-up d2">
      <div>
        <div class="page-title">All Prescriptions Written</div>
        <div class="page-subtitle"><?= count($prescriptions) ?> total across all patients</div>
      </div>
      <div>
        <input type="text" name="search" id="searchInput" placeholder="Search prescriptions..." class="form-control" oninput="filterRx()"/>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <div class="filter-tabs">
          <button class="filter-tab active" onclick="filterRx('all',this)">All</button>
          <button class="filter-tab" onclick="filterRx('active',this)">Active</button>
          <button class="filter-tab" onclick="filterRx('pending',this)">Refills</button>
          <button class="filter-tab" onclick="filterRx('completed',this)">Completed</button>
          <button class="filter-tab" onclick="filterRx('discontinued',this)">Stopped</button>
        </div>
        <button class="new-btn" onclick="openModal('createModal')">
          <i class="fa-solid fa-plus"></i> New Prescription
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-panel fade-up d3">
      <?php if (empty($prescriptions)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-prescription-bottle-medical"></i>
          <p>No prescriptions written yet. Click <strong>New Prescription</strong> to start.</p>
        </div>
      <?php else: ?>
      <table class="rx-table">
        <thead>
          <tr>
            <th>Medication</th>
            <th>Patient</th>
            <th>Dosage & Frequency</th>
            <th>Refills</th>
            <th>Expires</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($prescriptions as $i => $rx):
          $fm = $formMap[$rx['form']] ?? $formMap['Other'];
          $sm = $statusMap[$rx['status']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9'];
          $hasPendingRefill = $rx['status'] === 'Active' && $rx['refills_used'] > 0;
          $daysLeft = $rx['expiry_date'] ? ceil((strtotime($rx['expiry_date']) - time()) / 86400) : null;
          $isExpiring = $rx['status'] === 'Active' && $daysLeft !== null && $daysLeft <= 14 && $daysLeft >= 0;

          // filter tags
          $filterTag = strtolower($rx['status']);
          if ($hasPendingRefill) $filterTag .= ' pending';
        ?>
        <tr data-filter="<?= $filterTag ?>" style="animation:fadein .35s ease <?= $i*0.04 ?>s both;">
          <td>
            <div class="med-cell">
              <div class="med-icon" style="background:<?= $fm['bg'] ?>;color:<?= $fm['color'] ?>;">
                <i class="fa-solid <?= $fm['icon'] ?>"></i>
              </div>
              <div>
                <div class="med-name"><?= htmlspecialchars($rx['medication_name']) ?></div>
                <?php if ($rx['generic_name']): ?>
                <div class="med-generic"><?= htmlspecialchars($rx['generic_name']) ?></div>
                <?php endif; ?>
                <div style="font-size:11px;color:var(--muted);margin-top:1px;"><?= date('M j, Y', strtotime($rx['prescribed_date'])) ?></div>
              </div>
            </div>
          </td>
          <td>
            <div class="patient-chip"><i class="fa-solid fa-user" style="color:var(--muted);font-size:11px;"></i><?= htmlspecialchars($rx['patient_name']) ?></div>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($rx['dosage']) ?> · <?= htmlspecialchars($rx['form']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($rx['frequency']) ?></div>
          </td>
          <td>
            <div class="refill-indicator">
              <?php for($d=0;$d<max($rx['refills_allowed'],1);$d++): ?>
              <div class="refill-dot <?= $d < $rx['refills_used'] ? 'used':'' ?>"></div>
              <?php endfor; ?>
              <span style="font-size:11px;color:var(--muted);margin-left:4px;"><?= $rx['refills_used'] ?>/<?= $rx['refills_allowed'] ?></span>
            </div>
            <?php if ($hasPendingRefill): ?>
            <span class="pending-refill-badge"><i class="fa-solid fa-bell"></i> Refill requested</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($rx['expiry_date']): ?>
            <span style="font-size:12px;font-weight:600;<?= $isExpiring ? 'color:#d97706;' : '' ?>">
              <?= $isExpiring ? '<i class="fa-solid fa-triangle-exclamation" style="margin-right:3px;"></i>':'' ?>
              <?= date('M j, Y', strtotime($rx['expiry_date'])) ?>
            </span>
            <?php else: ?><span style="color:var(--muted);font-size:12px;">No expiry</span><?php endif; ?>
          </td>
          <td>
            <span class="status-badge" style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>;">
              <?= $rx['status'] ?>
            </span>
          </td>
          <td>
            <div class="table-actions">
              <!-- Edit -->
              <button class="act-btn" title="Edit"
                onclick="openEdit(<?= htmlspecialchars(json_encode($rx), ENT_QUOTES) ?>)">
                <i class="fa-solid fa-pen"></i>
              </button>
              <!-- Approve refill -->
              <?php if ($hasPendingRefill): ?>
              <button class="act-btn approve" title="Approve refill"
                onclick="openApproveRefill(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-check"></i>
              </button>
              <?php endif; ?>
              <!-- Mark completed -->
              <?php if ($rx['status'] === 'Active'): ?>
              <button class="act-btn" title="Mark completed"
                onclick="setStatus(<?= $rx['prescription_id'] ?>, 'Completed')">
                <i class="fa-solid fa-flag-checkered"></i>
              </button>
              <!-- Discontinue -->
              <button class="act-btn danger" title="Discontinue"
                onclick="openDiscontinue(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-ban"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
</main>

<!-- ══ MODAL: Create Prescription ══ -->
<div class="modal-overlay" id="createModal">
  <div class="modal-box" style="max-width:620px;">
    <button class="modal-close" onclick="closeModal('createModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title"><i class="fa-solid fa-prescription-bottle-medical"></i>New Prescription</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="create">
  <div class="form-group">
    <label class="form-label">Patient <span class="req">*</span></label>
    <!-- Add list="patients" to connect input with datalist -->
    <input name="patient_id" class="form-control" placeholder="Select patient" list="patients" required>

    <!-- Give the datalist an id -->
    <datalist id="patients">
        <?php foreach ($patients as $p): ?>
            <option value="<?= $p['patient_id'] ?> <?= htmlspecialchars($p['full_name']) ?> ">
              
            </option> 
        <?php endforeach; ?> 
    </datalist>
</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Medication Name <span class="req">*</span></label>
          <input type="text" name="medication_name" class="form-control" placeholder="e.g. Amlodipine" required>
        </div>
        <div class="form-group">
          <label class="form-label">Generic Name</label>
          <input type="text" name="generic_name" class="form-control" placeholder="e.g. Amlodipine Besylate">
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Dosage <span class="req">*</span></label>
          <input type="text" name="dosage" class="form-control" placeholder="e.g. 5mg" required>
        </div>
        <div class="form-group">
          <label class="form-label">Form <span class="req">*</span></label>
          <select name="form" class="form-control" required>
            <?php foreach ($formOptions as $f): ?><option><?= $f ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Route</label>
          <select name="route" class="form-control">
            <?php foreach ($routeOptions as $r): ?><option><?= $r ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Frequency <span class="req">*</span></label>
        <input type="text" name="frequency" class="form-control" placeholder="e.g. Twice daily with meals" required>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Duration (days)</label>
          <input type="number" name="duration_days" class="form-control" min="1" placeholder="30">
        </div>
        <div class="form-group">
          <label class="form-label">Quantity</label>
          <input type="number" name="quantity" class="form-control" min="1" placeholder="60">
        </div>
        <div class="form-group">
          <label class="form-label">Refills Allowed</label>
          <input type="number" name="refills_allowed" class="form-control" min="0" max="12" value="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Prescribed Date <span class="req">*</span></label>
          <input type="date" name="prescribed_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expiry_date" class="form-control" min="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Indication</label>
        <input type="text" name="indication" class="form-control" placeholder="e.g. Hypertension Stage 1">
      </div>
      <div class="form-group">
        <label class="form-label">Special Instructions</label>
        <textarea name="instructions" class="form-control" rows="3" placeholder="e.g. Take with food. Do not stop abruptly."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Cancel</button>
        <button type="submit" class="btn-submit"><i class="fa-solid fa-check" style="margin-right:6px;"></i>Create Prescription</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Edit Prescription ══ -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box" style="max-width:620px;">
    <button class="modal-close" onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title"><i class="fa-solid fa-pen"></i>Edit Prescription</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="prescription_id" id="edit-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Medication Name <span class="req">*</span></label>
          <input type="text" name="medication_name" id="edit-med" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Generic Name</label>
          <input type="text" name="generic_name" id="edit-gen" class="form-control">
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Dosage <span class="req">*</span></label>
          <input type="text" name="dosage" id="edit-dose" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Form</label>
          <select name="form" id="edit-form" class="form-control">
            <?php foreach ($formOptions as $f): ?><option><?= $f ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Route</label>
          <select name="route" id="edit-route" class="form-control">
            <?php foreach ($routeOptions as $r): ?><option><?= $r ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Frequency</label>
        <input type="text" name="frequency" id="edit-freq" class="form-control">
      </div>
      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label">Duration (days)</label>
          <input type="number" name="duration_days" id="edit-dur" class="form-control" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Quantity</label>
          <input type="number" name="quantity" id="edit-qty" class="form-control" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Refills Allowed</label>
          <input type="number" name="refills_allowed" id="edit-refills" class="form-control" min="0" max="12">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expiry_date" id="edit-expiry" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="edit-status" class="form-control">
            <option>Active</option><option>Completed</option>
            <option>Discontinued</option><option>Expired</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Indication</label>
        <input type="text" name="indication" id="edit-indication" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label">Special Instructions</label>
        <textarea name="instructions" id="edit-instructions" class="form-control" rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn-submit"><i class="fa-solid fa-check" style="margin-right:6px;"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Approve Refill ══ -->
<div class="modal-overlay" id="approveModal">
  <div class="modal-box sm">
    <button class="modal-close" onclick="closeModal('approveModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="confirm-icon" style="background:#d1fae5;color:#10b981;"><i class="fa-solid fa-check"></i></div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Approve Refill?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">Approve refill for <strong id="approve-name"></strong>?</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="approve_refill">
      <input type="hidden" name="prescription_id" id="approve-id">
      <div class="modal-footer" style="justify-content:center;">
        <button type="button" class="btn-cancel" onclick="closeModal('approveModal')">Cancel</button>
        <button type="submit" class="btn-green"><i class="fa-solid fa-check" style="margin-right:6px;"></i>Approve</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL: Discontinue ══ -->
<div class="modal-overlay" id="discontinueModal">
  <div class="modal-box sm">
    <button class="modal-close" onclick="closeModal('discontinueModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="confirm-icon" style="background:#fee2e2;color:#ef4444;"><i class="fa-solid fa-ban"></i></div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Discontinue Prescription?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">Stop <strong id="disc-name"></strong> for this patient?</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="set_status">
      <input type="hidden" name="status" value="Discontinued">
      <input type="hidden" name="prescription_id" id="disc-id">
      <div class="modal-footer" style="justify-content:center;">
        <button type="button" class="btn-cancel" onclick="closeModal('discontinueModal')">Cancel</button>
        <button type="submit" class="btn-red"><i class="fa-solid fa-ban" style="margin-right:6px;"></i>Discontinue</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden status form -->
<form method="POST" action="prescriptions.php" id="statusForm" style="display:none;">
  <input type="hidden" name="action" value="set_status">
  <input type="hidden" name="prescription_id" id="sf-id">
  <input type="hidden" name="status" id="sf-status">
</form>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('open');}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>{m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});});

function filterRx(f,btn){
  document.querySelectorAll('.filter-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('tbody tr').forEach(row=>{
    const tag=row.dataset.filter||'';
    row.style.display=(f==='all'||tag.includes(f))?'':'none';
  });
}

function openEdit(rx){
  document.getElementById('edit-id').value          = rx.prescription_id;
  document.getElementById('edit-med').value         = rx.medication_name;
  document.getElementById('edit-gen').value         = rx.generic_name||'';
  document.getElementById('edit-dose').value        = rx.dosage;
  document.getElementById('edit-freq').value        = rx.frequency;
  document.getElementById('edit-dur').value         = rx.duration_days||'';
  document.getElementById('edit-qty').value         = rx.quantity||'';
  document.getElementById('edit-refills').value     = rx.refills_allowed;
  document.getElementById('edit-expiry').value      = rx.expiry_date||'';
  document.getElementById('edit-indication').value  = rx.indication||'';
  document.getElementById('edit-instructions').value= rx.instructions||'';
  setSelectValue('edit-form',  rx.form);
  setSelectValue('edit-route', rx.route);
  setSelectValue('edit-status',rx.status);
  openModal('editModal');
}
function setSelectValue(id,val){const s=document.getElementById(id);for(let o of s.options)if(o.value===val){o.selected=true;break;}}

function openApproveRefill(id,name){
  document.getElementById('approve-id').value=id;
  document.getElementById('approve-name').textContent=name;
  openModal('approveModal');
}
function openDiscontinue(id,name){
  document.getElementById('disc-id').value=id;
  document.getElementById('disc-name').textContent=name;
  openModal('discontinueModal');
}
function setStatus(id,status){
  document.getElementById('sf-id').value=id;
  document.getElementById('sf-status').value=status;
  document.getElementById('statusForm').submit();
}

const toast=document.getElementById('toast');
if(toast)setTimeout(()=>{toast.style.opacity='0';},3500);
</script>

</body>
</html>