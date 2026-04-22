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
                'patient_id'       => $patientId,
                'doctor_id'        => $doctorId,
                'type'             => $type,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'status'           => 'Pending',
                'notes'            => $notes
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
                    'status'           => 'Pending'
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

    header('Location: ' . url('patient/appointments'));
    exit;
}

/* ─── Flash messages ─── */
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

/* ─── Icon / color map — Tailwind classes only ─── */
$typeMap = [
    'General Check-up' => ['icon' => 'fa-stethoscope',    'classes' => 'bg-teal-100 text-teal-600'],
    'Dental Cleaning'  => ['icon' => 'fa-tooth',           'classes' => 'bg-amber-100 text-amber-600'],
    'Eye Examination'  => ['icon' => 'fa-eye',             'classes' => 'bg-blue-100 text-blue-500'],
    'Vaccination'      => ['icon' => 'fa-syringe',         'classes' => 'bg-purple-100 text-purple-600'],
    'Consultation'     => ['icon' => 'fa-comment-medical', 'classes' => 'bg-red-100 text-red-500'],
    'Follow-up'        => ['icon' => 'fa-rotate-right',    'classes' => 'bg-sky-100 text-sky-500'],
    'Laboratory'       => ['icon' => 'fa-flask',           'classes' => 'bg-emerald-100 text-emerald-600'],
    'Other'            => ['icon' => 'fa-calendar',        'classes' => 'bg-slate-100 text-slate-500'],
];

