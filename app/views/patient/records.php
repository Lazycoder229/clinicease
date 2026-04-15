<?php
// Patient Records - View-Only Medical History
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . url('auth/login'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$db = new Database();

/* ─── Resolve patient_id ─── */
$patientRow = $db->table('patients')->where('user_id', $userId)->get();
if (!$patientRow) die('Patient record not found.');
$patientId = (int) $patientRow['patient_id'];

/* ─── Get patient name ─── */
$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$fullName = htmlspecialchars($me['full_name'] ?? 'Patient');

/* ─── Latest vitals from health_metrics ─── */
$latestVitals = $db->table('health_metrics hm')
    ->where('hm.patient_id', $patientId)
    ->order_by('hm.recorded_at', 'DESC')
    ->limit(1)
    ->get();

/* ─── Vitals trend (last 6 months) from health_metrics ─── */
$vitalsHistory = $db->table('health_metrics')
    ->select('DATE(recorded_at) AS date, blood_pressure_sys, blood_pressure_dia, heart_rate, blood_sugar, weight_kg, bmi')
    ->where('patient_id', $patientId)
    ->order_by('recorded_at', 'ASC')
    ->get_all();

/* ─── Health records list - JOIN with doctors for doctor name and specialization ─── */
$records = $db->table('health_records hr')
    ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.bmi, hr.doctor_notes, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('hr.patient_id', $patientId)
    ->order_by('hr.record_date', 'DESC')
    ->get_all();

/* ─── Prepare records by ID for modal JavaScript ─── */
$recordsById = [];
foreach ($records as $record) {
    $recordsById[$record['record_id']] = $record;
}

/* ─── Type icon/color map ─── */
$typeMap = [
    'General Check-up' => ['icon' => 'fa-stethoscope',    'color' => '#0d9488', 'bg' => '#ccfbf1'],
    'Follow-up'        => ['icon' => 'fa-rotate-right',   'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
    'Emergency'        => ['icon' => 'fa-truck-medical',  'color' => '#ef4444', 'bg' => '#fee2e2'],
    'Vaccination'      => ['icon' => 'fa-syringe',        'color' => '#a855f7', 'bg' => '#f3e8ff'],
    'Laboratory'       => ['icon' => 'fa-flask',          'color' => '#10b981', 'bg' => '#d1fae5'],
    'Consultation'     => ['icon' => 'fa-comment-medical','color' => '#d97706', 'bg' => '#fef3c7'],
    'Procedure'        => ['icon' => 'fa-scalpel',        'color' => '#64748b', 'bg' => '#f1f5f9'],
    'Dental'           => ['icon' => 'fa-tooth',          'color' => '#f59e0b', 'bg' => '#fef9c3'],
    'Eye Exam'         => ['icon' => 'fa-eye',            'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'Other'            => ['icon' => 'fa-file-medical',   'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$statusColors = [
    'Final'    => ['color' => '#0d9488', 'bg' => '#ccfbf1'],
    'Draft'    => ['color' => '#d97706', 'bg' => '#fef3c7'],
    'Archived' => ['color' => '#64748b', 'bg' => '#f1f5f9'],
];

/* ─── BMI label helper ─── */
function bmiLabel(float $bmi): string {
    return match(true) {
        $bmi < 18.5 => 'Underweight',
        $bmi < 25.0 => 'Normal',
        $bmi < 30.0 => 'Overweight',
        default     => 'Obese',
    };
}
function bmiColor(float $bmi): string {
    return match(true) {
        $bmi < 18.5 => '#3b82f6',
        $bmi < 25.0 => '#0d9488',
        $bmi < 30.0 => '#d97706',
        default     => '#ef4444',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Health Records — ClinicEase</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar { display: flex; align-items: center; justify-content: space-between; padding: 20px 32px; background: var(--card); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 40; }
    .topbar-left h2 { font-size: 20px; font-weight: 700; }
    .topbar-left p { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .topbar-right { display: flex; align-items: center; gap: 14px; }
    .icon-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border); background: var(--card); display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; font-size: 15px; position: relative; transition: border-color .2s, color .2s; }
    .icon-btn:hover { border-color: var(--teal); color: var(--teal); }
    .hamburger { display: none; }
    .content { padding: 32px; flex: 1; }

    .role-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 8px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700; margin-bottom: 20px; }

    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.07); }
    .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .stat-value { font-size: 24px; font-weight: 700; line-height: 1; }
    .stat-label { font-size: 12px; color: var(--muted); margin-top: 3px; }

    .section { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; overflow: hidden; }

    .vital-card { display: flex; flex-direction: column; gap: 8px; padding: 14px; border: 1px solid var(--border); border-radius: 10px; background: var(--card); }
    .vital-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; }
    .vital-value { font-size: 16px; font-weight: 700; }
    .vital-label { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .vital-note { font-size: 10px; margin-top: 2px; font-weight: 500; }

    .record-item { padding: 16px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; transition: all .2s; cursor: pointer; }
    .record-item:hover { border-color: var(--teal); background: var(--surface); }

    .record-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-size: 16px; }

    .quick-link { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); font-size: 12px; font-weight: 600; color: var(--navy); text-decoration: none; transition: border-color .2s, background .2s, color .2s; }
    .quick-link:hover { border-color: var(--teal); background: var(--teal-light); color: var(--teal); }

    .empty-state { text-align: center; padding: 60px 24px; color: var(--muted); }
    .empty-state .empty-icon { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; }
    .empty-state h4 { font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    .empty-state p { font-size: 13px; }

    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 100; align-items: center; justify-content: center; }
    .modal.open { display: flex; }
    .modal-content { background: var(--card); border-radius: 16px; padding: 28px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .modal-header h2 { font-size: 18px; font-weight: 700; }
    .modal-close { width: 32px; height: 32px; border: none; background: var(--surface); border-radius: 8px; cursor: pointer; font-size: 16px; color: var(--muted); }

    .detail-section { margin-bottom: 20px; }
    .detail-section-title { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 12px; }
    .detail-grid { display: grid; gap: 12px; }
    .detail-item { padding: 12px; background: var(--surface); border-radius: 8px; }
    .detail-item.full { grid-column: 1 / -1; }
    .d-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .d-value { font-size: 13px; color: var(--navy); }

    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 45; }
    .overlay.open { display: block; }

    @keyframes fadein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-up { animation: fadein .4s ease both; }
    .d1 { animation-delay: .05s } .d2 { animation-delay: .10s } .d3 { animation-delay: .15s }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; } .hamburger { display: flex; }
      .content { padding: 20px 16px; } .topbar { padding: 16px 20px; }
      [style*="grid-template-columns:repeat(auto-fit"] { grid-template-columns: 1fr !important; }
    }
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
        <h2>My Health Records</h2>
        <p><?= date('l, F j, Y') ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn" title="Notifications"><i class="fa-regular fa-bell"></i></div>
      <div class="icon-btn" title="Help"><i class="fa-regular fa-circle-question"></i></div>
    </div>
  </div>

  <div class="content">

    <div class="role-badge fade-up d1">
      <i class="fa-solid fa-file-medical"></i>Your Complete Medical History
    </div>

    <!-- Vitals Summary -->
    <?php if ($latestVitals): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:24px;width:100%;">
      <?php
        $vitals_list = [
          ['icon'=>'fa-heart-pulse',      'color'=>'#ef4444','bg'=>'#fee2e2', 'label'=>'Blood Pressure', 'value'=>$latestVitals['blood_pressure_sys'].'/'.$latestVitals['blood_pressure_dia'].' mmHg'],
          ['icon'=>'fa-wave-square',      'color'=>'#3b82f6','bg'=>'#dbeafe', 'label'=>'Heart Rate',     'value'=>$latestVitals['heart_rate'].' bpm'],
          ['icon'=>'fa-droplet',          'color'=>'#0d9488','bg'=>'#ccfbf1', 'label'=>'Blood Sugar',    'value'=>$latestVitals['blood_sugar'].' mg/dL'],
          ['icon'=>'fa-lungs',            'color'=>'#0ea5e9','bg'=>'#e0f2fe', 'label'=>'Oxygen Sat.',     'value'=>$latestVitals['oxygen_saturation'].'%'],
          ['icon'=>'fa-temperature-half', 'color'=>'#a855f7','bg'=>'#f3e8ff', 'label'=>'Temperature',    'value'=>$latestVitals['temperature'].' °C'],
          ['icon'=>'fa-weight-scale',     'color'=>'#d97706','bg'=>'#fef3c7', 'label'=>'Weight',         'value'=>$latestVitals['weight_kg'].' kg'],
        ];
        foreach($vitals_list as $v):
      ?>
      <div class="vital-card fade-up d1">
        <div class="vital-icon" style="background:<?= $v['bg'] ?>;color:<?= $v['color'] ?>;">
          <i class="fa-solid <?= $v['icon'] ?>"></i>
        </div>
        <div class="vital-value"><?= htmlspecialchars($v['value']) ?></div>
        <div class="vital-label"><?= $v['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Records Section -->
    <div class="section fade-up d2">
      <div style="margin-bottom:20px;">
        <h3 style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px;">Visit Records</h3>
        <p style="font-size:12px;color:var(--muted);"><?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?> from your doctors</p>
      </div>

      <?php if (empty($records)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-file-circle-xmark"></i></div>
        <h4>No health records yet</h4>
        <p>Your doctors' visit records will appear here</p>
      </div>
      <?php else: ?>
      <div style="display:grid;gap:12px;">
        <?php foreach ($records as $i => $record): ?>
        <div class="record-item fade-up" onclick="openRecord(<?= $record['record_id'] ?>)" style="animation-delay:<?= ($i * 0.05) ?>s">
          <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;">
            <div style="flex:1;">
              <div style="font-weight:700;font-size:13px;color:var(--navy);margin-bottom:4px;">
                <?= htmlspecialchars($record['visit_type']) ?>
              </div>
              <div style="font-size:12px;color:var(--muted);margin-bottom:8px;">
                <i class="fa-regular fa-calendar" style="margin-right:4px;"></i><?= date('M d, Y', strtotime($record['record_date'])) ?>
                &nbsp;·&nbsp;
                <i class="fa-solid fa-user-doctor" style="margin-right:4px;"></i>Dr. <?= htmlspecialchars($record['doctor_name']) ?>
              </div>
              <?php if ($record['diagnosis']): ?>
              <div style="font-size:12px;color:var(--navy);font-style:italic;margin-top:6px;">
                <?= htmlspecialchars(substr($record['diagnosis'], 0, 100)) ?><?= strlen($record['diagnosis']) > 100 ? '...' : '' ?>
              </div>
              <?php endif; ?>
            </div>
            <span style="background:var(--teal-light);color:var(--teal);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap;">
              <?= htmlspecialchars($record['status']) ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>

  </div>
</main>

<!-- Record Detail Modal -->
<div class="modal" id="recordModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Record Details</h2>
      <button class="modal-close" onclick="closeRecordModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="modalBody"></div>
  </div>
</div>

<script>
  const RECORDS = <?= json_encode($recordsById ?? [], JSON_HEX_TAG | JSON_HEX_APOS) ?>;

  function openRecord(id) {
    const r = RECORDS[id];
    if (!r) return;

    const val = v => v || '<span style="color:#94a3b8">—</span>';

    let html = `
      <div class="detail-section">
        <h3 style="font-size:14px;font-weight:700;margin-bottom:12px;color:var(--navy);">
          ${r.visit_type}
        </h3>
        <p style="font-size:13px;color:var(--muted);margin-bottom:12px;">
          ${new Date(r.record_date).toLocaleDateString()}
          &nbsp;·&nbsp;
          Dr. ${r.doctor_name}
          &nbsp;·&nbsp; ${r.specialization}
        </p>
      </div>

      <div class="detail-section">
        <div class="detail-section-title">Clinical Details</div>
        <div class="detail-grid">
          ${r.chief_complaint ? `<div class="detail-item full"><div class="d-label">Chief Complaint</div><div class="d-value">${val(r.chief_complaint)}</div></div>` : ''}
          ${r.diagnosis ? `<div class="detail-item full"><div class="d-label">Diagnosis</div><div class="d-value">${val(r.diagnosis)}</div></div>` : ''}
          ${r.treatment ? `<div class="detail-item full"><div class="d-label">Treatment</div><div class="d-value">${val(r.treatment)}</div></div>` : ''}
          ${r.prescription ? `<div class="detail-item full"><div class="d-label">Prescription</div><div class="d-value">${val(r.prescription)}</div></div>` : ''}
        </div>
      </div>

      ${(r.blood_pressure || r.heart_rate || r.temperature || r.oxygen_saturation) ? `
      <div class="detail-section">
        <div class="detail-section-title">Vitals at Visit</div>
        <div class="detail-grid">
          ${r.blood_pressure ? `<div class="detail-item"><div class="d-label">Blood Pressure</div><div class="d-value">${r.blood_pressure} mmHg</div></div>` : ''}
          ${r.heart_rate ? `<div class="detail-item"><div class="d-label">Heart Rate</div><div class="d-value">${r.heart_rate} bpm</div></div>` : ''}
          ${r.temperature ? `<div class="detail-item"><div class="d-label">Temperature</div><div class="d-value">${r.temperature} °C</div></div>` : ''}
          ${r.oxygen_saturation ? `<div class="detail-item"><div class="d-label">Oxygen Sat.</div><div class="d-value">${r.oxygen_saturation}%</div></div>` : ''}
        </div>
      </div>` : ''}

      ${r.doctor_notes ? `
      <div class="detail-section">
        <div class="detail-section-title">Doctor Notes</div>
        <div class="detail-grid">
          <div class="detail-item full"><div class="d-value">${val(r.doctor_notes)}</div></div>
        </div>
      </div>` : ''}
    `;

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('recordModal').classList.add('open');
  }

  function closeRecordModal() {
    document.getElementById('recordModal').classList.remove('open');
  }

  function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
  }

  function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
</script>
</body>
</html>