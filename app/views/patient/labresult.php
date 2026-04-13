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
    ->select('u.username, u.role, p.first_name, p.last_name, CONCAT(p.first_name, \' \', p.last_name) AS full_name')
    ->inner_join('user_profiles p', 'u.user_id = p.user_id')
    ->where('u.user_id', $userId)
    ->get();
$first_name = htmlspecialchars($currentUser['first_name'] ?? '');
$last_name  = htmlspecialchars($currentUser['last_name'] ?? '');
$full_name  = htmlspecialchars($currentUser['full_name'] ?? '');
$role       = htmlspecialchars($currentUser['role'] ?? 'patient');

/* ═══════════════════════════════════════════════════════════
   GET — Fetch health records and metrics
════════════════════════════════════════════════════════════ */

/* ─── Fetch all health records (lab results) ─── */
$labRecords = $db->table('health_records hr')
    ->select('hr.record_id, hr.record_date, hr.visit_type, hr.chief_complaint, hr.diagnosis, hr.treatment, hr.prescription, hr.doctor_notes, hr.blood_pressure, hr.heart_rate, hr.temperature, hr.oxygen_saturation, hr.weight_kg, hr.height_cm, hr.bmi, hr.lab_results, hr.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name')
    ->inner_join('doctors d', 'hr.doctor_id = d.doctor_id', 'INNER ')
    ->inner_join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('hr.patient_id', $patientId)
    ->order_by('hr.record_date', 'DESC')
    ->get_all();

/* ─── Fetch latest health metrics (vital signs) ─── */
$latestMetrics = $db->table('health_metrics hm')
    ->select('hm.metric_id, hm.recorded_at, hm.recorded_by, hm.blood_pressure_sys, hm.blood_pressure_dia, hm.heart_rate, hm.temperature, hm.oxygen_saturation, hm.blood_sugar, hm.weight_kg, hm.height_cm, hm.bmi, hm.notes')
    ->where('hm.patient_id', $patientId)
    ->order_by('hm.recorded_at', 'DESC')
    ->limit(20)
    ->get_all();

/* ─── Summary counts ─── */
$totalRecords = count($labRecords);
$pendingCount = 0;
$completedCount = 0;
foreach ($labRecords as $record) {
    if ($record['status'] === 'Draft') $pendingCount++;
    else $completedCount++;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Lab Results</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
  <style>
    .lab-card {
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      padding: 16px;
      margin-bottom: 12px;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .lab-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      border-color: #d1d5db;
    }
    .lab-card-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 12px;
    }
    .lab-card-title {
      font-weight: 600;
      font-size: 15px;
      color: #1f2937;
    }
    .lab-card-date {
      font-size: 13px;
      color: #6b7280;
      margin-top: 4px;
    }
    .lab-card-doctor {
      font-size: 13px;
      color: #6b7280;
      margin-top: 2px;
    }
    .lab-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-draft {
      background: #fef3c7;
      color: #d97706;
    }
    .badge-final {
      background: #ccfbf1;
      color: #0d9488;
    }
    .badge-archived {
      background: #f1f5f9;
      color: #64748b;
    }
    .lab-values {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 12px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f3f4f6;
    }
    .lab-value-box {
      padding: 10px;
      background: #f9fafb;
      border-radius: 8px;
      text-align: center;
    }
    .lab-value-label {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 4px;
    }
    .lab-value-text {
      font-weight: 600;
      font-size: 14px;
      color: #1f2937;
    }
    .lab-detail-toggle {
      font-size: 13px;
      color: #3b82f6;
      cursor: pointer;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid #f3f4f6;
    }
    .lab-detail-toggle:hover {
      text-decoration: underline;
    }
    .lab-detail-content {
      display: none;
      margin-top: 12px;
      padding: 12px;
      background: #f9fafb;
      border-radius: 8px;
      font-size: 13px;
      color: #4b5563;
      line-height: 1.6;
    }
    .lab-detail-content.open {
      display: block;
    }
    .metric-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #e5e7eb;
    }
    .metric-item:last-child {
      border-bottom: none;
    }
    .metric-label {
      font-size: 13px;
      color: #6b7280;
      font-weight: 500;
    }
    .metric-value {
      font-weight: 600;
      color: #1f2937;
    }
    .filter-tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      border-bottom: 2px solid #e5e7eb;
      overflow-x: auto;
    }
    .filter-tab {
      padding: 12px 16px;
      border: none;
      background: none;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      color: #6b7280;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: all 0.3s ease;
    }
    .filter-tab.active {
      color: #0d9488;
      border-bottom-color: #0d9488;
    }
    .filter-tab:hover {
      color: #374151;
    }
  </style>