$statusMap = [
    'Pending'   => 'bg-amber-100 text-amber-700',
    'Confirmed' => 'bg-teal-100 text-teal-700',
    'Scheduled' => 'bg-blue-100 text-blue-700',
    'Completed' => 'bg-slate-100 text-slate-600',
    'Cancelled' => 'bg-red-100 text-red-600',
    'No-show'   => 'bg-purple-100 text-purple-700',
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Appointments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .main-content {
      min-width: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    @media (min-width: 1024px) {
      .main-content { margin-left: 16rem; }
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(10px) scale(.98); }
      to   { opacity: 1; transform: translateY(0)   scale(1); }
    }
    .modal-animate { animation: modalIn .18s ease; }
    .filter-tab.active {
      background-color: #0d9488 !important;
      color: #fff !important;
      font-weight: 600;
    }
  </style>
</head>
<body class="bg-slate-50 h-full">

<?php include 'aside.php'; ?>
<div class="overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" id="overlay" onclick="closeSidebar()"></div>

<main class="main-content lg:ml-64">
  <?php include 'header.php'; ?>

  <div class="p-6 space-y-6">

      <!-- Toast -->
      <?php if ($success): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-medium shadow-sm">
          <i class="fa-solid fa-circle-check text-emerald-500"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php elseif ($error): ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-medium shadow-sm">
          <i class="fa-solid fa-triangle-exclamation text-red-400"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Page Header -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-800">My Appointments</h2>
          <p class="text-sm text-slate-500 mt-0.5"><?= count($appointments) ?> appointment(s) total</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <!-- Filter Tabs -->
          <div class="flex flex-wrap gap-2">
            <button class="filter-tab active px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-sm transition-colors"
                    onclick="filterAppts('all', this)">All</button>
            <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-sm transition-colors"
                    onclick="filterAppts('upcoming', this)">Upcoming</button>
            <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-sm transition-colors"
                    onclick="filterAppts('completed', this)">Completed</button>
            <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-sm transition-colors"
                    onclick="filterAppts('cancelled', this)">Cancelled</button>
          </div>

          <!-- Book Button -->
          <button onclick="openModal('bookModal')"
                  class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition-colors">
            <i class="fa-solid fa-plus text-xs"></i> Book Appointment
          </button>
        </div>
      </div>

      <!-- Appointments List -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-3">

        <?php if (empty($appointments)): ?>
          <div class="flex flex-col items-center justify-center py-16 text-slate-400">
            <i class="fa-regular fa-calendar-xmark text-4xl mb-3"></i>
            <p class="text-sm text-center">
              No appointments found.<br>
              Click <strong class="text-slate-600">Book Appointment</strong> to get started.
            </p>
          </div>

        <?php else: ?>
          <?php
          $today = date('Y-m-d');
          foreach ($appointments as $a):
            $t      = $typeMap[$a['type']] ?? $typeMap['Other'];
            $sBadge = $statusMap[$a['status']] ?? 'bg-slate-100 text-slate-600';

            $isPast   = ($a['appointment_date'] < $today);
            $isActive = !in_array($a['status'], ['Cancelled', 'Completed', 'No-show']);
            $isDead   = in_array($a['status'], ['Cancelled', 'Completed', 'No-show']);

            $filterTag = match(strtolower($a['status'])) {
              'cancelled' => 'cancelled',
              'completed' => 'completed',
              'no-show'   => 'completed',
              default     => ($isPast ? 'completed' : 'upcoming'),
            };
          ?>

          <div class="appt-item flex items-center gap-4 p-4 rounded-xl border border-slate-100 hover:bg-slate-50 hover:border-slate-200 transition-all"
               data-filter="<?= $filterTag ?>">

            <!-- Type Icon -->
            <div class="flex items-center justify-center w-11 h-11 rounded-xl shrink-0 <?= $t['classes'] ?>">
              <i class="fa-solid <?= $t['icon'] ?> text-sm"></i>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($a['type']) ?></p>
              <p class="text-xs text-slate-500 mt-0.5">
                <?= date('M j, Y', strtotime($a['appointment_date'])) ?> ·
                <?= date('g:i A', strtotime($a['appointment_time'])) ?> ·
                Dr. <?= htmlspecialchars($a['doctor_name']) ?>
              </p>
              <p class="text-xs text-slate-400"><?= htmlspecialchars($a['specialization']) ?></p>
            </div>

            <!-- Status Badge -->
            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?= $sBadge ?>">
              <?= htmlspecialchars($a['status']) ?>
            </span>

            <!-- Action Buttons -->
            <div class="flex items-center gap-1 shrink-0">
              <?php if ($isActive && !$isPast): ?>
                <button title="Reschedule"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-teal-50 hover:text-teal-600 transition-colors"
                        onclick="openReschedule(<?= $a['appointment_id'] ?>,'<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>','<?= $a['appointment_date'] ?>','<?= substr($a['appointment_time'], 0, 5) ?>')">
                  <i class="fa-solid fa-calendar-days text-sm"></i>
                </button>
                <button title="Cancel"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                        onclick="openCancel(<?= $a['appointment_id'] ?>,'<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>')">
                  <i class="fa-solid fa-xmark text-sm"></i>
                </button>
              <?php endif; ?>

              <?php if ($isDead): ?>
                <button title="Delete record"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-purple-50 hover:text-purple-600 transition-colors"
                        onclick="openDelete(<?= $a['appointment_id'] ?>,'<?= htmlspecialchars($a['type'], ENT_QUOTES) ?>')">
                  <i class="fa-solid fa-trash text-sm"></i>
                </button>
              <?php endif; ?>
            </div>

          </div>

          <?php endforeach; ?>
        <?php endif; ?>

      </div>
  </div>
</main>


<!-- ════════════════════════════════════════════════
     MODAL: Book New Appointment
════════════════════════════════════════════════ -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="bookModal">
  <div class="modal-animate bg-white w-full max-w-lg rounded-2xl shadow-2xl p-7 relative">

    <button onclick="closeModal('bookModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="flex items-center gap-2 text-lg font-bold text-slate-800 mb-6">
      <i class="fa-solid fa-calendar-plus text-teal-600"></i>
      Book New Appointment
    </div>

    <form method="POST" action="<?= url('patient/appointments') ?>" class="space-y-5">
      <input type="hidden" name="action" value="book">

      <!-- Type -->
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Appointment Type</label>
        <select name="appt_type"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                required>
          <option value="">Select type…</option>
          <?php foreach (array_keys($typeMap) as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Doctor -->
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Doctor</label>
        <select name="doctor_id"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                required>
          <option value="">Select doctor…</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['doctor_id'] ?>">
              Dr. <?= htmlspecialchars($doc['full_name']) ?> — <?= htmlspecialchars($doc['specialization']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date & Time -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Date</label>
          <input type="date" name="appt_date" min="<?= date('Y-m-d') ?>"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">Time</label>
          <input type="time" name="appt_time"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                 required>
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
          Notes <span class="text-slate-400 font-normal">(optional)</span>
        </label>
        <textarea name="notes" rows="3"
                  placeholder="Describe your concern or symptoms…"
                  class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none resize-none"></textarea>
      </div>

      <!-- Footer -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <button type="button" onclick="closeModal('bookModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Cancel
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition-colors">
          <i class="fa-solid fa-check text-xs"></i> Book Appointment
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     MODAL: Reschedule
════════════════════════════════════════════════ -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="rsModal">
  <div class="modal-animate bg-white w-full max-w-sm rounded-2xl shadow-2xl p-7 relative">

    <button onclick="closeModal('rsModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="flex items-center gap-2 text-lg font-bold text-slate-800 mb-2">
      <i class="fa-solid fa-calendar-days text-teal-600"></i> Reschedule Appointment
    </div>

    <div class="inline-flex items-center gap-2 px-3 py-1 mb-5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
      <i class="fa-solid fa-tag text-slate-400"></i>
      <span id="rs-type"></span>
    </div>

    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="appointment_id" id="rs-id">

      <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">New Date</label>
          <input type="date" name="new_date" id="rs-date" min="<?= date('Y-m-d') ?>"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-600 mb-1">New Time</label>
          <input type="time" name="new_time" id="rs-time"
                 class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none"
                 required>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <button type="button" onclick="closeModal('rsModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Cancel
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition-colors">
          <i class="fa-solid fa-check text-xs"></i> Confirm Reschedule
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     MODAL: Cancel Confirm
════════════════════════════════════════════════ -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="cancelModal">
  <div class="modal-animate bg-white w-full max-w-sm rounded-2xl shadow-2xl p-7 relative text-center">

    <button onclick="closeModal('cancelModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4 text-2xl text-red-500">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <p class="text-base font-bold text-slate-800 mb-2">Cancel Appointment?</p>
    <p class="text-sm text-slate-500 mb-6">
      You are about to cancel <strong id="cancel-type" class="text-slate-700"></strong>.<br>
      This action cannot be undone.
    </p>

    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="appointment_id" id="cancel-id">
      <div class="flex justify-center gap-3">
        <button type="button" onclick="closeModal('cancelModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Keep it
        </button>
        <button type="submit"
                class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold shadow-sm transition-colors">
          Yes, Cancel
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════
     MODAL: Delete Confirm
════════════════════════════════════════════════ -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="deleteModal">
  <div class="modal-animate bg-white w-full max-w-sm rounded-2xl shadow-2xl p-7 relative text-center">

    <button onclick="closeModal('deleteModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4 text-2xl text-purple-600">
      <i class="fa-solid fa-trash"></i>
    </div>
    <p class="text-base font-bold text-slate-800 mb-2">Delete Record?</p>
    <p class="text-sm text-slate-500 mb-6">
      Permanently delete <strong id="delete-type" class="text-slate-700"></strong> from your records?<br>
      <span class="text-red-500 font-semibold">This cannot be undone.</span>
    </p>

    <form method="POST" action="<?= url('patient/appointments') ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="appointment_id" id="delete-id">
      <div class="flex justify-center gap-3">
        <button type="button" onclick="closeModal('deleteModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Keep it
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-sm transition-colors">
          <i class="fa-solid fa-trash text-xs"></i> Yes, Delete
        </button>
      </div>
    </form>
  </div>
</div>


<script>
/* ─── Sidebar ─── */
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
  document.getElementById('overlay').classList.toggle('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
}

/* ─── Modals ─── */
function openModal(id) {
  const el = document.getElementById(id);
  el.classList.remove('hidden');
  el.classList.add('flex');
}
function closeModal(id) {
  const el = document.getElementById(id);
  el.classList.add('hidden');
  el.classList.remove('flex');
}
// Close on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

function openReschedule(id, type, date, time) {
  document.getElementById('rs-id').value         = id;
  document.getElementById('rs-type').textContent = type;
  document.getElementById('rs-date').value       = date;
  document.getElementById('rs-time').value       = time;
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
</script>
</body>
</html>