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

/* ─── Fetch all active doctors for Book modal ─── */
$doctors = $db->table('doctors d')
    ->select('d.doctor_id, CONCAT(p.first_name, \' \', p.last_name) AS full_name, d.specialization')
    ->join('user_profiles p', 'd.user_id = p.user_id', 'INNER ')
    ->join('users u', 'd.user_id = u.user_id', 'INNER ')
    ->order_by('p.last_name')
    ->get_all();

/* ═══════════════════════════════════════════════════════════
   POST HANDLER — PRG (Post → Redirect → Get) pattern
════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    /* ── Book ── */
    if ($action === 'book') {
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $type     = trim($_POST['appt_type']   ?? '');
        $date     = trim($_POST['appt_date']   ?? '');
        $time     = trim($_POST['appt_time']   ?? '');
        $notes    = trim($_POST['notes']        ?? '') ?: null;

        /* Server-side validation */
        if (!$doctorId || !$type || !$date || !$time) {
            $_SESSION['appt_error'] = 'Please fill in all required fields.';
            header('Location: ' . url('patient/appointments'));
            exit;
        }
        if ($date < date('Y-m-d')) {
            $_SESSION['appt_error'] = 'Appointment date cannot be in the past.';
            header('Location: ' . url('patient/appointments'));
            exit;
        }

        try {
            $db->table('appointments')->insert([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'type' => $type,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'status' => 'Pending',
                'notes' => $notes
            ]);
            $_SESSION['appt_success'] = 'Appointment booked successfully!';
        } catch (Exception $e) {
            error_log('Book error: ' . $e->getMessage());
            $_SESSION['appt_error'] = 'Booking failed. Please try again.';
        }

        header('Location: ' . url('patient/appointments'));
        exit;
    }

    /* ── Reschedule ── */
    if ($action === 'reschedule' && isset($_POST['appointment_id'])) {
        $apptId  = (int) $_POST['appointment_id'];
        $newDate = trim($_POST['new_date'] ?? '');
        $newTime = trim($_POST['new_time'] ?? '');

        if (!$newDate || !$newTime) {
            $_SESSION['appt_error'] = 'Please provide a new date and time.';
            header('Location: ' . url('patient/appointments'));
            exit;
        }
        if ($newDate < date('Y-m-d')) {
            $_SESSION['appt_error'] = 'Reschedule date cannot be in the past.';
            header('Location: ' . url('patient/appointments'));
            exit;
        }

        try {
            $db->table('appointments')
                ->where('appointment_id', $apptId)
                ->where('patient_id', $patientId)
                ->update([
                    'appointment_date' => $newDate,
                    'appointment_time' => $newTime,
                    'status' => 'Pending'
                ]);
            $_SESSION['appt_success'] = 'Appointment rescheduled successfully.';
        } catch (Exception $e) {
            error_log('Reschedule error: ' . $e->getMessage());
            $_SESSION['appt_error'] = 'Reschedule failed. Please try again.';
        }

        header('Location: ' . url('patient/appointments'));
        exit;
    }

    /* ── Cancel ── */
    if ($action === 'cancel' && isset($_POST['appointment_id'])) {
        $apptId = (int) $_POST['appointment_id'];

        try {
            $db->table('appointments')
                ->where('appointment_id', $apptId)
                ->where('patient_id', $patientId)
                ->update(['status' => 'Cancelled']);
            $_SESSION['appt_success'] = 'Appointment cancelled.';
        } catch (Exception $e) {
            error_log('Cancel error: ' . $e->getMessage());
            $_SESSION['appt_error'] = 'Cancel failed. Please try again.';
        }

        header('Location: ' . url('patient/appointments'));
        exit;
    }

    /* ── Delete (hard delete) ── */
    if ($action === 'delete' && isset($_POST['appointment_id'])) {
        $apptId = (int) $_POST['appointment_id'];

        try {
            /* Only allow deleting Cancelled or Completed records */
            $result = $db->table('appointments')
                ->where('appointment_id', $apptId)
                ->where('patient_id', $patientId)
                ->where('status', 'IN', ['Cancelled', 'Completed', 'No-show'])
                ->delete();

            if ($result > 0) {
                $_SESSION['appt_success'] = 'Appointment record deleted.';
            } else {
                $_SESSION['appt_error'] = 'Cannot delete an active appointment. Cancel it first.';
            }
        } catch (Exception $e) {
            error_log('Delete error: ' . $e->getMessage());
            $_SESSION['appt_error'] = 'Delete failed. Please try again.';
        }

        header('Location: ' . url('patient/appointments'));
        exit;
    }

    /* Fallback for unknown action */
    header('Location: ' . url('patient/appointments'));
    exit;
}

