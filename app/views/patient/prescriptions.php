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
    ->select('u.username, u.role, CONCAT(p.first_name, \' \', p.last_name) AS full_name')
    ->inner_join('user_profiles p', 'u.user_id = p.user_id')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($currentUser['full_name'] ?? '');

/* ── POST handler ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'refill' && isset($_POST['prescription_id'])) {
        $rxId = (int) $_POST['prescription_id'];
        try {
            $rx = $db->table('prescriptions')->where('prescription_id',$rxId)->where('patient_id',$patientId)->where('status','Active')->get();
            if ($rx && (int)$rx['refills_used'] < (int)$rx['refills_allowed']) {
                $db->table('prescriptions')->where('prescription_id',$rxId)->update(['refills_used'=>((int)$rx['refills_used']+1)]);
                $_SESSION['rx_success'] = 'Refill requested successfully.';
            } else {
                $_SESSION['rx_error'] = 'Refill not available — limit reached or prescription inactive.';
            }
        } catch (Exception $e) {
            error_log('Refill error: ' . $e->getMessage());
            $_SESSION['rx_error'] = 'Refill request failed. Please try again.';
        }
        header('Location: ' . url('patient/prescriptions')); exit;
    }

    if ($action === 'discontinue' && isset($_POST['prescription_id'])) {
        $rxId = (int) $_POST['prescription_id'];
        try {
            $db->table('prescriptions')->where('prescription_id',$rxId)->where('patient_id',$patientId)->where('status','Active')->update(['status'=>'Discontinued']);
            $_SESSION['rx_success'] = 'Prescription marked as discontinued.';
        } catch (Exception $e) {
            error_log('Discontinue error: ' . $e->getMessage());
            $_SESSION['rx_error'] = 'Action failed. Please try again.';
        }
        header('Location: ' . url('patient/prescriptions')); exit;
    }

    header('Location: ' . url('patient/prescriptions')); exit;
}

$success = $_SESSION['rx_success'] ?? '';
$error   = $_SESSION['rx_error']   ?? '';
unset($_SESSION['rx_success'], $_SESSION['rx_error']);

/* Auto-expire */
$db->table('prescriptions')->where('patient_id',$patientId)->where('status','Active')->where('expiry_date','<',date('Y-m-d'))->update(['status'=>'Expired']);

$prescriptions = $db->table('prescriptions rx')
    ->select('rx.prescription_id, rx.medication_name, rx.generic_name, rx.dosage, rx.form, rx.frequency, rx.duration_days, rx.route, rx.instructions, rx.indication, rx.prescribed_date, rx.expiry_date, rx.refills_allowed, rx.refills_used, (rx.refills_allowed - rx.refills_used) AS refills_remaining, rx.status, CONCAT(dp.first_name, \' \', dp.last_name) AS doctor_name, d.specialization')
    ->join('doctors d', 'rx.doctor_id = d.doctor_id', 'INNER ')
    ->join('user_profiles dp', 'd.user_id = dp.user_id', 'INNER ')
    ->join('patients p', 'rx.patient_id = p.patient_id', 'INNER ')
    ->where('p.user_id', $userId)
    ->order_by('rx.prescribed_date', 'DESC')
    ->get_all();

$counts = ['Active'=>0,'Completed'=>0,'Discontinued'=>0,'Expired'=>0];
foreach ($prescriptions as $rx) if (isset($counts[$rx['status']])) $counts[$rx['status']]++;

$expiringSoon = array_filter($prescriptions, fn($rx) =>
    $rx['status']==='Active' && $rx['expiry_date'] &&
    (strtotime($rx['expiry_date'])-time()) <= 30*86400 && strtotime($rx['expiry_date']) >= time()
);

$statusMap = [
    'Active'       => 'bg-teal-100 text-teal-700',
    'Completed'    => 'bg-slate-100 text-slate-600',
    'Discontinued' => 'bg-red-100 text-red-600',
    'Expired'      => 'bg-amber-100 text-amber-700',
];