</head>
<body>

<!-- ── Sidebar ── -->
<?php include 'aside.php'; ?>

<!-- Mobile overlay -->
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ── Main ── -->
<main class="main">

  <!-- Topbar -->
  <?php include 'header.php'; ?>

  <!-- Content -->
  <div class="content">

    <!-- Stat Cards -->
    <div class="stats-grid">

      <div class="stat-card fade-up d1">
        <div class="stat-icon" style="background:#dbeafe;color:#3b82f6;">
          <i class="fa-solid fa-flask"></i>
        </div>
        <div>
          <div class="value"><?= $totalRecords ?></div>
          <div class="label">Total Records</div>
          <div class="trend up"><i class="fa-solid fa-file-medical"></i> All lab results</div>
        </div>
      </div>

      <div class="stat-card fade-up d2">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
          <i class="fa-solid fa-hourglass-end"></i>
        </div>
        <div>
          <div class="value"><?= $pendingCount ?></div>
          <div class="label">Pending Results</div>
          <div class="trend down"><i class="fa-solid fa-clock"></i> Awaiting review</div>
        </div>
      </div>

      <div class="stat-card fade-up d3">
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <div class="value"><?= $completedCount ?></div>
          <div class="label">Completed</div>
          <div class="trend up"><i class="fa-solid fa-check"></i> Ready to view</div>
        </div>
      </div>

      <div class="stat-card fade-up d4">
        <div class="stat-icon" style="background:#f3e8ff;color:#a855f7;">
          <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <div>
          <div class="value"><?= count($latestMetrics) ?></div>
          <div class="label">Latest Metrics</div>
          <div class="trend up"><i class="fa-solid fa-arrow-up"></i> Vitals tracked</div>
        </div>
      </div>

    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button class="filter-tab active" onclick="filterRecords('all')">
        <i class="fa-solid fa-list"></i> All Results
      </button>
      <button class="filter-tab" onclick="filterRecords('final')">
        <i class="fa-solid fa-circle-check"></i> Completed
      </button>
      <button class="filter-tab" onclick="filterRecords('draft')">
        <i class="fa-solid fa-hourglass-end"></i> Pending
      </button>
      <button class="filter-tab" onclick="filterRecords('vitals')">
        <i class="fa-solid fa-heart-pulse"></i> Vitals
      </button>
    </div>

    <!-- Lab Records Section -->
    <div class="panel fade-up d3">
      <div class="panel-header">
        <h3><i class="fa-solid fa-flask" style="color:#3b82f6;margin-right:8px;"></i>Lab Results & Health Records</h3>
        <span style="font-size:13px;color:#6b7280;"><?= $totalRecords ?> records</span>
      </div>

      <?php if (empty($labRecords)): ?>
        <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
          <i class="fa-solid fa-inbox" style="font-size:32px;margin-bottom:12px;opacity:0.5;"></i>
          <p>No lab results or health records available yet.</p>
          <p style="font-size:13px;margin-top:8px;">Once you have completed visits and lab work, they will appear here.</p>
        </div>
      <?php else: ?>
        <!-- Lab Records List -->
        <div id="records-container">
          <?php foreach ($labRecords as $record): 
            $statusClass = $record['status'] === 'Draft' ? 'badge-draft' : ($record['status'] === 'Archived' ? 'badge-archived' : 'badge-final');
            $recordDate = date('F j, Y', strtotime($record['record_date']));
            $recordTime = date('g:i A', strtotime($record['record_date']));
          ?>
          <div class="lab-card" data-status="<?= strtolower($record['status']) ?>" data-type="record">
            <div class="lab-card-header">
              <div>
                <div class="lab-card-title">
                  <i class="fa-solid fa-file-medical" style="margin-right:8px;color:#3b82f6;"></i>
                  <?= htmlspecialchars($record['visit_type']) ?>
                </div>
                <div class="lab-card-date"><?= $recordDate ?> · <?= $recordTime ?></div>
                <div class="lab-card-doctor"><i class="fa-solid fa-user-md" style="margin-right:4px;"></i><?= htmlspecialchars($record['doctor_name']) ?></div>
              </div>
              <span class="lab-badge <?= $statusClass ?>"><?= htmlspecialchars($record['status']) ?></span>
            </div>

            <!-- Vital Signs Display -->
            <?php if ($record['blood_pressure'] || $record['heart_rate'] || $record['temperature']): ?>
            <div class="lab-values">
              <?php if ($record['blood_pressure']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Blood Pressure</div>
                <div class="lab-value-text"><?= htmlspecialchars($record['blood_pressure']) ?></div>
              </div>
              <?php endif; ?>

              <?php if ($record['heart_rate']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Heart Rate</div>
                <div class="lab-value-text"><?= (int)$record['heart_rate'] ?> bpm</div>
              </div>
              <?php endif; ?>

              <?php if ($record['temperature']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Temperature</div>
                <div class="lab-value-text"><?= (float)$record['temperature'] ?>°C</div>
              </div>
              <?php endif; ?>

              <?php if ($record['oxygen_saturation']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Oxygen Saturation</div>
                <div class="lab-value-text"><?= (int)$record['oxygen_saturation'] ?>%</div>
              </div>
              <?php endif; ?>

              <?php if ($record['weight_kg']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Weight</div>
                <div class="lab-value-text"><?= (float)$record['weight_kg'] ?> kg</div>
              </div>
              <?php endif; ?>

              <?php if ($record['height_cm']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">Height</div>
                <div class="lab-value-text"><?= (float)$record['height_cm'] ?> cm</div>
              </div>
              <?php endif; ?>

              <?php if ($record['bmi']): ?>
              <div class="lab-value-box">
                <div class="lab-value-label">BMI</div>
                <div class="lab-value-text"><?= (float)$record['bmi'] ?></div>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- View Details Toggle -->
            <div class="lab-detail-toggle" onclick="toggleDetail(this)">
              <i class="fa-solid fa-chevron-down" style="margin-right:4px;"></i> View Clinical Details
            </div>

            <!-- Hidden Details -->
            <div class="lab-detail-content">
              <?php if ($record['chief_complaint']): ?>
              <div style="margin-bottom:12px;">
                <strong style="color:#1f2937;">Chief Complaint:</strong><br>
                <?= nl2br(htmlspecialchars($record['chief_complaint'])) ?>
              </div>
              <?php endif; ?>

              <?php if ($record['diagnosis']): ?>
              <div style="margin-bottom:12px;">
                <strong style="color:#1f2937;">Diagnosis:</strong><br>
                <?= nl2br(htmlspecialchars($record['diagnosis'])) ?>
              </div>
              <?php endif; ?>

              <?php if ($record['treatment']): ?>
              <div style="margin-bottom:12px;">
                <strong style="color:#1f2937;">Treatment:</strong><br>
                <?= nl2br(htmlspecialchars($record['treatment'])) ?>
              </div>
              <?php endif; ?>

              <?php if ($record['lab_results']): ?>
              <div style="margin-bottom:12px;">
                <strong style="color:#1f2937;">Lab Results:</strong><br>
                <?= nl2br(htmlspecialchars($record['lab_results'])) ?>
              </div>
              <?php endif; ?>

              <?php if ($record['doctor_notes']): ?>
              <div>
                <strong style="color:#1f2937;">Doctor's Notes:</strong><br>
                <?= nl2br(htmlspecialchars($record['doctor_notes'])) ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>

    <!-- Latest Vital Signs Section -->
    <?php if (!empty($latestMetrics)): ?>
    <div class="panel fade-up d4">
      <div class="panel-header">
        <h3><i class="fa-solid fa-heart-pulse" style="color:#ef4444;margin-right:8px;"></i>Latest Vital Signs</h3>
        <span style="font-size:13px;color:#6b7280;">Most recent entries</span>
      </div>

      <?php $latest = $latestMetrics[0]; ?>
      
      <div style="margin-bottom:20px;padding:12px;background:#f9fafb;border-radius:8px;font-size:13px;color:#6b7280;">
        <strong style="color:#1f2937;">Last recorded:</strong> 
        <?= date('F j, Y \a\t g:i A', strtotime($latest['recorded_at'])) ?>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <?php if ($latest['blood_pressure_sys'] && $latest['blood_pressure_dia']): ?>
        <div style="padding:16px;background:#fee2e2;border-radius:8px;border-left:4px solid #ef4444;">
          <div style="font-size:13px;color:#dc2626;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-heart-pulse"></i> Blood Pressure
          </div>
          <div style="font-size:24px;font-weight:700;color:#991b1b;">
            <?= (int)$latest['blood_pressure_sys'] ?> / <?= (int)$latest['blood_pressure_dia'] ?>
          </div>
          <div style="font-size:12px;color:#dc2626;margin-top:4px;">mmHg</div>
        </div>
        <?php endif; ?>

        <?php if ($latest['heart_rate']): ?>
        <div style="padding:16px;background:#fecaca;border-radius:8px;border-left:4px solid #fca5a5;">
          <div style="font-size:13px;color:#dc2626;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-pulse"></i> Heart Rate
          </div>
          <div style="font-size:24px;font-weight:700;color:#991b1b;">
            <?= (int)$latest['heart_rate'] ?>
          </div>
          <div style="font-size:12px;color:#dc2626;margin-top:4px;">bpm</div>
        </div>
        <?php endif; ?>

        <?php if ($latest['temperature']): ?>
        <div style="padding:16px;background:#fed7aa;border-radius:8px;border-left:4px solid #fb923c;">
          <div style="font-size:13px;color:#b45309;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-temperature-half"></i> Temperature
          </div>
          <div style="font-size:24px;font-weight:700;color:#7c2d12;">
            <?= (float)$latest['temperature'] ?>
          </div>
          <div style="font-size:12px;color:#b45309;margin-top:4px;">°C</div>
        </div>
        <?php endif; ?>

        <?php if ($latest['oxygen_saturation']): ?>
        <div style="padding:16px;background:#bfdbfe;border-radius:8px;border-left:4px solid #60a5fa;">
          <div style="font-size:13px;color:#1e40af;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-lungs"></i> Oxygen Saturation
          </div>
          <div style="font-size:24px;font-weight:700;color:#1e3a8a;">
            <?= (int)$latest['oxygen_saturation'] ?>
          </div>
          <div style="font-size:12px;color:#1e40af;margin-top:4px;">%</div>
        </div>
        <?php endif; ?>

        <?php if ($latest['blood_sugar']): ?>
        <div style="padding:16px;background:#a7f3d0;border-radius:8px;border-left:4px solid #34d399;">
          <div style="font-size:13px;color:#065f46;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-droplet"></i> Blood Sugar
          </div>
          <div style="font-size:24px;font-weight:700;color:#064e3b;">
            <?= (float)$latest['blood_sugar'] ?>
          </div>
          <div style="font-size:12px;color:#065f46;margin-top:4px;">mg/dL</div>
        </div>
        <?php endif; ?>

        <?php if ($latest['bmi']): ?>
        <div style="padding:16px;background:#e9d5ff;border-radius:8px;border-left:4px solid #d8b4fe;">
          <div style="font-size:13px;color:#6b21a8;font-weight:500;margin-bottom:8px;">
            <i class="fa-solid fa-weight-scale"></i> BMI
          </div>
          <div style="font-size:24px;font-weight:700;color:#581c87;">
            <?= (float)$latest['bmi'] ?>
          </div>
          <div style="font-size:12px;color:#6b21a8;margin-top:4px;">kg/m²</div>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($latest['notes']): ?>
      <div style="margin-top:16px;padding:12px;background:#f3f4f6;border-radius:8px;border-left:4px solid #6b7280;">
        <strong style="color:#1f2937;font-size:13px;">Notes:</strong><br>
        <span style="font-size:13px;color:#4b5563;display:block;margin-top:4px;">
          <?= nl2br(htmlspecialchars($latest['notes'])) ?>
        </span>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</main>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
  }
  
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
  }

  function toggleDetail(element) {
    const detailContent = element.nextElementSibling;
    detailContent.classList.toggle('open');
    element.querySelector('i').style.transform = detailContent.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
    element.querySelector('i').style.transition = 'transform 0.3s ease';
  }

  function filterRecords(status) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
    event.target.closest('.filter-tab').classList.add('active');

    // Filter records
    const container = document.getElementById('records-container');
    if (!container) return;

    const records = container.querySelectorAll('.lab-card');
    records.forEach(record => {
      if (status === 'all') {
        record.style.display = 'block';
      } else if (status === 'vitals') {
        record.style.display = record.getAttribute('data-type') === 'record' ? 'block' : 'none';
      } else {
        record.style.display = record.getAttribute('data-status') === status ? 'block' : 'none';
      }
    });
  }
</script>
</body>
</html>
