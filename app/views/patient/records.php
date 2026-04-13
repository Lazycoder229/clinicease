<?php
// Session already started in helpers.php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* ─── Database connection ─── */
$db = new Database();

/* ─── Resolve patient_id ─── */
$patientRow = $db->table('patients')->where('user_id', $userId)->get();
if (!$patientRow) die('Patient record not found.');
$patientId = (int) $patientRow['patient_id'];

/* ─── Current user display info ─── */
$currentUser = $db->table('users u')
    ->select('u.username, u.role, CONCAT(p.first_name, \' \', p.last_name) AS full_name')
    ->inner_join('user_profiles p', 'u.user_id = p.user_id')
    ->where('u.user_id', $userId)
    ->get();
$fullName = htmlspecialchars($currentUser['full_name'] ?? '');
$role     = htmlspecialchars($currentUser['role'] ?? 'patient');

/* ─── Latest vitals ─── */
$latestVitals = $db->table('health_metrics hm')
    ->where('hm.patient_id', $patientId)
    ->order_by('hm.recorded_at', 'DESC')
    ->limit(1)
    ->get();

/* ─── Vitals trend (last 6 months) ─── */
$vitalsHistory = $db->table('health_metrics')
    ->select('DATE(recorded_at) AS date, blood_pressure_sys, blood_pressure_dia, heart_rate, blood_sugar, weight_kg, bmi')
    ->where('patient_id', $patientId)
    ->where('recorded_at', '>=', 'DATE_SUB(NOW(), INTERVAL 6 MONTH)')
    ->order_by('recorded_at', 'ASC')
    ->get_all();

/* ─── Health records list ─── */
$records = $db->table('health_records hr')
    ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.bmi, hr.doctor_notes, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('hr.patient_id', $patientId)
    ->order_by('hr.record_date', 'DESC')
    ->get_all();