$formMap = [
    'Tablet'    => ['icon'=>'fa-prescription-bottle-medical','classes'=>'bg-teal-100 text-teal-600'],
    'Capsule'   => ['icon'=>'fa-capsules',                   'classes'=>'bg-purple-100 text-purple-600'],
    'Syrup'     => ['icon'=>'fa-bottle-droplet',             'classes'=>'bg-sky-100 text-sky-500'],
    'Drops'     => ['icon'=>'fa-eye-dropper',                'classes'=>'bg-blue-100 text-blue-500'],
    'Injection' => ['icon'=>'fa-syringe',                    'classes'=>'bg-red-100 text-red-500'],
    'Inhaler'   => ['icon'=>'fa-lungs',                      'classes'=>'bg-emerald-100 text-emerald-600'],
    'Patch'     => ['icon'=>'fa-bandage',                    'classes'=>'bg-amber-100 text-amber-600'],
    'Cream'     => ['icon'=>'fa-hand-dots',                  'classes'=>'bg-orange-100 text-orange-600'],
    'Ointment'  => ['icon'=>'fa-hand-dots',                  'classes'=>'bg-orange-100 text-orange-600'],
    'Other'     => ['icon'=>'fa-pills',                      'classes'=>'bg-slate-100 text-slate-500'],
];

