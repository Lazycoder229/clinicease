<?php
// Session already started in helpers.php
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}
$first_name = htmlspecialchars($_SESSION['first_name'] ?? '');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? '');
$role     = htmlspecialchars($_SESSION['role'] ?? 'patient');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
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
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;">
          <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
          <div class="value">3</div>
          <div class="label">Upcoming Appointments</div>
          <div class="trend up"><i class="fa-solid fa-arrow-up"></i> Next: Tomorrow</div>
        </div>
      </div>

      <div class="stat-card fade-up d2">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
          <i class="fa-solid fa-prescription-bottle-medical"></i>
        </div>
        <div>
          <div class="value">5</div>
          <div class="label">Active Prescriptions</div>
          <div class="trend up"><i class="fa-solid fa-check"></i> All refilled</div>
        </div>
      </div>

      <div class="stat-card fade-up d3">
        <div class="stat-icon" style="background:#dbeafe;color:#3b82f6;">
          <i class="fa-solid fa-flask"></i>
        </div>
        <div>
          <div class="value">2</div>
          <div class="label">Pending Lab Results</div>
          <div class="trend down"><i class="fa-solid fa-clock"></i> Awaiting review</div>
        </div>
      </div>

      <div class="stat-card fade-up d4">
        <div class="stat-icon" style="background:#f3e8ff;color:#a855f7;">
          <i class="fa-solid fa-message"></i>
        </div>
        <div>
          <div class="value">3</div>
          <div class="label">Unread Messages</div>
          <div class="trend up"><i class="fa-solid fa-envelope"></i> From your care team</div>
        </div>
      </div>

    </div>


    <!-- Appointments + Health Metrics -->
    <div class="row-2">

      <!-- Upcoming Appointments -->
      <div class="panel fade-up d3">
        <div class="panel-header">
          <h3><i class="fa-solid fa-calendar-check" style="color:var(--teal);margin-right:8px;"></i>Upcoming Appointments</h3>
          <a href="appointments.php">View all</a>
        </div>

        <div class="appt-item">
          <div class="appt-dot" style="background:#ccfbf1;color:#0d9488;">
            <i class="fa-solid fa-stethoscope"></i>
          </div>
          <div class="appt-info">
            <div class="appt-title">General Check-up</div>
            <div class="appt-sub"><i class="fa-regular fa-clock" style="margin-right:4px;"></i>Tomorrow · 9:00 AM · Dr. Santos</div>
          </div>
          <span class="appt-badge" style="background:#ccfbf1;color:#0d9488;">Confirmed</span>
        </div>

        <div class="appt-item">
          <div class="appt-dot" style="background:#fef3c7;color:#d97706;">
            <i class="fa-solid fa-tooth"></i>
          </div>
          <div class="appt-info">
            <div class="appt-title">Dental Cleaning</div>
            <div class="appt-sub"><i class="fa-regular fa-clock" style="margin-right:4px;"></i>Feb 25 · 2:00 PM · Dr. Reyes</div>
          </div>
          <span class="appt-badge" style="background:#fef3c7;color:#d97706;">Pending</span>
        </div>

        <div class="appt-item">
          <div class="appt-dot" style="background:#dbeafe;color:#3b82f6;">
            <i class="fa-solid fa-eye"></i>
          </div>
          <div class="appt-info">
            <div class="appt-title">Eye Examination</div>
            <div class="appt-sub"><i class="fa-regular fa-clock" style="margin-right:4px;"></i>Mar 3 · 10:30 AM · Dr. Lim</div>
          </div>
          <span class="appt-badge" style="background:#dbeafe;color:#3b82f6;">Scheduled</span>
        </div>
      </div>

      <!-- Health Metrics -->
      <div class="panel fade-up d4">
        <div class="panel-header">
          <h3><i class="fa-solid fa-heart-pulse" style="color:#ef4444;margin-right:8px;"></i>Health Metrics</h3>
          <a href="records.php">Details</a>
        </div>

        <div class="metric-row">
          <div class="metric-icon" style="background:#fee2e2;color:#ef4444;">
            <i class="fa-solid fa-heart-pulse"></i>
          </div>
          <div>
            <div class="m-label">Blood Pressure</div>
            <div class="m-value">120 / 80 mmHg</div>
          </div>
          <div class="m-bar-wrap">
            <div class="m-bar" style="width:60%;background:#ef4444;"></div>
          </div>
        </div>

        <div class="metric-row">
          <div class="metric-icon" style="background:#ccfbf1;color:#0d9488;">
            <i class="fa-solid fa-droplet"></i>
          </div>
          <div>
            <div class="m-label">Blood Sugar</div>
            <div class="m-value">98 mg/dL</div>
          </div>
          <div class="m-bar-wrap">
            <div class="m-bar" style="width:45%;background:#0d9488;"></div>
          </div>
        </div>

        <div class="metric-row">
          <div class="metric-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fa-solid fa-weight-scale"></i>
          </div>
          <div>
            <div class="m-label">BMI</div>
            <div class="m-value">22.4 (Normal)</div>
          </div>
          <div class="m-bar-wrap">
            <div class="m-bar" style="width:55%;background:#d97706;"></div>
          </div>
        </div>

        <div class="metric-row">
          <div class="metric-icon" style="background:#dbeafe;color:#3b82f6;">
            <i class="fa-solid fa-lungs"></i>
          </div>
          <div>
            <div class="m-label">Oxygen Saturation</div>
            <div class="m-value">98%</div>
          </div>
          <div class="m-bar-wrap">
            <div class="m-bar" style="width:98%;background:#3b82f6;"></div>
          </div>
        </div>

        <div class="metric-row">
          <div class="metric-icon" style="background:#f3e8ff;color:#a855f7;">
            <i class="fa-solid fa-temperature-half"></i>
          </div>
          <div>
            <div class="m-label">Temperature</div>
            <div class="m-value">36.6 °C</div>
          </div>
          <div class="m-bar-wrap">
            <div class="m-bar" style="width:50%;background:#a855f7;"></div>
          </div>
        </div>

      </div>
    </div>

    <!-- Recent Activity -->
    <div class="panel fade-up d5">
      <div class="panel-header">
        <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--teal);margin-right:8px;"></i>Recent Activity</h3>
        <a href="records.php">View all</a>
      </div>
      <div style="display:flex;flex-direction:column;gap:0;">

        <?php
        $activities = [
          ['icon'=>'fa-file-medical',        'color'=>'#0d9488','bg'=>'#ccfbf1', 'text'=>'Lab results uploaded',              'sub'=>'Feb 18, 2026 · Blood Panel'],
          ['icon'=>'fa-prescription',        'color'=>'#d97706','bg'=>'#fef3c7', 'text'=>'Prescription renewed',               'sub'=>'Feb 15, 2026 · Dr. Santos'],
          ['icon'=>'fa-calendar-xmark',      'color'=>'#ef4444','bg'=>'#fee2e2', 'text'=>'Appointment rescheduled',           'sub'=>'Feb 12, 2026 · Cardiology'],
          ['icon'=>'fa-comment-medical',     'color'=>'#3b82f6','bg'=>'#dbeafe', 'text'=>'Message from Dr. Reyes',            'sub'=>'Feb 10, 2026 · Follow-up note'],
          ['icon'=>'fa-syringe',             'color'=>'#a855f7','bg'=>'#f3e8ff', 'text'=>'Vaccination recorded',              'sub'=>'Feb 5, 2026 · Flu Shot'],
        ];
        foreach ($activities as $a):
        ?>
        <div class="appt-item">
          <div class="appt-dot" style="background:<?= $a['bg'] ?>;color:<?= $a['color'] ?>;">
            <i class="fa-solid <?= $a['icon'] ?>"></i>
          </div>
          <div class="appt-info">
            <div class="appt-title"><?= $a['text'] ?></div>
            <div class="appt-sub"><?= $a['sub'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

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
</script>
</body>
</html>