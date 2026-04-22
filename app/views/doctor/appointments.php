<?php
// Doctor - Appointments Page

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

/* ─── Get appointments ─── */
$appointments = $db->table('appointments a')
    ->select('a.appointment_id, a.appointment_date, a.appointment_time, a.type, a.status, CONCAT(up.first_name, \' \', up.last_name) AS patient_name, u.email, up.phone')
    ->join('patients p', 'a.patient_id = p.patient_id')
    ->join('user_profiles up', 'p.user_id = up.user_id')
    ->join('users u', 'p.user_id = u.user_id')
    ->where('a.doctor_id', $doctorId)
    ->order_by('a.appointment_date DESC, a.appointment_time DESC')
    ->get_all();

/* ─── Stats ─── */
$totalAppointments = count($appointments);
$todayAppointments = 0;
$upcomingAppointments = 0;
$today = date('Y-m-d');

foreach ($appointments as $apt) {
    if ($apt['appointment_date'] == $today) $todayAppointments++;
    elseif ($apt['appointment_date'] > $today) $upcomingAppointments++;
}

/* ─── All patients for modal ─── */
$allPatients = $db->table('patients p')
    ->select('p.patient_id, CONCAT(up.first_name, \' \', up.last_name) AS patient_name')
    ->join('user_profiles up', 'p.user_id = up.user_id')
    ->join('appointments a', 'a.patient_id = p.patient_id')
    ->where('a.doctor_id', $doctorId)
    ->group_by('p.patient_id')
    ->order_by('up.first_name ASC')
    ->get_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Appointments — ClinicEase</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>
  <style>
    :root {
      --sidebar-w: 260px;
      --teal: #0d9488;
      --teal-light: #ccfbf1;
      --navy: #0f172a;
      --muted: #64748b;
      --surface: #f8fafc;
      --card: #ffffff;
      --border: #e2e8f0;
      --accent: #f59e0b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DM Sans', sans-serif; background: var(--surface); color: var(--navy); display: flex; min-height: 100vh; }

    /* ── Sidebar (mirrors dashboard exactly) ── */
    .sidebar { width: var(--sidebar-w); background: var(--navy); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 50; transition: transform .3s; }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 28px 24px 24px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .sidebar-logo .icon-box { width: 36px; height: 36px; border-radius: 10px; background: var(--teal); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sidebar-logo span { font-weight: 700; font-size: 16px; color: #fff; }
    .sidebar-section { padding: 20px 16px 8px; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; color: #475569; text-transform: uppercase; }
    .nav-link { display: flex; align-items: center; gap: 12px; padding: 10px 16px; margin: 2px 8px; border-radius: 10px; color: #94a3b8; font-size: 14px; font-weight: 500; text-decoration: none; transition: background .2s, color .2s; }
    .nav-link i { width: 18px; text-align: center; font-size: 14px; }
    .nav-link:hover { background: rgba(255,255,255,.07); color: #e2e8f0; }
    .nav-link.active { background: var(--teal); color: #fff; }
    .badge-count { margin-left: auto; background: var(--accent); color: #fff; font-size: 10px; font-weight: 700; border-radius: 20px; padding: 1px 7px; }
    .sidebar-footer { margin-top: auto; padding: 16px; border-top: 1px solid rgba(255,255,255,.08); }
    .user-chip { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: rgba(255,255,255,.05); margin-bottom: 10px; }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--teal), #0ea5e9); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0; }
    .user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role { font-size: 11px; color: #64748b; text-transform: capitalize; }
    .logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px; border-radius: 10px; background: rgba(239,68,68,.12); color: #f87171; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; transition: background .2s; }
    .logout-btn:hover { background: rgba(239,68,68,.22); }

    /* ── Main ── */
    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

    /* ── Topbar ── */
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; background: var(--card); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 40; }
    .topbar-left h2 { font-size: 20px; font-weight: 700; }
    .topbar-left p { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .topbar-right { display: flex; align-items: center; gap: 14px; }
    .icon-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border); background: var(--card); display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; font-size: 15px; position: relative; transition: border-color .2s, color .2s; }
    .icon-btn:hover { border-color: var(--teal); color: var(--teal); }
    .hamburger { display: none; }

    /* ── Content ── */
    .content { padding: 32px; flex: 1; }

    /* ── Role badge ── */
    .role-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700; margin-bottom: 20px; }

    /* ── Stat cards ── */
    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.07); }
    .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .stat-value { font-size: 24px; font-weight: 700; line-height: 1; }
    .stat-label { font-size: 12px; color: var(--muted); margin-top: 3px; }

    /* ── Section card ── */
    .section { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; overflow: hidden; }

    /* ── Table ── */
    .apt-table { width: 100%; border-collapse: collapse; }
    .apt-table th { background: var(--surface); padding: 12px; text-align: left; font-weight: 700; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; border-bottom: 2px solid var(--border); }
    .apt-table td { padding: 14px 12px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
    .apt-row:hover { background: var(--surface); }
    .apt-row:last-child td { border-bottom: none; }

    /* ── Status badges ── */
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
    .status-scheduled  { background: #dbeafe; color: #1d4ed8; }
    .status-scheduled::before  { background: #1d4ed8; }
    .status-completed  { background: #dcfce7; color: #166534; }
    .status-completed::before  { background: #166534; }
    .status-cancelled  { background: #fee2e2; color: #991b1b; }
    .status-cancelled::before  { background: #991b1b; }
    .status-pending    { background: #fef3c7; color: #92400e; }
    .status-pending::before    { background: #92400e; }

    /* ── Search / action bar ── */
    .search-input { flex: 1; min-width: 200px; padding: 10px 14px 10px 38px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: var(--navy); background: var(--surface); transition: border-color .2s, box-shadow .2s; }
    .search-input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.1); background: #fff; }
    .search-wrap { position: relative; flex: 1; min-width: 200px; }
    .search-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 13px; pointer-events: none; }
    .btn-teal { padding: 10px 18px; background: var(--teal); color: #fff; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 7px; font-family: 'DM Sans', sans-serif; transition: background .2s, transform .1s; }
    .btn-teal:hover { background: #0d8078; }
    .btn-teal:active { transform: scale(.97); }

    /* ── Quick links ── */
    .quick-link { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); font-size: 12px; font-weight: 600; color: var(--navy); text-decoration: none; transition: border-color .2s, background .2s, color .2s; }
    .quick-link:hover { border-color: var(--teal); background: var(--teal-light); color: var(--teal); }

    /* ── Modal ── */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 100; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: var(--card); border-radius: 18px; box-shadow: 0 24px 64px rgba(0,0,0,.18); width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; animation: slideUp .25s ease; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 22px 24px; border-bottom: 1px solid var(--border); }
    .modal-header h3 { font-size: 17px; font-weight: 700; }
    .modal-close { background: none; border: none; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: background .2s, color .2s; font-size: 15px; }
    .modal-close:hover { background: var(--surface); color: var(--navy); }
    .modal-body { padding: 22px 24px; }
    .modal-footer { display: flex; gap: 10px; padding: 18px 24px; border-top: 1px solid var(--border); justify-content: flex-end; }
    .form-label { display: block; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--navy); background: var(--surface); transition: border-color .2s, box-shadow .2s; }
    .form-control:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.1); background: #fff; }
    textarea.form-control { resize: vertical; min-height: 80px; }
    .btn-ghost { padding: 10px 16px; background: var(--surface); color: var(--navy); border: 1px solid var(--border); border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .2s; }
    .btn-ghost:hover { background: var(--border); }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 60px 24px; color: var(--muted); }
    .empty-state .empty-icon { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; }
    .empty-state h4 { font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    .empty-state p { font-size: 13px; }

    /* ── Mobile overlay ── */
    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 45; }
    .overlay.open { display: block; }

    /* ── Animations ── */
    @keyframes fadein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-up { animation: fadein .4s ease both; }
    .d1 { animation-delay: .05s } .d2 { animation-delay: .10s } .d3 { animation-delay: .15s } .d4 { animation-delay: .20s }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; } .hamburger { display: flex; }
      .content { padding: 20px 16px; } .topbar { padding: 16px 20px; }
      .apt-table th, .apt-table td { padding: 10px 8px; font-size: 12px; }
      .hide-mobile { display: none; }
    }
  </style>
</head>
<body>

<?php include 'aside.php'; ?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<main class="main">

  <!-- ── Topbar ── -->
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="icon-btn hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-left">
        <h2>Appointments</h2>
        <p><?= date('l, F j, Y') ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn" title="Notifications"><i class="fa-regular fa-bell"></i></div>
      <div class="icon-btn" title="Help"><i class="fa-regular fa-circle-question"></i></div>
    </div>
  </div>

  <div class="content">

    <!-- Role badge -->
    <div class="role-badge fade-up d1">
      <i class="fa-solid fa-calendar-check"></i>Appointments — Manage your schedule
    </div>

    <!-- Stats grid (mirrors dashboard) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 fade-up d1">

      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#0284c7;">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
          <div class="stat-value"><?= $totalAppointments ?></div>
          <div class="stat-label">Total Appointments</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div>
          <div class="stat-value"><?= $todayAppointments ?></div>
          <div class="stat-label">Today's Appointments</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;">
          <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
        <div>
          <div class="stat-value"><?= $upcomingAppointments ?></div>
          <div class="stat-label">Upcoming</div>
        </div>
      </div>

    </div>

    <!-- Appointments section card -->
    <div class="section fade-up d2">

      <!-- Section header -->
      <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
        <div>
          <h3 class="text-[15px] font-bold" style="color:var(--navy);">Appointment Schedule</h3>
          <p class="text-[12px] mt-1" style="color:var(--muted);">View and manage all your appointments</p>
        </div>
      </div>

      <!-- Action bar: search + new button -->
      <div class="flex gap-3 mb-5 flex-wrap items-center">
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" class="search-input" placeholder="Search by patient name, type or status…" oninput="filterTable()">
        </div>
        <button class="btn-teal" onclick="openAddModal()">
          <i class="fa-solid fa-plus"></i> New Appointment
        </button>
      </div>

      <!-- Table -->
      <?php if (count($appointments) > 0): ?>
      <div class="overflow-x-auto">
        <table class="apt-table" id="appointmentsTable">
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Patient</th>
              <th>Type</th>
              <th class="hide-mobile">Contact</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appointments as $apt): ?>
            <tr class="apt-row fade-up">

              <!-- Date & Time -->
              <td>
                <div class="font-semibold text-[13px]" style="color:var(--navy);">
                  <?= date('M d, Y', strtotime($apt['appointment_date'])) ?>
                </div>
                <div class="text-[12px] mt-0.5 font-medium" style="color:var(--teal);">
                  <?= date('h:i A', strtotime($apt['appointment_time'])) ?>
                </div>
              </td>

              <!-- Patient -->
              <td>
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold flex-shrink-0"
                       style="background:var(--teal-light);color:var(--teal);">
                    <?= strtoupper(substr($apt['patient_name'], 0, 1)) ?>
                  </div>
                  <span class="font-semibold text-[13px]" style="color:var(--navy);">
                    <?= htmlspecialchars($apt['patient_name']) ?>
                  </span>
                </div>
              </td>

              <!-- Type -->
              <td>
                <span class="text-[12px] font-medium px-2.5 py-1 rounded-md"
                      style="background:var(--surface);color:var(--muted);border:1px solid var(--border);">
                  <?= htmlspecialchars($apt['type'] ?? 'General') ?>
                </span>
              </td>

              <!-- Contact (hidden on mobile) -->
              <td class="hide-mobile">
                <div class="text-[12px]" style="color:var(--navy);"><?= htmlspecialchars($apt['email'] ?? 'N/A') ?></div>
                <div class="text-[12px] mt-0.5" style="color:var(--muted);"><?= htmlspecialchars($apt['phone'] ?? 'N/A') ?></div>
              </td>

              <!-- Status -->
              <td>
                <?php
                  $s = strtolower($apt['status'] ?? 'pending');
                  $label = ucfirst($apt['status'] ?? 'Pending');
                ?>
                <span class="status-badge status-<?= $s ?>"><?= $label ?></span>
              </td>

              <!-- Action -->
              <td>
                <a href="<?= url('doctor/appointment-detail?id=' . $apt['appointment_id']) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-semibold transition-all"
                   style="background:var(--teal-light);color:var(--teal);"
                   onmouseover="this.style.background='#99f6e4'"
                   onmouseout="this.style.background='var(--teal-light)'">
                  <i class="fa-solid fa-eye"></i> View
                </a>
              </td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- No results msg (hidden by default) -->
      <div id="noResults" class="hidden">
        <div class="empty-state">
          <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
          <h4>No results found</h4>
          <p>Try a different search term</p>
        </div>
      </div>

      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
        <h4>No appointments yet</h4>
        <p>Your scheduled appointments will appear here</p>
      </div>
      <?php endif; ?>

    </div>

  </div><!-- /content -->
</main>

<!-- ── Add Appointment Modal ── -->
<div class="modal-overlay" id="addModal" onclick="event.target.id==='addModal'&&closeAddModal()">
  <div class="modal-box">

    <div class="modal-header">
      <h3><i class="fa-solid fa-calendar-plus mr-2" style="color:var(--teal);"></i>Schedule New Appointment</h3>
      <button class="modal-close" onclick="closeAddModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form id="addAppointmentForm" onsubmit="handleAddAppointment(event)">
      <div class="modal-body">

        <div class="grid grid-cols-1 gap-4">

          <div>
            <label class="form-label" for="patient_id">Select Patient</label>
            <select id="patient_id" name="patient_id" class="form-control" required>
              <option value="">— Choose a patient —</option>
              <?php foreach ($allPatients as $patient): ?>
                <option value="<?= $patient['patient_id'] ?>"><?= htmlspecialchars($patient['patient_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label" for="appointment_date">Date</label>
              <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
            </div>
            <div>
              <label class="form-label" for="appointment_time">Time</label>
              <input type="time" id="appointment_time" name="appointment_time" class="form-control" required>
            </div>
          </div>

          <div>
            <label class="form-label" for="type">Appointment Type</label>
            <select id="type" name="type" class="form-control" required>
              <option value="">— Select type —</option>
              <option value="Consultation">Consultation</option>
              <option value="Follow-up">Follow-up</option>
              <option value="Check-up">Check-up</option>
              <option value="Procedure">Procedure</option>
              <option value="Emergency">Emergency</option>
            </select>
          </div>

          <div>
            <label class="form-label" for="notes">Notes <span style="color:var(--muted);text-transform:none;letter-spacing:0;font-weight:400;">(optional)</span></label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Add any additional notes…"></textarea>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn-teal">
          <i class="fa-solid fa-check"></i> Schedule Appointment
        </button>
      </div>
    </form>

  </div>
</div>

<script>
  /* ── Sidebar ── */
  function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
  }
  function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
  }

  /* ── Modal ── */
  function openAddModal() {
    document.getElementById('addModal').classList.add('open');
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointment_date').min = today;
  }
  function closeAddModal() {
    document.getElementById('addModal').classList.remove('open');
    document.getElementById('addAppointmentForm').reset();
  }

  /* ── Add appointment ── */
  function handleAddAppointment(event) {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.target));
    if (!data.patient_id || !data.appointment_date || !data.appointment_time || !data.type) {
      alert('Please fill in all required fields.');
      return;
    }
    fetch('<?= url('api/doctor/appointments/create') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) { alert('Appointment scheduled successfully!'); closeAddModal(); location.reload(); }
      else alert('Error: ' + (res.message || 'Failed to schedule appointment'));
    })
    .catch(() => alert('An error occurred. Please try again.'));
  }

  /* ── Live search filter ── */
  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#appointmentsTable tbody tr');
    let visible = 0;
    rows.forEach(row => {
      const match = row.textContent.toLowerCase().includes(q);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.classList.toggle('hidden', visible > 0);
  }
</script>
</body>
</html>