$rxById = [];
foreach ($prescriptions as $rx) $rxById[$rx['prescription_id']] = $rx;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Prescriptions</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
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
    .filter-tab.active { background-color: #0d9488 !important; color: #fff !important; font-weight: 600; }
    .refill-dot { width: 10px; height: 10px; border-radius: 50%; background: #e2e8f0; }
    .refill-dot.used { background: #0d9488; }
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
      <i class="fa-solid fa-circle-check text-emerald-500"></i><?= htmlspecialchars($success) ?>
    </div>
    <?php elseif ($error): ?>
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-medium shadow-sm">
      <i class="fa-solid fa-triangle-exclamation text-red-400"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <?php
      $statCards = [
        ['label'=>'Active',       'value'=>$counts['Active'],       'icon'=>'fa-circle-check', 'classes'=>'bg-teal-100 text-teal-600'],
        ['label'=>'Completed',    'value'=>$counts['Completed'],    'icon'=>'fa-circle-xmark', 'classes'=>'bg-slate-100 text-slate-500'],
        ['label'=>'Expired',      'value'=>$counts['Expired'],      'icon'=>'fa-clock',        'classes'=>'bg-amber-100 text-amber-600'],
        ['label'=>'Discontinued', 'value'=>$counts['Discontinued'], 'icon'=>'fa-ban',          'classes'=>'bg-red-100 text-red-500'],
      ];
      foreach ($statCards as $sc): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl <?= $sc['classes'] ?> flex items-center justify-center shrink-0">
          <i class="fa-solid <?= $sc['icon'] ?>"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= $sc['value'] ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider"><?= $sc['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Expiry Warning -->
    <?php if (!empty($expiringSoon)): ?>
    <div class="flex items-start gap-3 px-4 py-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm font-medium shadow-sm">
      <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 shrink-0"></i>
      <p>
        <strong><?= count($expiringSoon) ?> prescription<?= count($expiringSoon)>1?'s are':' is'?> expiring soon</strong> —
        <?= implode(', ', array_column(array_values($expiringSoon),'medication_name')) ?>.
        Contact your doctor to request a renewal.
      </p>
    </div>
    <?php endif; ?>

    <!-- List Header + Filters -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-base font-bold text-slate-800">My Prescriptions</h2>
        <p class="text-xs text-slate-500 mt-0.5"><?= count($prescriptions) ?> prescription(s) total</p>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input type="text" id="searchInput" placeholder="Search prescriptions…"
               oninput="searchRx()"
               class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 w-full sm:w-52">
        <div class="flex flex-wrap gap-2">
          <button class="filter-tab active px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs transition-colors" onclick="filterRx('all',this)">All</button>
          <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs transition-colors" onclick="filterRx('active',this)">Active</button>
          <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs transition-colors" onclick="filterRx('completed',this)">Completed</button>
          <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs transition-colors" onclick="filterRx('expired',this)">Expired</button>
          <button class="filter-tab px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs transition-colors" onclick="filterRx('discontinued',this)">Discontinued</button>
        </div>
      </div>
    </div>

    <!-- Prescriptions Grid -->
    <?php if (empty($prescriptions)): ?>
      <div class="flex flex-col items-center justify-center py-16 text-slate-400 bg-white border border-slate-200 rounded-2xl">
        <i class="fa-solid fa-prescription-bottle-medical text-4xl mb-3 opacity-50"></i>
        <p class="text-sm font-semibold text-slate-600 mb-1">No prescriptions found</p>
        <p class="text-xs">Your doctor will add prescriptions after your consultation.</p>
      </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="rx-grid">
      <?php foreach ($prescriptions as $i => $rx):
        $fm = $formMap[$rx['form']] ?? $formMap['Other'];
        $sBadge = $statusMap[$rx['status']] ?? 'bg-slate-100 text-slate-600';
        $isActive   = $rx['status'] === 'Active';
        $daysLeft   = $rx['expiry_date'] ? ceil((strtotime($rx['expiry_date'])-time())/86400) : null;
        $isExpiring = $isActive && $daysLeft!==null && $daysLeft<=30 && $daysLeft>=0;
        $isExpired  = $daysLeft!==null && $daysLeft<0;
        $expiryText = $rx['expiry_date']
            ? ($isExpired ? 'Expired '.date('M j, Y',strtotime($rx['expiry_date']))
              : ($isExpiring ? "Expires in {$daysLeft} day".($daysLeft==1?'':'s')
              : 'Expires '.date('M j, Y',strtotime($rx['expiry_date']))))
            : 'No expiry set';
        $refillsUsed    = (int)$rx['refills_used'];
        $refillsAllowed = (int)$rx['refills_allowed'];
        $canRefill      = $isActive && $refillsUsed < $refillsAllowed;
      ?>
      <div class="rx-card bg-white border <?= $isExpiring ? 'border-amber-300' : 'border-slate-200' ?> rounded-2xl shadow-sm hover:shadow-md transition-all cursor-pointer overflow-hidden"
           data-status="<?= strtolower($rx['status']) ?>"
           data-search="<?= strtolower($rx['medication_name'].' '.$rx['generic_name'].' '.$rx['doctor_name']) ?>"
           onclick="openDetail(<?= $rx['prescription_id'] ?>)">

        <!-- Card Header -->
        <div class="p-4 flex items-start gap-3">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 <?= $fm['classes'] ?>">
            <i class="fa-solid <?= $fm['icon'] ?> text-sm"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($rx['medication_name']) ?></p>
            <?php if ($rx['generic_name'] && $rx['generic_name'] !== $rx['medication_name']): ?>
              <p class="text-xs text-slate-400 italic"><?= htmlspecialchars($rx['generic_name']) ?></p>
            <?php endif; ?>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide shrink-0 <?= $sBadge ?>">
            <?= $rx['status'] ?>
          </span>
        </div>

        <!-- Card Body -->
        <div class="px-4 pb-3 space-y-1.5 text-xs text-slate-500">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-pills w-3 text-slate-400"></i>
            <span class="font-medium text-slate-600">Dose:</span>
            <span><?= htmlspecialchars($rx['dosage']) ?> <?= htmlspecialchars($rx['form']) ?></span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left w-3 text-slate-400"></i>
            <span class="font-medium text-slate-600">Frequency:</span>
            <span><?= htmlspecialchars($rx['frequency']) ?></span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-user-doctor w-3 text-slate-400"></i>
            <span class="font-medium text-slate-600">Doctor:</span>
            <span>Dr. <?= htmlspecialchars($rx['doctor_name']) ?></span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fa-regular fa-calendar w-3 text-slate-400"></i>
            <span class="font-medium text-slate-600">Prescribed:</span>
            <span><?= date('M j, Y', strtotime($rx['prescribed_date'])) ?></span>
          </div>
        </div>

        <div class="mx-4 border-t border-slate-100"></div>

        <!-- Card Footer -->
        <div class="p-4 flex items-end justify-between gap-3">
          <div class="space-y-1">
            <?php if ($refillsAllowed > 0): ?>
            <div class="flex items-center gap-1.5">
              <div class="flex gap-1">
                <?php for ($d=0; $d<$refillsAllowed; $d++): ?>
                  <div class="refill-dot <?= $d<$refillsUsed?'used':'' ?>"></div>
                <?php endfor; ?>
              </div>
              <span class="text-xs text-slate-400"><?= $refillsAllowed-$refillsUsed ?>/<?= $refillsAllowed ?> refills</span>
            </div>
            <?php endif; ?>
            <p class="text-[11px] <?= $isExpiring ? 'text-amber-600 font-semibold' : ($isExpired ? 'text-red-500' : 'text-slate-400') ?>">
              <i class="fa-regular fa-clock mr-1"></i><?= htmlspecialchars($expiryText) ?>
            </p>
          </div>

          <div class="flex items-center gap-1.5" onclick="event.stopPropagation()">
            <?php if ($canRefill): ?>
            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition-colors"
                    onclick="openRefill(<?= $rx['prescription_id'] ?>,'<?= htmlspecialchars($rx['medication_name'],ENT_QUOTES) ?>')">
              <i class="fa-solid fa-rotate text-xs"></i> Refill
            </button>
            <?php endif; ?>
            <?php if ($isActive): ?>
            <button class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                    title="Discontinue"
                    onclick="openDiscontinue(<?= $rx['prescription_id'] ?>,'<?= htmlspecialchars($rx['medication_name'],ENT_QUOTES) ?>')">
              <i class="fa-solid fa-ban text-sm"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</main>


<!-- MODAL: Detail -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="detailModal">
  <div class="modal-animate bg-white w-full max-w-lg rounded-2xl shadow-2xl p-7 relative max-h-[90vh] overflow-y-auto">
    <button onclick="closeModal('detailModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div id="detailBody"></div>
  </div>
</div>

<!-- MODAL: Refill Confirm -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="refillModal">
  <div class="modal-animate bg-white w-full max-w-sm rounded-2xl shadow-2xl p-7 text-center relative">
    <button onclick="closeModal('refillModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="w-16 h-16 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center mx-auto mb-4 text-2xl">
      <i class="fa-solid fa-rotate"></i>
    </div>
    <p class="text-base font-bold text-slate-800 mb-2">Request Refill?</p>
    <p class="text-sm text-slate-500 mb-1">You are requesting a refill for</p>
    <p class="text-sm font-bold text-slate-700 mb-1" id="refill-name"></p>
    <p class="text-xs text-slate-400 mb-6">Your doctor will be notified to approve.</p>
    <form method="POST" action="<?= url('patient/prescriptions') ?>">
      <input type="hidden" name="action" value="refill">
      <input type="hidden" name="prescription_id" id="refill-id">
      <div class="flex justify-center gap-3">
        <button type="button" onclick="closeModal('refillModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Cancel
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors">
          <i class="fa-solid fa-rotate text-xs"></i> Yes, Request
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Discontinue Confirm -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="discontinueModal">
  <div class="modal-animate bg-white w-full max-w-sm rounded-2xl shadow-2xl p-7 text-center relative">
    <button onclick="closeModal('discontinueModal')"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="w-16 h-16 rounded-full bg-red-100 text-red-500 flex items-center justify-center mx-auto mb-4 text-2xl">
      <i class="fa-solid fa-ban"></i>
    </div>
    <p class="text-base font-bold text-slate-800 mb-2">Discontinue Prescription?</p>
    <p class="text-sm text-slate-500 mb-1">Stop taking <strong id="disc-name" class="text-slate-700"></strong>?</p>
    <p class="text-xs font-semibold text-red-500 mb-6">Always consult your doctor before stopping medication.</p>
    <form method="POST" action="<?= url('patient/prescriptions') ?>">
      <input type="hidden" name="action" value="discontinue">
      <input type="hidden" name="prescription_id" id="disc-id">
      <div class="flex justify-center gap-3">
        <button type="button" onclick="closeModal('discontinueModal')"
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Keep Taking
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition-colors">
          <i class="fa-solid fa-ban text-xs"></i> Discontinue
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const RX_DATA  = <?= json_encode($rxById,   JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const FORM_MAP = <?= json_encode(array_map(fn($f)=>['icon'=>$f['icon'],'classes'=>$f['classes']], $formMap), JSON_HEX_TAG) ?>;

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
  document.getElementById('overlay').classList.toggle('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
}

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
document.querySelectorAll('[id$="Modal"]').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});

function filterRx(status, btn) {
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.rx-card').forEach(c => {
    c.style.display = (status === 'all' || c.dataset.status === status) ? '' : 'none';
  });
}

