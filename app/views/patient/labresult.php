<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$db = new Database();

$patientRow = $db->table('patients')->where('user_id', $userId)->get();
if (!$patientRow) die('Patient record not found.');
$patientId = (int) $patientRow['patient_id'];

$currentUser = $db->table('users u')
    ->select('u.username, u.role, p.first_name, p.last_name, CONCAT(p.first_name, \' \', p.last_name) AS full_name')
    ->inner_join('user_profiles p', 'u.user_id = p.user_id')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($currentUser['full_name'] ?? '');

$labRecords = $db->table('health_records hr')
    ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.doctor_notes, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.height_cm, hr.bmi, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name')
    ->inner_join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
    ->inner_join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('hr.patient_id', $patientId)
    ->order_by('hr.record_date', 'DESC')
    ->get_all();

$latestMetrics = $db->table('health_metrics hm')
    ->select('hm.metric_id, hm.recorded_at, hm.recorded_by, hm.blood_pressure_sys, hm.blood_pressure_dia, hm.heart_rate, hm.temperature, hm.oxygen_saturation, hm.blood_sugar, hm.weight_kg, hm.height_cm, hm.bmi, hm.notes')
    ->where('hm.patient_id', $patientId)
    ->order_by('hm.recorded_at', 'DESC')
    ->limit(20)
    ->get_all();

