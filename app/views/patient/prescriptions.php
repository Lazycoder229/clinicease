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
$full_name = htmlspecialchars($currentUser['full_name'] ?? '');
$role      = htmlspecialchars($currentUser['role'] ?? 'patient');

/* ═══════════════════════════════════════════════════════════
   POST HANDLER — PRG pattern (all actions redirect after)
════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    /* ── Request Refill ── */
    if ($action === 'refill' && isset($_POST['prescription_id'])) {
        $rxId = (int) $_POST['prescription_id'];
        try {
            /* Verify prescription exists and has refills available */
            $rx = $db->table('prescriptions')
                ->where('prescription_id', $rxId)
                ->where('patient_id', $patientId)
                ->where('status', 'Active')
                ->get();

            if ($rx && (int)$rx['refills_used'] < (int)$rx['refills_allowed']) {
                /* Increment refills_used */
                $result = $db->table('prescriptions')
                    ->where('prescription_id', $rxId)
                    ->update(['refills_used' => ((int)$rx['refills_used'] + 1)]);

                $_SESSION['rx_success'] = 'Refill requested successfully.';
            } else {
                $_SESSION['rx_error'] = 'Refill not available — limit reached or prescription inactive.';
            }
        } catch (Exception $e) {
            error_log('Refill error: ' . $e->getMessage());
            $_SESSION['rx_error'] = 'Refill request failed. Please try again.';
        }
        header('Location: ' . url('patient/prescriptions'));
        exit;
    }

    /* ── Discontinue (patient requests stop) ── */
    if ($action === 'discontinue' && isset($_POST['prescription_id'])) {
        $rxId = (int) $_POST['prescription_id'];
        try {
            $result = $db->table('prescriptions')
                ->where('prescription_id', $rxId)
                ->where('patient_id', $patientId)
                ->where('status', 'Active')
                ->update(['status' => 'Discontinued']);
            
            $_SESSION['rx_success'] = 'Prescription marked as discontinued.';
        } catch (Exception $e) {
            error_log('Discontinue error: ' . $e->getMessage());
            $_SESSION['rx_error'] = 'Action failed. Please try again.';
        }
        header('Location: ' . url('patient/prescriptions'));
        exit;
    }

    header('Location: ' . url('patient/prescriptions'));
    exit;
}

/* ═══════════════════════════════════════════════════════════
   GET — Flash messages + data fetch
════════════════════════════════════════════════════════════ */
$success = $_SESSION['rx_success'] ?? '';
$error   = $_SESSION['rx_error'] ?? '';
unset($_SESSION['rx_success'], $_SESSION['rx_error']);

/* ─── Auto-expire past-date active prescriptions ─── */
$db->table('prescriptions')
    ->where('patient_id', $patientId)
    ->where('status', 'Active')
    ->where('expiry_date', '<', date('Y-m-d'))
    ->update(['status' => 'Expired']);