function searchRx() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#rx-grid .rx-card').forEach(c => {
    c.style.display = c.dataset.search.includes(q) ? '' : 'none';
  });
}

function openRefill(id, name) {
  document.getElementById('refill-id').value         = id;
  document.getElementById('refill-name').textContent = name;
  openModal('refillModal');
}

function openDiscontinue(id, name) {
  document.getElementById('disc-id').value        = id;
  document.getElementById('disc-name').textContent = name;
  openModal('discontinueModal');
}

function openDetail(id) {
  const rx = RX_DATA[id];
  if (!rx) return;
  const fm  = FORM_MAP[rx.form] || FORM_MAP['Other'];
  const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}) : '—';
  const val = v => (v !== null && v !== '' && v !== undefined) ? v : '<span class="text-slate-300">—</span>';

  const refillDots = Array.from({length: parseInt(rx.refills_allowed)||0}, (_,i) =>
    `<div class="refill-dot ${i < rx.refills_used ? 'used' : ''}"></div>`
  ).join('');

  const statusClasses = {'Active':'bg-teal-100 text-teal-700','Completed':'bg-slate-100 text-slate-600','Discontinued':'bg-red-100 text-red-600','Expired':'bg-amber-100 text-amber-700'};
  const sBadge = statusClasses[rx.status] || 'bg-slate-100 text-slate-600';

  document.getElementById('detailBody').innerHTML = `
    <div class="flex items-start gap-4 mb-5">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 text-lg ${fm.classes}">
        <i class="fa-solid ${fm.icon}"></i>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="text-base font-bold text-slate-800">${rx.medication_name}</h3>
        ${rx.generic_name && rx.generic_name !== rx.medication_name ? `<p class="text-xs text-slate-400 italic">${rx.generic_name}</p>` : ''}
        <span class="inline-block mt-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide ${sBadge}">${rx.status}</span>
      </div>
    </div>
    <hr class="border-slate-100 mb-5">

    <div class="mb-5">
      <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Dosage & Administration</p>
      <div class="grid grid-cols-2 gap-2 text-sm">
        ${dbox('Dosage', val(rx.dosage))} ${dbox('Form', val(rx.form))}
        ${dbox('Frequency', val(rx.frequency))} ${dbox('Route', val(rx.route))}
        ${dbox('Duration', rx.duration_days ? rx.duration_days+' days' : '—')} ${dbox('Quantity', rx.quantity ? rx.quantity+' units' : '—')}
        ${rx.instructions ? `<div class="col-span-2 p-3 bg-slate-50 rounded-xl"><p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Instructions</p><p class="text-slate-700">${rx.instructions}</p></div>` : ''}
        ${rx.indication ? `<div class="col-span-2 p-3 bg-slate-50 rounded-xl"><p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">Indication</p><p class="text-slate-700">${rx.indication}</p></div>` : ''}
      </div>
    </div>

    <div class="mb-5">
      <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Prescription Info</p>
      <div class="grid grid-cols-2 gap-2 text-sm">
        ${dbox('Prescribed By', 'Dr. '+rx.doctor_name)}
        ${dbox('Specialization', rx.specialization)}
        ${dbox('Prescribed Date', fmt(rx.prescribed_date))}
        ${dbox('Expiry Date', fmt(rx.expiry_date))}
      </div>
    </div>

    ${parseInt(rx.refills_allowed) > 0 ? `
    <div>
      <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Refills</p>
      <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
        <div class="flex gap-1">${refillDots}</div>
        <span class="text-sm font-semibold text-slate-700">${rx.refills_allowed - rx.refills_used} of ${rx.refills_allowed} refills remaining</span>
      </div>
    </div>` : ''}
  `;
  openModal('detailModal');
}

function dbox(label, value) {
  return `<div class="p-3 bg-slate-50 rounded-xl">
    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-1">${label}</p>
    <p class="text-slate-800">${value}</p>
  </div>`;
}
</script>
</body>
</html>