/* ═══════════════════════════════════════════════════════════
   GET — Read flash messages from session then clear them
════════════════════════════════════════════════════════════ */
$success = $_SESSION['appt_success'] ?? '';
$error   = $_SESSION['appt_error'] ?? '';
unset($_SESSION['appt_success'], $_SESSION['appt_error']);

/* ─── Fetch Appointments ─── */
$appointments = $db->table('appointments a')
    ->select('a.appointment_id, a.type, a.appointment_date, a.appointment_time, a.status, a.notes, a.doctor_notes, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'a.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->where('a.patient_id', $patientId)
    ->order_by('a.appointment_date DESC, a.appointment_time DESC')
    ->get_all();

/* ─── Icon / color map ─── */
$typeMap = [
    'General Check-up' => ['icon' => 'fa-stethoscope',     'color' => '#0d9488', 'bg' => '#ccfbf1'],
    'Dental Cleaning'  => ['icon' => 'fa-tooth',            'color' => '#d97706', 'bg' => '#fef3c7'],
    'Eye Examination'  => ['icon' => 'fa-eye',              'color' => '#3b82f6', 'bg' => '#dbeafe'],
    'Vaccination'      => ['icon' => 'fa-syringe',          'color' => '#a855f7', 'bg' => '#f3e8ff'],
    'Consultation'     => ['icon' => 'fa-comment-medical',  'color' => '#ef4444', 'bg' => '#fee2e2'],
    'Follow-up'        => ['icon' => 'fa-rotate-right',     'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
    'Laboratory'       => ['icon' => 'fa-flask',            'color' => '#10b981', 'bg' => '#d1fae5'],
    'Other'            => ['icon' => 'fa-calendar',         'color' => '#64748b', 'bg' => '#f1f5f9'],
];

$statusColors = [
    'Pending'   => ['color' => '#d97706', 'bg' => '#fef3c7'],
    'Confirmed' => ['color' => '#0d9488', 'bg' => '#ccfbf1'],
    'Scheduled' => ['color' => '#3b82f6', 'bg' => '#dbeafe'],
    'Completed' => ['color' => '#64748b', 'bg' => '#f1f5f9'],
    'Cancelled' => ['color' => '#ef4444', 'bg' => '#fee2e2'],
    'No-show'   => ['color' => '#9333ea', 'bg' => '#f3e8ff'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Appointments</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= url('public/css/appointment.css') ?>">
  
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
        <h2>Appointments</h2>
        <p>Manage and track your scheduled visits</p>
      </div>
    </div>
    
  </div>

  <div class="content">

    <!-- Flash toast (from session, shown only once) -->
    <?php if ($success): ?>
    <div class="toast success" id="toast"><i class="fa-solid fa-check-circle"></i><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
    <div class="toast error"   id="toast"><i class="fa-solid fa-triangle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header fade-up d1">
      <div>
        <div class="page-title">My Appointments</div>
        <div class="page-subtitle"><?= count($appointments) ?> appointment(s) total</div>
      </div>
      <div class="page-actions">
        <div class="filter-tabs">
          <button class="filter-tab active" onclick="filterAppts('all',      this)">All</button>
          <button class="filter-tab"        onclick="filterAppts('upcoming',  this)">Upcoming</button>
          <button class="filter-tab"        onclick="filterAppts('completed', this)">Completed</button>
          <button class="filter-tab"        onclick="filterAppts('cancelled', this)">Cancelled</button>
        </div>
        <button class="book-btn" onclick="openModal('bookModal')">
          <i class="fa-solid fa-plus"></i> Book Appointment
        </button>
      </div>
    </div>

    <!-- Appointment list -->
    <div class="panel fade-up d2" id="appt-panel">
      <?php if (empty($appointments)): ?>
        <div class="empty-state">
          <i class="fa-regular fa-calendar-xmark"></i>
          <p>No appointments found.<br>Click <strong>Book Appointment</strong> to get started.</p>
        </div>
      <?php else: ?>
        <?php
        $today = date('Y-m-d');
        foreach ($appointments as $i => $a):
          $t = $typeMap[$a['type']] ?? $typeMap['Other'];
          $s = $statusColors[$a['status']] ?? ['color' => '#64748b', 'bg' => '#f1f5f9'];

          $isPast    = ($a['appointment_date'] < $today);
          $isActive  = !in_array($a['status'], ['Cancelled', 'Completed', 'No-show']);
          $isDead    = in_array($a['status'], ['Cancelled', 'Completed', 'No-show']); // deletable

          $filterTag = match(strtolower($a['status'])) {
            'cancelled' => 'cancelled',
            'completed' => 'completed',
            'no-show'   => 'completed',
            default     => ($isPast ? 'completed' : 'upcoming'),
          };
        ?>
        <div class="appt-item"
             data-filter="<?= $filterTag ?>"
             style="animation: fadein .4s ease <?= $i * 0.055 ?>s both;">

          <div class="appt-dot" style="background:<?= $t['bg'] ?>;color:<?= $t['color'] ?>;">
            <i class="fa-solid <?= $t['icon'] ?>"></i>
          </div>

          <div class="appt-info">
            <div class="appt-title"><?= htmlspecialchars($a['type']) ?></div>
            <div class="appt-sub">
              <i class="fa-regular fa-clock" style="margin-right:4px;"></i>
              <?= date('M j, Y', strtotime($a['appointment_date'])) ?>
              &nbsp;·&nbsp;
              <?= date('g:i A', strtotime($a['appointment_time'])) ?>
              &nbsp;·&nbsp;
              <i class="fa-solid fa-user-doctor" style="margin-right:3px;"></i>
              Dr. <?= htmlspecialchars($a['doctor_name']) ?>
            </div>
            <div class="appt-spec"><?= htmlspecialchars($a['specialization']) ?></div>
          </div>

          <span class="appt-badge" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;">
            <?= htmlspecialchars($a['status']) ?>
          </span>

          <div class="action-btns">
            <?php if ($isActive && !$isPast): ?>
              <!-- Reschedule -->
              <button class="act-btn" title="Reschedule"
                onclick="openReschedule(
                  <?= $a['appointment_id'] ?>,
                  '<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>',
                  '<?= $a['appointment_date'] ?>',
                  '<?= substr($a['appointment_time'], 0, 5) ?>'
                )">
                <i class="fa-solid fa-calendar-days"></i>
              </button>
              <!-- Cancel -->
              <button class="act-btn danger" title="Cancel"
                onclick="openCancel(<?= $a['appointment_id'] ?>, '<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-xmark"></i>
              </button>
            <?php endif; ?>

            <?php if ($isDead): ?>
              <!-- Hard delete (only for Cancelled/Completed/No-show) -->
              <button class="act-btn delete-btn" title="Delete record"
                onclick="openDelete(<?= $a['appointment_id'] ?>, '<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>')">
                <i class="fa-solid fa-trash"></i>
              </button>
            <?php endif; ?>
          </div>

        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- /content -->
</main>

<!-- ════════════════════════════════════════════════
     MODAL: Book New Appointment
════════════════════════════════════════════════ -->
<div class="modal-overlay" id="bookModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('bookModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title"><i class="fa-solid fa-calendar-plus"></i>Book New Appointment</div>
    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="book">

      <div class="form-group">
        <label class="form-label">Appointment Type</label>
        <select name="appt_type" class="form-control" required>
          <option value="">Select type…</option>
          <?php foreach (array_keys($typeMap) as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Doctor</label>
        <select name="doctor_id" class="form-control" required>
          <option value="">Select doctor…</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['doctor_id'] ?>">
              Dr. <?= htmlspecialchars($doc['full_name']) ?> — <?= htmlspecialchars($doc['specialization']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date</label>
          <input type="date" name="appt_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Time</label>
          <input type="time" name="appt_time" class="form-control" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Notes (optional)</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Describe your concern or symptoms…"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('bookModal')">Cancel</button>
        <button type="submit" class="btn-submit">
          <i class="fa-solid fa-check" style="margin-right:6px;"></i>Book Appointment
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Reschedule
════════════════════════════════════════════════ -->
<div class="modal-overlay" id="rsModal">
  <div class="modal-box" style="max-width:420px;">
    <button class="modal-close" onclick="closeModal('rsModal')"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-title"><i class="fa-solid fa-calendar-days"></i>Reschedule Appointment</div>
    <div class="rs-badge"><i class="fa-solid fa-tag"></i><span id="rs-type"></span></div>
    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="appointment_id" id="rs-id">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">New Date</label>
          <input type="date" name="new_date" id="rs-date" class="form-control" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">New Time</label>
          <input type="time" name="new_time" id="rs-time" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('rsModal')">Cancel</button>
        <button type="submit" class="btn-submit">
          <i class="fa-solid fa-check" style="margin-right:6px;"></i>Confirm Reschedule
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Cancel Confirm
════════════════════════════════════════════════ -->
<div class="modal-overlay" id="cancelModal">
  <div class="modal-box" style="max-width:380px;text-align:center;">
    <button class="modal-close" onclick="closeModal('cancelModal')"><i class="fa-solid fa-xmark"></i></button>
    <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#ef4444;">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Cancel Appointment?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
      You are about to cancel <strong id="cancel-type"></strong>.<br>This action cannot be undone.
    </div>
    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="appointment_id" id="cancel-id">
      <div class="modal-footer" style="justify-content:center;">
        <button type="button" class="btn-cancel" onclick="closeModal('cancelModal')">Keep it</button>
        <button type="submit" class="btn-red">Yes, Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Delete Confirm
════════════════════════════════════════════════ -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box" style="max-width:380px;text-align:center;">
    <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fa-solid fa-xmark"></i></button>
    <div style="width:64px;height:64px;border-radius:50%;background:#f3e8ff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#7c3aed;">
      <i class="fa-solid fa-trash"></i>
    </div>
    <div style="font-size:17px;font-weight:700;margin-bottom:8px;">Delete Record?</div>
    <div style="font-size:13px;color:var(--muted);margin-bottom:24px;">
      Permanently delete <strong id="delete-type"></strong> from your records?<br>
      <span style="color:#ef4444;font-weight:600;">This cannot be undone.</span>
    </div>
    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="appointment_id" id="delete-id">
      <div class="modal-footer" style="justify-content:center;">
        <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Keep it</button>
        <button type="submit" class="btn-purple">
          <i class="fa-solid fa-trash" style="margin-right:6px;"></i>Yes, Delete
        </button>
      </div>
    </form>
  </div>
</div>

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

/* ─── Modals ─── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

function openReschedule(id, type, date, time) {
  document.getElementById('rs-id').value           = id;
  document.getElementById('rs-type').textContent   = type;
  document.getElementById('rs-date').value         = date;
  document.getElementById('rs-time').value         = time;
  openModal('rsModal');
}

function openCancel(id, type) {
  document.getElementById('cancel-id').value         = id;
  document.getElementById('cancel-type').textContent = type;
  openModal('cancelModal');
}

function openDelete(id, type) {
  document.getElementById('delete-id').value         = id;
  document.getElementById('delete-type').textContent = type;
  openModal('deleteModal');
}

/* ─── Filter tabs ─── */
function filterAppts(filter, btn) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.appt-item').forEach(item => {
    item.style.display = (filter === 'all' || item.dataset.filter === filter) ? 'flex' : 'none';
  });
}

/* ─── Auto-dismiss toast ─── */
const toast = document.getElementById('toast');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>