/* ─── View single record (for modal) ─── */
$viewRecord = null;
if (!empty($_GET['view'])) {
    $viewRecord = $db->table('health_records hr')
        ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.bmi, hr.doctor_notes, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
        ->join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
        ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
        ->where('hr.record_id', (int)$_GET['view'])
        ->where('hr.patient_id', $patientId)
        ->get();
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
  <title>ClinicEase — Health Records</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link rel="stylesheet" href="<?= url('public/css/record.css') ?>">
</head>
<body>
<!-- ── Sidebar ── -->
<?php include 'aside.php'; ?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ── Main ── -->
<main class="main">

  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="icon-btn hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div class="topbar-left">
        <h2>Health Records</h2>
        <p>Your complete medical history and vitals</p>
      </div>
    </div>
   
  </div>

  <div class="content">

    <!-- ── Vitals Summary Cards ── -->
    <div class="vitals-grid fade-up d1">

      <?php
      $bp  = $latestVitals ? ($latestVitals['blood_pressure_sys'] . '/' . $latestVitals['blood_pressure_dia'] . ' mmHg') : '—';
      $hr  = $latestVitals ? ($latestVitals['heart_rate'] . ' bpm') : '—';
      $sg  = $latestVitals ? ($latestVitals['blood_sugar'] . ' mg/dL') : '—';
      $spo = $latestVitals ? ($latestVitals['oxygen_saturation'] . '%') : '—';
      $tmp = $latestVitals ? ($latestVitals['temperature'] . ' °C') : '—';
      $bmi = $latestVitals && $latestVitals['bmi']
               ? $latestVitals['bmi'] . ' (' . bmiLabel((float)$latestVitals['bmi']) . ')'
               : '—';
      $bmiC = $latestVitals && $latestVitals['bmi'] ? bmiColor((float)$latestVitals['bmi']) : '#64748b';

      $vitCards = [
        ['icon'=>'fa-heart-pulse',      'color'=>'#ef4444','bg'=>'#fee2e2', 'label'=>'Blood Pressure',     'value'=>$bp,  'note'=>'Systolic / Diastolic'],
        ['icon'=>'fa-droplet',          'color'=>'#0d9488','bg'=>'#ccfbf1', 'label'=>'Blood Sugar',         'value'=>$sg,  'note'=>'Fasting glucose'],
        ['icon'=>'fa-wave-square',      'color'=>'#3b82f6','bg'=>'#dbeafe', 'label'=>'Heart Rate',          'value'=>$hr,  'note'=>'Beats per minute'],
        ['icon'=>'fa-lungs',            'color'=>'#0ea5e9','bg'=>'#e0f2fe', 'label'=>'Oxygen Saturation',   'value'=>$spo, 'note'=>'SpO₂'],
        ['icon'=>'fa-temperature-half', 'color'=>'#a855f7','bg'=>'#f3e8ff', 'label'=>'Temperature',         'value'=>$tmp, 'note'=>'Body temp'],
        ['icon'=>'fa-weight-scale',     'color'=>$bmiC,    'bg'=>'#f1f5f9', 'label'=>'BMI',                 'value'=>$bmi, 'note'=>'Body Mass Index'],
      ];
      foreach ($vitCards as $v): ?>
      <div class="vital-card">
        <div class="vital-icon" style="background:<?= $v['bg'] ?>;color:<?= $v['color'] ?>;">
          <i class="fa-solid <?= $v['icon'] ?>"></i>
        </div>
        <div>
          <div class="vital-value"><?= htmlspecialchars($v['value']) ?></div>
          <div class="vital-label"><?= $v['label'] ?></div>
          <div class="vital-note" style="color:<?= $v['color'] ?>"><?= $v['note'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <!-- ── Charts ── -->
    <?php if (!empty($vitalsHistory)): ?>
    <div class="row-2 fade-up d2">

      <!-- Blood Pressure Chart -->
      <div class="panel">
        <div class="panel-header">
          <h3><i class="fa-solid fa-heart-pulse" style="color:#ef4444;margin-right:8px;"></i>Blood Pressure Trend</h3>
        </div>
        <div class="chart-wrap"><canvas id="bpChart" height="180"></canvas></div>
      </div>

      <!-- Blood Sugar + Weight Chart -->
      <div class="panel">
        <div class="panel-header">
          <h3><i class="fa-solid fa-droplet" style="color:#0d9488;margin-right:8px;"></i>Blood Sugar Trend</h3>
        </div>
        <div class="chart-wrap"><canvas id="sgChart" height="180"></canvas></div>
      </div>

    </div>
    <?php endif; ?>

    <!-- ── Records List ── -->
    <div class="records-panel fade-up d3">
      <div class="records-panel-header">
        <h3><i class="fa-solid fa-folder-open" style="color:var(--teal);margin-right:8px;"></i>
          Visit Records
          <span style="font-size:12px;font-weight:500;color:var(--muted);margin-left:6px;"><?= count($records) ?> total</span>
        </h3>
        <div class="filter-tabs">
          <button class="filter-tab active" onclick="filterRecords('all',this)">All</button>
          <button class="filter-tab" onclick="filterRecords('general check-up',this)">Check-ups</button>
          <button class="filter-tab" onclick="filterRecords('laboratory',this)">Lab</button>
          <button class="filter-tab" onclick="filterRecords('vaccination',this)">Vaccines</button>
        </div>
      </div>

      <?php if (empty($records)): ?>
        <div class="empty-state">
          <i class="fa-regular fa-folder-open"></i>
          <p>No health records found.</p>
        </div>
      <?php else: ?>
        <?php foreach ($records as $i => $rec):
          $t = $typeMap[$rec['visit_type']] ?? $typeMap['Other'];
          $s = $statusColors[$rec['status']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9'];
        ?>
        <div class="record-item"
             data-type="<?= strtolower(htmlspecialchars($rec['visit_type'])) ?>"
             onclick="openRecord(<?= $rec['record_id'] ?>)"
             style="animation: fadein .4s ease <?= $i * 0.05 ?>s both;">
          <div class="record-dot" style="background:<?= $t['bg'] ?>;color:<?= $t['color'] ?>;">
            <i class="fa-solid <?= $t['icon'] ?>"></i>
          </div>
          <div class="record-info">
            <div class="record-title"><?= htmlspecialchars($rec['visit_type']) ?></div>
            <div class="record-sub">
              <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
              <?= date('M j, Y', strtotime($rec['record_date'])) ?>
              &nbsp;·&nbsp;
              <i class="fa-solid fa-user-doctor" style="margin-right:4px;"></i>
              Dr. <?= htmlspecialchars($rec['doctor_name']) ?>
              &nbsp;·&nbsp; <?= htmlspecialchars($rec['specialization']) ?>
            </div>
            <?php if ($rec['diagnosis']): ?>
            <div class="record-diag">"<?= htmlspecialchars($rec['diagnosis']) ?>"</div>
            <?php endif; ?>
          </div>
          <span class="record-badge" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;">
            <?= htmlspecialchars($rec['status']) ?>
          </span>
          <button class="view-btn" title="View details" onclick="event.stopPropagation(); openRecord(<?= $rec['record_id'] ?>)">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- /content -->
</main>

<!-- ═══════════════════════════════════════
     RECORD DETAIL MODAL
═══════════════════════════════════════ -->
<div class="modal-overlay" id="recordModal">
  <div class="modal-box" id="modalContent">
    <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
    <div id="modalBody">
      <!-- Injected via JS / PHP partial below -->
    </div>
  </div>
</div>

<?php
/* ── Pre-render all records as hidden data for JS modal ── */
$recordsById = [];
foreach ($records as $r) {
    $recordsById[$r['record_id']] = $r;
}
?>
<script>
/* ─── Sidebar ─── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}

/* ─── Records data from PHP ─── */
const RECORDS = <?= json_encode($recordsById, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

const TYPE_MAP = <?= json_encode($typeMap, JSON_HEX_TAG) ?>;
const STATUS_COLORS = <?= json_encode($statusColors, JSON_HEX_TAG) ?>;

function bmiLabel(bmi) {
  if (bmi < 18.5) return 'Underweight';
  if (bmi < 25)   return 'Normal';
  if (bmi < 30)   return 'Overweight';
  return 'Obese';
}

function openRecord(id) {
  const r = RECORDS[id];
  if (!r) return;

  const t = TYPE_MAP[r.visit_type]    || { icon:'fa-file-medical', color:'#64748b', bg:'#f1f5f9' };
  const s = STATUS_COLORS[r.status]   || { color:'#64748b', bg:'#f1f5f9' };

  const fmtDate = d => d ? new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' }) : '—';
  const val     = v => v || '<span style="color:#94a3b8">—</span>';

  const vitals = [
    { icon:'fa-heart-pulse',      color:'#ef4444', label:'Blood Pressure', value: r.blood_pressure ? r.blood_pressure + ' mmHg' : null },
    { icon:'fa-wave-square',      color:'#3b82f6', label:'Heart Rate',     value: r.heart_rate ? r.heart_rate + ' bpm' : null },
    { icon:'fa-temperature-half', color:'#a855f7', label:'Temperature',    value: r.temperature ? r.temperature + ' °C' : null },
    { icon:'fa-lungs',            color:'#0ea5e9', label:'SpO₂',           value: r.oxygen_saturation ? r.oxygen_saturation + '%' : null },
    { icon:'fa-weight-scale',     color:'#d97706', label:'Weight',         value: r.weight_kg ? r.weight_kg + ' kg' : null },
    { icon:'fa-ruler-vertical',   color:'#10b981', label:'BMI',            value: r.bmi ? r.bmi + ' (' + bmiLabel(parseFloat(r.bmi)) + ')' : null },
  ].filter(v => v.value);

  const vitPills = vitals.map(v => `
    <div class="vital-pill">
      <i class="fa-solid ${v.icon}" style="color:${v.color}"></i>
      <div>
        <div class="vp-label">${v.label}</div>
        <div class="vp-value">${v.value}</div>
      </div>
    </div>`).join('');

  document.getElementById('modalBody').innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
      <div style="width:46px;height:46px;border-radius:13px;background:${t.bg};color:${t.color};display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
        <i class="fa-solid ${t.icon}"></i>
      </div>
      <div>
        <div class="modal-title" style="margin-bottom:2px;">${r.visit_type}</div>
        <div class="modal-subtitle" style="margin-bottom:0;">
          ${fmtDate(r.record_date)} &nbsp;·&nbsp;
          Dr. ${r.doctor_name} &nbsp;·&nbsp; ${r.specialization}
          &nbsp;&nbsp;<span style="background:${s.bg};color:${s.color};font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">${r.status}</span>
        </div>
      </div>
    </div>
    <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">

    ${vitals.length ? `
    <div class="detail-section">
      <div class="detail-section-title">Vitals at Visit</div>
      <div class="vitals-row">${vitPills}</div>
    </div>` : ''}

    <div class="detail-section">
      <div class="detail-section-title">Clinical Details</div>
      <div class="detail-grid">
        <div class="detail-item full">
          <div class="d-label">Chief Complaint</div>
          <div class="d-value">${val(r.chief_complaint)}</div>
        </div>
        <div class="detail-item full">
          <div class="d-label">Diagnosis</div>
          <div class="d-value">${val(r.diagnosis)}</div>
        </div>
        <div class="detail-item full">
          <div class="d-label">Treatment</div>
          <div class="d-value">${val(r.treatment)}</div>
        </div>
        <div class="detail-item full">
          <div class="d-label">Prescription</div>
          <div class="d-value">${val(r.prescription)}</div>
        </div>
      </div>
    </div>

    ${(r.doctor_notes || r.lab_results) ? `
    <div class="detail-section">
      <div class="detail-section-title">Additional Notes</div>
      <div class="detail-grid">
        ${r.doctor_notes ? `<div class="detail-item full"><div class="d-label">Doctor Notes</div><div class="d-value">${r.doctor_notes}</div></div>` : ''}
        ${r.lab_results  ? `<div class="detail-item full"><div class="d-label">Lab Results</div><div class="d-value">${r.lab_results}</div></div>` : ''}
      </div>
    </div>` : ''}
  `;

  document.getElementById('recordModal').classList.add('open');
}

function closeModal() {
  document.getElementById('recordModal').classList.remove('open');
}
document.getElementById('recordModal').addEventListener('click', e => {
  if (e.target === document.getElementById('recordModal')) closeModal();
});

/* ─── Filter ─── */
function filterRecords(type, btn) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.record-item').forEach(item => {
    item.style.display = (type === 'all' || item.dataset.type === type) ? 'flex' : 'none';
  });
}

/* ─── Charts ─── */
<?php if (!empty($vitalsHistory)): ?>
const labels = <?= json_encode(array_column($vitalsHistory, 'date')) ?>;
const sysList = <?= json_encode(array_column($vitalsHistory, 'blood_pressure_sys')) ?>;
const diaList = <?= json_encode(array_column($vitalsHistory, 'blood_pressure_dia')) ?>;
const sgList  = <?= json_encode(array_column($vitalsHistory, 'blood_sugar')) ?>;

const chartDefaults = {
  responsive: true,
  plugins: { legend: { position: 'bottom', labels: { font: { family: 'DM Sans', size: 12 }, boxWidth: 12 } } },
  scales: {
    x: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Sans', size: 11 } } },
    y: { grid: { color: '#f1f5f9' }, ticks: { font: { family: 'DM Sans', size: 11 } } }
  }
};

new Chart(document.getElementById('bpChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Systolic',  data: sysList, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)',  tension: .4, fill: true, pointRadius: 4 },
      { label: 'Diastolic', data: diaList, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', tension: .4, fill: true, pointRadius: 4 },
    ]
  },
  options: chartDefaults
});

new Chart(document.getElementById('sgChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Blood Sugar (mg/dL)', data: sgList, borderColor: '#0d9488', backgroundColor: 'rgba(13,148,136,.08)', tension: .4, fill: true, pointRadius: 4 },
    ]
  },
  options: chartDefaults
});
<?php endif; ?>
</script>
</body>
</html>