/* ─── Fetch all prescriptions ─── */
$prescriptions = $db->table('prescriptions rx')
    ->select('rx.prescription_id, rx.medication_name, rx.generic_name, rx.dosage, rx.form, rx.frequency, rx.duration_days, rx.route, rx.instructions, rx.indication, rx.prescribed_date, rx.expiry_date, rx.refills_allowed, rx.refills_used, (rx.refills_allowed - rx.refills_used) AS refills_remaining, rx.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'rx.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->join('patients p', 'rx.patient_id = p.patient_id', 'INNER ')
    ->where('p.user_id', $userId)
    ->order_by('rx.prescribed_date', 'DESC')
    ->get_all();

/* ─── Summary counts ─── */
$counts = ['Active' => 0, 'Completed' => 0, 'Discontinued' => 0, 'Expired' => 0];
foreach ($prescriptions as $rx) {
    if (isset($counts[$rx['status']])) $counts[$rx['status']]++;
}

/* ─── Expiring soon (≤ 30 days, Active) ─── */
$expiringSoon = array_filter($prescriptions, fn($rx) =>
    $rx['status'] === 'Active' &&
    $rx['expiry_date'] &&
    (strtotime($rx['expiry_date']) - time()) <= 30 * 86400 &&
    strtotime($rx['expiry_date']) >= time()
);

/* ─── Color / icon maps ─── */
$statusMap = [
    'Active'       => ['color' => '#0d9488', 'bg' => '#ccfbf1', 'icon' => 'fa-circle-check'],
    'Completed'    => ['color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-circle-xmark'],
    'Discontinued' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-ban'],
    'Expired'      => ['color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
];

$formMap = [
    'Tablet'    => ['icon' => 'fa-prescription-bottle-medical', 'color' => '#0d9488', 'bg' => '#ccfbf1'],
    'Capsule'   => ['icon' => 'fa-capsules',                    'color' => '#a855f7', 'bg' => '#f3e8ff'],
    'Syrup'     => ['icon' => 'fa-bottle-droplet',              'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
    'Drops'     => ['icon' => 'fa-eye-dropper',                 'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'Injection' => ['icon' => 'fa-syringe',                     'color' => '#ef4444', 'bg' => '#fee2e2'],
    'Inhaler'   => ['icon' => 'fa-lungs',                       'color' => '#10b981', 'bg' => '#d1fae5'],
    'Patch'     => ['icon' => 'fa-bandage',                     'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'Cream'     => ['icon' => 'fa-hand-dots',                   'color' => '#d97706', 'bg' => '#fef9c3'],
    'Ointment'  => ['icon' => 'fa-hand-dots',                   'color' => '#d97706', 'bg' => '#fef9c3'],
    'Other'     => ['icon' => 'fa-pills',                       'color' => '#64748b', 'bg' => '#f1f5f9'],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Prescriptions</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script>
    
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

  </script>
   <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="<?= url('public/css/prescription.css') ?>">
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
        <h2>Prescriptions</h2>
        <p>Your medications and refill management</p>
      </div>
    </div>
   
  </div>

  <div class="content">

    <!-- Toast -->
    <?php if ($success): ?>
    <div class="toast success" id="toast"><i class="fa-solid fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
    <div class="toast error"   id="toast"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── Stat cards ── -->
    <div class="stats-grid fade-up d1">
      <div class="stat-card">
        <div class="stat-icon" style="background:#ccfbf1;color:#0d9488;"><i class="fa-solid fa-circle-check"></i></div>
        <div>
          <div class="stat-value"><?= $counts['Active'] ?></div>
          <div class="stat-label">Active</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f1f5f9;color:#64748b;"><i class="fa-solid fa-circle-xmark"></i></div>
        <div>
          <div class="stat-value"><?= $counts['Completed'] ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fa-solid fa-clock"></i></div>
        <div>
          <div class="stat-value"><?= $counts['Expired'] ?></div>
          <div class="stat-label">Expired</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#ef4444;"><i class="fa-solid fa-ban"></i></div>
        <div>
          <div class="stat-value"><?= $counts['Discontinued'] ?></div>
          <div class="stat-label">Discontinued</div>
        </div>
      </div>
    </div>

    <!-- ── Expiry warning banner ── -->
    <?php if (!empty($expiringSoon)): ?>
    <div class="alert-banner fade-up d2">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <p>
        <strong><?= count($expiringSoon) ?> prescription<?= count($expiringSoon) > 1 ? 's are' : ' is' ?> expiring soon</strong> —
        <span><?= implode(', ', array_column(array_values($expiringSoon), 'medication_name')) ?></span>.
        Contact your doctor to request a renewal.
      </p>
    </div>
    <?php endif; ?>

    <!-- ── List header + filters ── -->
    <div class="page-header fade-up d3">
      <div>
        <div class="page-title">My Prescriptions</div>
        <div class="page-subtitle"><?= count($prescriptions) ?> prescription(s) total</div>
      </div>
        <div>
        <input type="text" name="search" id="searchInput" placeholder="Search prescriptions..." class="form-control" oninput="searchRx()"/>
      </div>
      <div class="filter-tabs">
        <button class="filter-tab active" onclick="filterRx('all',          this)">All</button>
        <button class="filter-tab"        onclick="filterRx('active',       this)">Active</button>
        <button class="filter-tab"        onclick="filterRx('completed',    this)">Completed</button>
        <button class="filter-tab"        onclick="filterRx('expired',      this)">Expired</button>
        <button class="filter-tab"        onclick="filterRx('discontinued', this)">Discontinued</button>
      </div>
    </div>

    <!-- ── Prescriptions grid ── -->
    <?php if (empty($prescriptions)): ?>
      <div class="empty-state">
        <i class="fa-solid fa-prescription-bottle-medical"></i>
        <p style="font-size:15px;margin-bottom:6px;font-weight:600;">No prescriptions found</p>
        <p style="font-size:13px;">Your doctor will add prescriptions after your consultation.</p>
      </div>
    <?php else: ?>
    <div class="rx-grid fade-up d4" id="rx-grid">
      <?php foreach ($prescriptions as $i => $rx):
        $fm  = $formMap[$rx['form']]     ?? $formMap['Other'];
        $sm  = $statusMap[$rx['status']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9','icon'=>'fa-circle'];
        $isActive   = $rx['status'] === 'Active';
        $daysLeft   = $rx['expiry_date'] ? ceil((strtotime($rx['expiry_date']) - time()) / 86400) : null;
        $isExpiring = $isActive && $daysLeft !== null && $daysLeft <= 30 && $daysLeft >= 0;
        $isExpired  = $daysLeft !== null && $daysLeft < 0;

        $expiryClass = $isExpiring ? 'soon' : ($isExpired ? 'expired' : '');
        $expiryText  = $rx['expiry_date']
            ? ($isExpired
                ? 'Expired ' . date('M j, Y', strtotime($rx['expiry_date']))
                : ($isExpiring
                    ? "Expires in {$daysLeft} day" . ($daysLeft == 1 ? '' : 's')
                    : 'Expires ' . date('M j, Y', strtotime($rx['expiry_date']))))
            : 'No expiry set';

        $refillsUsed    = (int)$rx['refills_used'];
        $refillsAllowed = (int)$rx['refills_allowed'];
        $canRefill      = $isActive && $refillsUsed < $refillsAllowed;
      ?>
      <div class="rx-card <?= $isExpiring ? 'expiring' : '' ?>"
           data-status="<?= strtolower($rx['status']) ?>"
           onclick="openDetail(<?= $rx['prescription_id'] ?>)"
           style="animation: fadein .4s ease <?= $i * 0.06 ?>s both;">

        <!-- Card header -->
        <div class="rx-card-header">
          <div class="rx-form-icon" style="background:<?= $fm['bg'] ?>;color:<?= $fm['color'] ?>;">
            <i class="fa-solid <?= $fm['icon'] ?>"></i>
          </div>
          <div style="flex:1;min-width:0;">
            <div class="rx-med-name"><?= htmlspecialchars($rx['medication_name']) ?></div>
            <?php if ($rx['generic_name'] && $rx['generic_name'] !== $rx['medication_name']): ?>
            <div class="rx-generic"><?= htmlspecialchars($rx['generic_name']) ?></div>
            <?php endif; ?>
          </div>
          <span class="rx-status-badge" style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>;">
            <?= $rx['status'] ?>
          </span>
        </div>

        <!-- Card body -->
        <div class="rx-card-body">
          <div class="rx-detail-row">
            <i class="fa-solid fa-pills"></i>
            <span class="rx-detail-label">Dose:</span>
            <span class="rx-detail-value"><?= htmlspecialchars($rx['dosage']) ?> <?= htmlspecialchars($rx['form']) ?></span>
          </div>
          <div class="rx-detail-row">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="rx-detail-label">Frequency:</span>
            <span class="rx-detail-value"><?= htmlspecialchars($rx['frequency']) ?></span>
          </div>
          <div class="rx-detail-row">
            <i class="fa-solid fa-user-doctor"></i>
            <span class="rx-detail-label">Doctor:</span>
            <span class="rx-detail-value">Dr. <?= htmlspecialchars($rx['doctor_name']) ?></span>
          </div>
          <div class="rx-detail-row">
            <i class="fa-regular fa-calendar"></i>
            <span class="rx-detail-label">Prescribed:</span>
            <span class="rx-detail-value"><?= date('M j, Y', strtotime($rx['prescribed_date'])) ?></span>
          </div>
        </div>

        <hr class="rx-divider">

        <!-- Card footer -->
        <div class="rx-card-footer">
          <div>
            <!-- Refill dots -->
            <?php if ($refillsAllowed > 0): ?>
            <div class="refill-pill">
              <div class="refill-dots">
                <?php for ($d = 0; $d < $refillsAllowed; $d++): ?>
                <div class="refill-dot <?= $d < $refillsUsed ? 'used' : '' ?>"></div>
                <?php endfor; ?>
              </div>
              <span style="font-size:11px;color:var(--muted);"><?= $refillsAllowed - $refillsUsed ?>/<?= $refillsAllowed ?> refills left</span>
            </div>
            <?php endif; ?>
            <!-- Expiry -->
            <div class="expiry-info <?= $expiryClass ?>" style="margin-top:4px;">
              <i class="fa-regular fa-clock" style="margin-right:4px;"></i><?= htmlspecialchars($expiryText) ?>
            </div>
          </div>

          <div class="rx-actions" onclick="event.stopPropagation()">
            <?php if ($canRefill): ?>
            <button class="rx-btn primary"
              onclick="openRefill(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
              <i class="fa-solid fa-rotate"></i> Refill
            </button>
            <?php endif; ?>
            <?php if ($isActive): ?>
            <button class="rx-btn danger"
              onclick="openDiscontinue(<?= $rx['prescription_id'] ?>, '<?= htmlspecialchars($rx['medication_name'], ENT_QUOTES) ?>')">
              <i class="fa-solid fa-ban"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</main>

<!-- ════════════════════════════════════════════
     MODAL: Prescription Detail
════════════════════════════════════════════ -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('detailModal')"><i class="fa-solid fa-xmark"></i></button>
    <div id="detailBody"></div>
  </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL: Refill Confirm
════════════════════════════════════════════ -->
<div class="modal-overlay" id="refillModal">
  <div class="modal-box confirm-box">
    <button class="modal-close" onclick="closeModal('refillModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="confirm-icon" style="background:#ccfbf1;color:#0d9488;">
      <i class="fa-solid fa-rotate"></i>
    </div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Request Refill?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">
      You are requesting a refill for<br><strong id="refill-name"></strong>.
    </div>
    <div style="font-size:12px;color:var(--muted);">Your doctor will be notified to approve.</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="refill">
      <input type="hidden" name="prescription_id" id="refill-id">
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('refillModal')">Cancel</button>
        <button type="submit" class="btn-teal"><i class="fa-solid fa-rotate" style="margin-right:6px;"></i>Yes, Request</button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════
     MODAL: Discontinue Confirm
════════════════════════════════════════════ -->
<div class="modal-overlay" id="discontinueModal">
  <div class="modal-box confirm-box">
    <button class="modal-close" onclick="closeModal('discontinueModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="confirm-icon" style="background:#fee2e2;color:#ef4444;">
      <i class="fa-solid fa-ban"></i>
    </div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Discontinue Prescription?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:4px;">
      Stop taking <strong id="disc-name"></strong>?
    </div>
    <div style="font-size:12px;color:#ef4444;font-weight:600;">Always consult your doctor before stopping medication.</div>
    <form method="POST" action="prescriptions.php">
      <input type="hidden" name="action" value="discontinue">
      <input type="hidden" name="prescription_id" id="disc-id">
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('discontinueModal')">Keep Taking</button>
        <button type="submit" class="btn-red"><i class="fa-solid fa-ban" style="margin-right:6px;"></i>Discontinue</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Prescription data for JS detail modal ── -->
<?php
$rxById = [];
foreach ($prescriptions as $rx) $rxById[$rx['prescription_id']] = $rx;
?>
<script>
const RX_DATA    = <?= json_encode($rxById,   JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const FORM_MAP   = <?= json_encode($formMap,   JSON_HEX_TAG) ?>;
const STATUS_MAP = <?= json_encode($statusMap, JSON_HEX_TAG) ?>;

/* ─── Sidebar ─── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('open');
}

/* ─── Modals ─── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

/* ─── Filter ─── */
function filterRx(status, btn) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.rx-card').forEach(c => {
    c.style.display = (status === 'all' || c.dataset.status === status) ? '' : 'none';
  });
}

/* ─── Refill modal ─── */
function openRefill(id, name) {
  document.getElementById('refill-id').value       = id;
  document.getElementById('refill-name').textContent = name;
  openModal('refillModal');
}

/* ─── Discontinue modal ─── */
function openDiscontinue(id, name) {
  document.getElementById('disc-id').value        = id;
  document.getElementById('disc-name').textContent = name;
  openModal('discontinueModal');
}

/* ─── Detail modal ─── */
function openDetail(id) {
  const rx = RX_DATA[id];
  if (!rx) return;

  const fm = FORM_MAP[rx.form]     || FORM_MAP['Other'];
  const sm = STATUS_MAP[rx.status] || { color:'#64748b', bg:'#f1f5f9' };
  const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' }) : '—';
  const val = v => (v !== null && v !== '' && v !== undefined) ? v : '<span style="color:#94a3b8">—</span>';

  const refillDots = Array.from({ length: parseInt(rx.refills_allowed) || 0 }, (_, i) =>
    `<div class="refill-dot ${i < rx.refills_used ? 'used' : ''}"></div>`
  ).join('');

  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
      <div style="width:52px;height:52px;border-radius:14px;background:${fm.bg};color:${fm.color};display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
        <i class="fa-solid ${fm.icon}"></i>
      </div>
      <div style="flex:1;">
        <div style="font-size:18px;font-weight:700;">${rx.medication_name}</div>
        ${rx.generic_name && rx.generic_name !== rx.medication_name
          ? `<div style="font-size:13px;color:var(--muted);font-style:italic;">${rx.generic_name}</div>` : ''}
        <span style="background:${sm.bg};color:${sm.color};font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-block;margin-top:4px;">${rx.status}</span>
      </div>
    </div>

    <div class="detail-section">
      <div class="detail-section-title">Dosage & Administration</div>
      <div class="detail-grid">
        <div><div class="d-label">Dosage</div><div class="d-value">${val(rx.dosage)}</div></div>
        <div><div class="d-label">Form</div><div class="d-value">${val(rx.form)}</div></div>
        <div><div class="d-label">Frequency</div><div class="d-value">${val(rx.frequency)}</div></div>
        <div><div class="d-label">Route</div><div class="d-value">${val(rx.route)}</div></div>
        <div><div class="d-label">Duration</div><div class="d-value">${rx.duration_days ? rx.duration_days + ' days' : '—'}</div></div>
        <div><div class="d-label">Quantity</div><div class="d-value">${rx.quantity ? rx.quantity + ' units' : '—'}</div></div>
        ${rx.instructions ? `<div class="d-full"><div class="d-label">Special Instructions</div><div class="d-value" style="font-size:13px;line-height:1.5;">${rx.instructions}</div></div>` : ''}
        ${rx.indication   ? `<div class="d-full"><div class="d-label">Indication</div><div class="d-value">${rx.indication}</div></div>` : ''}
      </div>
    </div>

    <div class="detail-section">
      <div class="detail-section-title">Prescription Info</div>
      <div class="detail-grid">
        <div><div class="d-label">Prescribed By</div><div class="d-value">Dr. ${rx.doctor_name}</div></div>
        <div><div class="d-label">Specialization</div><div class="d-value">${rx.specialization}</div></div>
        <div><div class="d-label">Prescribed Date</div><div class="d-value">${fmt(rx.prescribed_date)}</div></div>
        <div><div class="d-label">Expiry Date</div><div class="d-value">${fmt(rx.expiry_date)}</div></div>
      </div>
    </div>

    ${parseInt(rx.refills_allowed) > 0 ? `
    <div class="detail-section">
      <div class="detail-section-title">Refills</div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="refill-dots">${refillDots}</div>
        <span style="font-size:13px;font-weight:600;">${rx.refills_allowed - rx.refills_used} of ${rx.refills_allowed} refills remaining</span>
      </div>
    </div>` : ''}
  `;

  openModal('detailModal');
}

/* ─── Toast auto-dismiss ─── */
const toast = document.getElementById('toast');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>