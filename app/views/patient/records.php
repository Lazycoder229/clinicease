<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . url('auth/login'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$db = new Database();

$patientRow = $db->table('patients')->where('user_id', $userId)->get();
if (!$patientRow) die('Patient record not found.');
$patientId = (int) $patientRow['patient_id'];

$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$fullName = htmlspecialchars($me['full_name'] ?? 'Patient');

$latestVitals = $db->table('health_metrics hm')
    ->where('hm.patient_id', $patientId)
    ->order_by('hm.recorded_at', 'DESC')
    ->limit(1)
    ->get();

$records = $db->table('health_records hr')
    ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.bmi, hr.doctor_notes, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('hr.patient_id', $patientId)
    ->order_by('hr.record_date', 'DESC')
    ->get_all();

$recordsById = [];
foreach ($records as $record) {
    $recordsById[$record['record_id']] = $record;
}

$typeMap = [
    'General Check-up' => ['icon' => 'fa-stethoscope',    'classes' => 'bg-teal-100 text-teal-600'],
    'Follow-up'        => ['icon' => 'fa-rotate-right',   'classes' => 'bg-sky-100 text-sky-500'],
    'Emergency'        => ['icon' => 'fa-truck-medical',  'classes' => 'bg-red-100 text-red-500'],
    'Vaccination'      => ['icon' => 'fa-syringe',        'classes' => 'bg-purple-100 text-purple-600'],
    'Laboratory'       => ['icon' => 'fa-flask',          'classes' => 'bg-emerald-100 text-emerald-600'],
    'Consultation'     => ['icon' => 'fa-comment-medical','classes' => 'bg-amber-100 text-amber-600'],
    'Procedure'        => ['icon' => 'fa-scalpel',        'classes' => 'bg-slate-100 text-slate-500'],
    'Dental'           => ['icon' => 'fa-tooth',          'classes' => 'bg-yellow-100 text-yellow-600'],
    'Eye Exam'         => ['icon' => 'fa-eye',            'classes' => 'bg-blue-100 text-blue-500'],
    'Other'            => ['icon' => 'fa-file-medical',   'classes' => 'bg-slate-100 text-slate-500'],
];

$statusMap = [
    'Final'    => 'bg-teal-100 text-teal-700',
    'Draft'    => 'bg-amber-100 text-amber-700',
    'Archived' => 'bg-slate-100 text-slate-600',
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Health Records — ClinicEase</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .main-content { min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
    @media (min-width: 1024px) { .main-content { margin-left: 16rem; } }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(10px) scale(.98); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-animate { animation: modalIn .18s ease; }
  </style>
</head>
<body class="bg-slate-50 h-full">

<?php include 'aside.php'; ?>
<div class="overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" id="overlay" onclick="closeSidebar()"></div>

<main class="main-content lg:ml-64">
  <?php include 'header.php'; ?>

  <div class="p-6 space-y-6">

    <!-- Badge -->
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold">
      <i class="fa-solid fa-file-medical"></i> Your Complete Medical History
    </div>

    <!-- Vitals Summary -->
    <?php if ($latestVitals):
      $vitals_list = [
        ['icon'=>'fa-heart-pulse',      'classes'=>'bg-red-100 text-red-500',     'label'=>'Blood Pressure', 'value'=>$latestVitals['blood_pressure_sys'].'/'.$latestVitals['blood_pressure_dia'].' mmHg'],
        ['icon'=>'fa-wave-square',      'classes'=>'bg-blue-100 text-blue-500',   'label'=>'Heart Rate',     'value'=>$latestVitals['heart_rate'].' bpm'],
        ['icon'=>'fa-droplet',          'classes'=>'bg-teal-100 text-teal-600',   'label'=>'Blood Sugar',    'value'=>$latestVitals['blood_sugar'].' mg/dL'],
        ['icon'=>'fa-lungs',            'classes'=>'bg-sky-100 text-sky-500',     'label'=>'Oxygen Sat.',    'value'=>$latestVitals['oxygen_saturation'].'%'],
        ['icon'=>'fa-temperature-half', 'classes'=>'bg-purple-100 text-purple-600','label'=>'Temperature',   'value'=>$latestVitals['temperature'].' °C'],
        ['icon'=>'fa-weight-scale',     'classes'=>'bg-amber-100 text-amber-600', 'label'=>'Weight',         'value'=>$latestVitals['weight_kg'].' kg'],
      ];
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">
      <?php foreach ($vitals_list as $v): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col gap-2 shadow-sm hover:shadow-md transition-all">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $v['classes'] ?>">
          <i class="fa-solid <?= $v['icon'] ?> text-sm"></i>
        </div>
        <div class="text-base font-bold text-slate-800"><?= htmlspecialchars($v['value']) ?></div>
        <div class="text-xs text-slate-500"><?= $v['label'] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Records Section -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-base font-bold text-slate-800">Visit Records</h3>
          <p class="text-xs text-slate-500 mt-0.5"><?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?> from your doctors</p>
        </div>
      </div>

      <?php if (empty($records)): ?>
        <div class="flex flex-col items-center justify-center py-16 text-slate-400">
          <i class="fa-solid fa-file-circle-xmark text-4xl mb-3"></i>
          <h4 class="text-sm font-bold text-slate-600 mb-1">No health records yet</h4>
          <p class="text-xs">Your doctors' visit records will appear here</p>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($records as $i => $record):
            $t = $typeMap[$record['visit_type']] ?? $typeMap['Other'];
            $sBadge = $statusMap[$record['status']] ?? 'bg-slate-100 text-slate-600';
          ?>
          <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-100 hover:bg-slate-50 hover:border-slate-200 transition-all cursor-pointer"
               onclick="openRecord(<?= $record['record_id'] ?>)">

            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $t['classes'] ?>">
              <i class="fa-solid <?= $t['icon'] ?> text-sm"></i>
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($record['visit_type']) ?></p>
              <p class="text-xs text-slate-500 mt-0.5">
                <i class="fa-regular fa-calendar mr-1"></i><?= date('M d, Y', strtotime($record['record_date'])) ?>
                &nbsp;·&nbsp;
                <i class="fa-solid fa-user-doctor mr-1"></i>Dr. <?= htmlspecialchars($record['doctor_name']) ?>
              </p>
              <?php if ($record['diagnosis']): ?>
              <p class="text-xs text-slate-600 italic mt-1">
                <?= htmlspecialchars(substr($record['diagnosis'], 0, 100)) ?><?= strlen($record['diagnosis']) > 100 ? '…' : '' ?>
              </p>
              <?php endif; ?>
            </div>

            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $sBadge ?> shrink-0">
              <?= htmlspecialchars($record['status']) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<!-- Record Detail Modal -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="recordModal">
  <div class="modal-animate bg-white w-full max-w-xl rounded-2xl shadow-2xl p-7 relative max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-5">
      <h2 class="text-lg font-bold text-slate-800">Record Details</h2>
      <button onclick="closeRecordModal()"
              class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div id="modalBody"></div>
  </div>
</div>

<script>
  const RECORDS = <?= json_encode($recordsById ?? [], JSON_HEX_TAG | JSON_HEX_APOS) ?>;

  function openRecord(id) {
    const r = RECORDS[id];
    if (!r) return;
    const val = v => v || '<span class="text-slate-300">—</span>';

    let sections = '';

    if (r.chief_complaint || r.diagnosis || r.treatment || r.prescription) {
      sections += `<div class="mb-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Clinical Details</p>
        <div class="space-y-2">`;
      if (r.chief_complaint) sections += detailItem('Chief Complaint', r.chief_complaint);
      if (r.diagnosis)       sections += detailItem('Diagnosis', r.diagnosis);
      if (r.treatment)       sections += detailItem('Treatment', r.treatment);
      if (r.prescription)    sections += detailItem('Prescription', r.prescription);
      sections += `</div></div>`;
    }

    if (r.blood_pressure || r.heart_rate || r.temperature || r.oxygen_saturation) {
      sections += `<div class="mb-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Vitals at Visit</p>
        <div class="grid grid-cols-2 gap-2">`;
      if (r.blood_pressure)     sections += vitalBox('Blood Pressure', r.blood_pressure + ' mmHg');
      if (r.heart_rate)         sections += vitalBox('Heart Rate', r.heart_rate + ' bpm');
      if (r.temperature)        sections += vitalBox('Temperature', r.temperature + ' °C');
      if (r.oxygen_saturation)  sections += vitalBox('Oxygen Sat.', r.oxygen_saturation + '%');
      sections += `</div></div>`;
    }

    if (r.doctor_notes) {
      sections += `<div class="mb-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Doctor Notes</p>
        ${detailItem('', r.doctor_notes)}
      </div>`;
    }

    document.getElementById('modalBody').innerHTML = `
      <div class="mb-4">
        <h3 class="text-base font-bold text-slate-800">${r.visit_type}</h3>
        <p class="text-xs text-slate-500 mt-1">
          ${new Date(r.record_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}
          &nbsp;·&nbsp; Dr. ${r.doctor_name}
          &nbsp;·&nbsp; ${r.specialization}
        </p>
      </div>
      <hr class="border-slate-100 mb-4">
      ${sections}
    `;

    const modal = document.getElementById('recordModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function detailItem(label, value) {
    return `<div class="p-3 bg-slate-50 rounded-xl">
      ${label ? `<p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">${label}</p>` : ''}
      <p class="text-sm text-slate-700">${value}</p>
    </div>`;
  }

  function vitalBox(label, value) {
    return `<div class="p-3 bg-slate-50 rounded-xl">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">${label}</p>
      <p class="text-sm font-bold text-slate-800">${value}</p>
    </div>`;
  }

  function closeRecordModal() {
    const modal = document.getElementById('recordModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  document.getElementById('recordModal').addEventListener('click', e => {
    if (e.target === document.getElementById('recordModal')) closeRecordModal();
  });

  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
  }
</script>
</body>
</html>