$totalRecords   = count($labRecords);
$pendingCount   = 0;
$completedCount = 0;
foreach ($labRecords as $r) {
    if ($r['status'] === 'Draft') $pendingCount++;
    else $completedCount++;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Lab Results</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .main-content { min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
    @media (min-width: 1024px) { .main-content { margin-left: 16rem; } }
    .detail-content { display: none; }
    .detail-content.open { display: block; }
    .chevron-icon { transition: transform .3s ease; }
    .filter-tab.active { color: #0d9488; border-bottom-color: #0d9488; }
  </style>
</head>
<body class="bg-slate-50 h-full">

<?php include 'aside.php'; ?>
<div class="overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" id="overlay" onclick="closeSidebar()"></div>

<main class="main-content lg:ml-64">
  <?php include 'header.php'; ?>

  <div class="p-6 space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-flask"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= $totalRecords ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Records</div>
          <div class="text-[11px] font-bold mt-1.5 text-blue-600 flex items-center gap-1">
            <i class="fa-solid fa-file-medical"></i> All lab results
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-hourglass-end"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= $pendingCount ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending</div>
          <div class="text-[11px] font-bold mt-1.5 text-amber-600 flex items-center gap-1">
            <i class="fa-solid fa-clock"></i> Awaiting review
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= $completedCount ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Completed</div>
          <div class="text-[11px] font-bold mt-1.5 text-teal-600 flex items-center gap-1">
            <i class="fa-solid fa-check"></i> Ready to view
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= count($latestMetrics) ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Latest Metrics</div>
          <div class="text-[11px] font-bold mt-1.5 text-purple-600 flex items-center gap-1">
            <i class="fa-solid fa-arrow-up"></i> Vitals tracked
          </div>
        </div>
      </div>

    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-0 border-b-2 border-slate-200 overflow-x-auto">
      <button class="filter-tab px-4 py-3 border-b-2 border-transparent text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors active"
              onclick="filterRecords('all', this)">
        <i class="fa-solid fa-list mr-1.5"></i> All Results
      </button>
      <button class="filter-tab px-4 py-3 border-b-2 border-transparent text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors"
              onclick="filterRecords('final', this)">
        <i class="fa-solid fa-circle-check mr-1.5"></i> Completed
      </button>
      <button class="filter-tab px-4 py-3 border-b-2 border-transparent text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors"
              onclick="filterRecords('draft', this)">
        <i class="fa-solid fa-hourglass-end mr-1.5"></i> Pending
      </button>
    </div>

    <!-- Lab Records -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-flask text-blue-500"></i> Lab Results & Health Records
        </h3>
        <span class="text-xs text-slate-500"><?= $totalRecords ?> records</span>
      </div>

      <?php if (empty($labRecords)): ?>
        <div class="flex flex-col items-center justify-center py-16 text-slate-400">
          <i class="fa-solid fa-inbox text-4xl mb-3 opacity-50"></i>
          <p class="text-sm">No lab results or health records available yet.</p>
          <p class="text-xs mt-1 text-slate-400">Once you have completed visits and lab work, they will appear here.</p>
        </div>
      <?php else: ?>
        <div id="records-container" class="space-y-3">
          <?php foreach ($labRecords as $record):
            $statusBadge = match($record['status']) {
              'Draft'    => 'bg-amber-100 text-amber-700',
              'Archived' => 'bg-slate-100 text-slate-600',
              default    => 'bg-teal-100 text-teal-700',
            };
          ?>
          <div class="lab-card border border-slate-100 rounded-xl p-4 hover:border-slate-200 hover:bg-slate-50 transition-all"
               data-status="<?= strtolower($record['status']) ?>">

            <!-- Header -->
            <div class="flex items-start justify-between gap-3 mb-3">
              <div>
                <p class="text-sm font-bold text-slate-800">
                  <i class="fa-solid fa-file-medical text-blue-500 mr-1.5"></i>
                  <?= htmlspecialchars($record['visit_type']) ?>
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  <?= date('F j, Y', strtotime($record['record_date'])) ?>
                </p>
                <p class="text-xs text-slate-500">
                  <i class="fa-solid fa-user-md mr-1"></i><?= htmlspecialchars($record['doctor_name']) ?>
                </p>
              </div>
              <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide shrink-0 <?= $statusBadge ?>">
                <?= htmlspecialchars($record['status']) ?>
              </span>
            </div>

            <!-- Vitals Grid -->
            <?php if ($record['blood_pressure'] || $record['heart_rate'] || $record['temperature'] || $record['oxygen_saturation'] || $record['weight_kg'] || $record['height_cm'] || $record['bmi']): ?>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 pt-3 border-t border-slate-100">
              <?php if ($record['blood_pressure']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">Blood Pressure</div>
                <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($record['blood_pressure']) ?></div>
              </div>
              <?php endif; ?>
              <?php if ($record['heart_rate']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">Heart Rate</div>
                <div class="text-sm font-semibold text-slate-800"><?= (int)$record['heart_rate'] ?> bpm</div>
              </div>
              <?php endif; ?>
              <?php if ($record['temperature']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">Temperature</div>
                <div class="text-sm font-semibold text-slate-800"><?= (float)$record['temperature'] ?>°C</div>
              </div>
              <?php endif; ?>
              <?php if ($record['oxygen_saturation']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">O₂ Sat.</div>
                <div class="text-sm font-semibold text-slate-800"><?= (int)$record['oxygen_saturation'] ?>%</div>
              </div>
              <?php endif; ?>
              <?php if ($record['weight_kg']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">Weight</div>
                <div class="text-sm font-semibold text-slate-800"><?= (float)$record['weight_kg'] ?> kg</div>
              </div>
              <?php endif; ?>
              <?php if ($record['height_cm']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">Height</div>
                <div class="text-sm font-semibold text-slate-800"><?= (float)$record['height_cm'] ?> cm</div>
              </div>
              <?php endif; ?>
              <?php if ($record['bmi']): ?>
              <div class="p-2 bg-slate-50 rounded-lg text-center">
                <div class="text-xs text-slate-400 mb-0.5">BMI</div>
                <div class="text-sm font-semibold text-slate-800"><?= (float)$record['bmi'] ?></div>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Toggle Details -->
            <button class="flex items-center gap-1.5 text-xs text-blue-500 hover:text-blue-700 font-medium mt-3 pt-3 border-t border-slate-100 w-full transition-colors"
                    onclick="toggleDetail(this)">
              <i class="fa-solid fa-chevron-down chevron-icon text-xs"></i> View Clinical Details
            </button>

            <div class="detail-content mt-3 p-3 bg-slate-50 rounded-xl text-sm text-slate-600 leading-relaxed space-y-3">
              <?php if ($record['chief_complaint']): ?>
              <div><strong class="text-slate-800">Chief Complaint:</strong><br><?= nl2br(htmlspecialchars($record['chief_complaint'])) ?></div>
              <?php endif; ?>
              <?php if ($record['diagnosis']): ?>
              <div><strong class="text-slate-800">Diagnosis:</strong><br><?= nl2br(htmlspecialchars($record['diagnosis'])) ?></div>
              <?php endif; ?>
              <?php if ($record['treatment']): ?>
              <div><strong class="text-slate-800">Treatment:</strong><br><?= nl2br(htmlspecialchars($record['treatment'])) ?></div>
              <?php endif; ?>
              <?php if ($record['lab_results']): ?>
              <div><strong class="text-slate-800">Lab Results:</strong><br><?= nl2br(htmlspecialchars($record['lab_results'])) ?></div>
              <?php endif; ?>
              <?php if ($record['doctor_notes']): ?>
              <div><strong class="text-slate-800">Doctor's Notes:</strong><br><?= nl2br(htmlspecialchars($record['doctor_notes'])) ?></div>
              <?php endif; ?>
            </div>

          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Latest Vital Signs -->
    <?php if (!empty($latestMetrics)):
      $latest = $latestMetrics[0];
      $vitalCards = [];
      if ($latest['blood_pressure_sys'] && $latest['blood_pressure_dia'])
        $vitalCards[] = ['label'=>'Blood Pressure','value'=>(int)$latest['blood_pressure_sys'].'/'.(int)$latest['blood_pressure_dia'],'unit'=>'mmHg','classes'=>'bg-red-50 border-red-300 text-red-800'];
      if ($latest['heart_rate'])
        $vitalCards[] = ['label'=>'Heart Rate','value'=>(int)$latest['heart_rate'],'unit'=>'bpm','classes'=>'bg-red-50 border-red-200 text-red-700'];
      if ($latest['temperature'])
        $vitalCards[] = ['label'=>'Temperature','value'=>(float)$latest['temperature'],'unit'=>'°C','classes'=>'bg-orange-50 border-orange-200 text-orange-700'];
      if ($latest['oxygen_saturation'])
        $vitalCards[] = ['label'=>'Oxygen Sat.','value'=>(int)$latest['oxygen_saturation'],'unit'=>'%','classes'=>'bg-blue-50 border-blue-200 text-blue-800'];
      if ($latest['blood_sugar'])
        $vitalCards[] = ['label'=>'Blood Sugar','value'=>(float)$latest['blood_sugar'],'unit'=>'mg/dL','classes'=>'bg-emerald-50 border-emerald-200 text-emerald-800'];
      if ($latest['bmi'])
        $vitalCards[] = ['label'=>'BMI','value'=>(float)$latest['bmi'],'unit'=>'kg/m²','classes'=>'bg-purple-50 border-purple-200 text-purple-800'];
    ?>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-heart-pulse text-red-500"></i> Latest Vital Signs
        </h3>
        <span class="text-xs text-slate-500">Most recent entries</span>
      </div>

      <p class="text-xs text-slate-500 mb-4 px-3 py-2 bg-slate-50 rounded-lg">
        <strong class="text-slate-700">Last recorded:</strong>
        <?= date('F j, Y \a\t g:i A', strtotime($latest['recorded_at'])) ?>
      </p>

      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <?php foreach ($vitalCards as $vc): ?>
        <div class="p-4 rounded-xl border-l-4 <?= $vc['classes'] ?>">
          <p class="text-xs font-medium mb-2 opacity-80"><?= $vc['label'] ?></p>
          <p class="text-2xl font-bold"><?= $vc['value'] ?></p>
          <p class="text-xs mt-1 opacity-70"><?= $vc['unit'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($latest['notes']): ?>
      <div class="mt-4 p-3 bg-slate-50 rounded-xl border-l-4 border-slate-400 text-sm text-slate-600">
        <strong class="text-slate-800">Notes:</strong><br>
        <?= nl2br(htmlspecialchars($latest['notes'])) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</main>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
  }

  function toggleDetail(btn) {
    const content = btn.nextElementSibling;
    const icon = btn.querySelector('.chevron-icon');
    content.classList.toggle('open');
    icon.style.transform = content.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0)';
  }

  function filterRecords(status, btn) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.lab-card').forEach(card => {
      card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
  }
</script>
</body>
</html>