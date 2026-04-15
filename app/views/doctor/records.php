<?php
// Doctor - Records Page

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ' . url('auth/login'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$db = new Database();

/* ─── Resolve doctor_id ─── */
$doctorRow = $db->table('doctors')->where('user_id', $userId)->get();
if (!$doctorRow) die('Doctor record not found.');
$doctorId = (int) $doctorRow['doctor_id'];

/* ─── Get doctor name ─── */
$me = $db->table('users u')
    ->select('CONCAT(p.first_name, \' \', p.last_name) AS full_name, u.role')
    ->join('user_profiles p', 'u.user_id = p.user_id', 'INNER ')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($me['full_name'] ?? 'Doctor');

/* ─── Get medical records ─── */
$records = $db->table('health_records hr')
    ->select('hr.record_id, hr.doctor_id, hr.patient_id, hr.visit_type, hr.doctor_notes, hr.record_date, hr.created_at, hr.updated_at, CONCAT(up.first_name, \' \', up.last_name) AS patient_name')
    ->join('patients p', 'hr.patient_id = p.patient_id')
    ->join('user_profiles up', 'p.user_id = up.user_id')
    ->where('hr.doctor_id', $doctorId)
    ->order_by('hr.record_date DESC')
    ->get_all();

/* ─── Stats ─── */
$totalRecords = count($records);
$recentRecords = array_slice($records, 0, 5);

/* ─── Get doctor's patients ─── */
$patients = $db->table('patients p')
    ->select('p.patient_id, CONCAT(up.first_name, \' \', up.last_name) AS patient_name, u.email')
    ->join('users u', 'p.user_id = u.user_id')
    ->join('user_profiles up', 'u.user_id = up.user_id')
    ->order_by('up.first_name ASC')
    ->get_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Medical Records — ClinicEase</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
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

    .records-table { width: 100%; border-collapse: collapse; }
    .records-table th { background: var(--surface); padding: 12px; text-align: left; font-weight: 700; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; border-bottom: 2px solid var(--border); }
    .records-table td { padding: 14px 12px; border-bottom: 1px solid var(--border); font-size: 13px; vertical-align: middle; }
    .records-table tbody tr:hover { background: var(--surface); }
    .records-table tbody tr:last-child td { border-bottom: none; }

    .record-item { padding: 16px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; transition: all .2s; }
    .record-item:hover { border-color: var(--teal); background: var(--surface); }

    .btn-primary { padding: 10px 18px; background: var(--teal); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; transition: background .2s; }
    .btn-primary:hover { background: #0d8078; }
    .btn-sm { padding: 6px 10px; font-size: 11px; }
    .btn-danger { background: #ef4444; }
    .btn-danger:hover { background: #dc2626; }

    .modal-content { background: var(--card); border-radius: 16px; padding: 0; max-width: 600px; width: 90%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.2); display: flex; flex-direction: column; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 28px 28px 20px 28px; flex-shrink: 0; border-bottom: 1px solid var(--border); }
    .modal-header h2 { font-size: 18px; font-weight: 700; margin: 0; }
    .modal-close { width: 32px; height: 32px; border: none; background: var(--surface); border-radius: 8px; cursor: pointer; font-size: 16px; color: var(--muted); flex-shrink: 0; }
    .modal-form-body { flex: 1; overflow-y: auto; padding: 0 28px 28px 28px; }
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 12px; font-weight: 700; color: var(--navy); margin-bottom: 8px; text-transform: uppercase; letter-spacing: .5px; }
    .form-input, .form-select, .form-textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--navy); background: var(--surface); transition: border-color .2s, background .2s; box-sizing: border-box; }
    .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--teal); background: white; box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.1); }
    .form-textarea { resize: vertical; min-height: 90px; }
    .modal-actions { display: flex; gap: 12px; padding: 20px 28px; flex-shrink: 0; border-top: 1px solid var(--border); background: var(--surface); }
    .modal-actions button { flex: 1; }

    .empty-state { text-align: center; padding: 60px 24px; color: var(--muted); }
    .empty-state .empty-icon { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; }
    .empty-state h4 { font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    .empty-state p { font-size: 13px; }

    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 45; }
    .overlay.open { display: block; }

    @keyframes fadein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-up { animation: fadein .4s ease both; }
    .d1 { animation-delay: .05s } .d2 { animation-delay: .10s } .d3 { animation-delay: .15s }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; } .hamburger { display: flex; }
      .content { padding: 20px 16px; } .topbar { padding: 16px 20px; }
      .records-table th, .records-table td { padding: 10px 8px; font-size: 12px; }
      .hide-mobile { display: none; }
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
        <h2>Medical Records</h2>
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
      <i class="fa-solid fa-file-medical"></i>Medical Records — View patient health records
    </div>

    <div class="stat-card fade-up d1" style="margin-bottom:24px;">
      <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
        <i class="fa-solid fa-folder-open"></i>
      </div>
      <div>
        <div class="stat-value"><?= $totalRecords ?></div>
        <div class="stat-label">Total Records</div>
      </div>
    </div>

    <div class="section fade-up d2">
      <div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
        <div>
          <h3 style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px;">Patient Health Records</h3>
          <p style="font-size:12px;color:var(--muted);">View and manage all patient medical records</p>
        </div>
        <button class="btn-primary" onclick="openRecordModal()" style="padding:8px 16px;font-size:12px;">
          <i class="fa-solid fa-plus" style="margin-right:6px;"></i>New Record
        </button>
      </div>

      <?php if ($totalRecords > 0): ?>
      
        <div style="display:grid;gap:12px;">
          <?php foreach ($records as $record): ?>
          <div class="record-item fade-up">
            <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;margin-bottom:8px;">
              <div>
                <div style="font-weight:700;font-size:13px;color:var(--navy);">
                  <?= htmlspecialchars($record['patient_name']) ?>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                  <?= date('M d, Y', strtotime($record['record_date'])) ?>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span style="background:var(--teal-light);color:var(--teal);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;">
                  <?= htmlspecialchars($record['visit_type'] ?? 'General Check-up') ?>
                </span>
              </div>
            </div>
            <?php if ($record['doctor_notes'] ?? false): ?>
            <div style="font-size:12px;color:var(--navy);margin-top:8px;padding:8px 12px;background:var(--surface);border-radius:6px;border-left:3px solid var(--teal);">
              <?= htmlspecialchars($record['doctor_notes']) ?>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
              <button class="btn-primary btn-sm" onclick="editRecord(<?= $record['record_id'] ?>, '<?= htmlspecialchars(addslashes($record['patient_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars($record['visit_type'], ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($record['doctor_notes']), ENT_QUOTES) ?>')">
                <i class="fa-solid fa-edit"></i> Edit
              </button>
              <button class="btn-primary btn-sm btn-danger" onclick="deleteRecord(<?= $record['record_id'] ?>, '<?= htmlspecialchars(addslashes($record['patient_name']), ENT_QUOTES) ?>')">
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-file-circle-xmark"></i></div>
        <h4>No records found</h4>
        <p>Medical records will appear here</p>
      </div>
      <?php endif; ?>

    </div>

   
    </div>

  </div>
</main>

<!-- Record Modal -->
<div class="modal" id="recordModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modalTitle">Create Medical Record</h2>
      <button class="modal-close" onclick="closeRecordModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <div class="modal-form-body">
      <form id="recordForm" method="POST">
        <input type="hidden" id="recordAction" value="create">
        <input type="hidden" id="recordId" value="">

        <div class="form-group">
          <label class="form-label">Select Patient *</label>
          <select id="patientId" class="form-select" required>
            <option value="">Choose a patient...</option>
            <?php foreach ($patients as $patient): ?>
            <option value="<?= $patient['patient_id'] ?>">
              <?= htmlspecialchars($patient['patient_name']) ?> (<?= htmlspecialchars($patient['email']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Visit Date *</label>
          <input type="date" id="recordDate" class="form-input" required>
        </div>

        <div class="form-group">
          <label class="form-label">Visit Type *</label>
          <select id="visitType" class="form-select" required>
            <option value="General Check-up">General Check-up</option>
            <option value="Follow-up">Follow-up</option>
            <option value="Emergency">Emergency</option>
            <option value="Vaccination">Vaccination</option>
            <option value="Laboratory">Laboratory</option>
            <option value="Consultation">Consultation</option>
            <option value="Procedure">Procedure</option>
            <option value="Dental">Dental</option>
            <option value="Eye Exam">Eye Exam</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Chief Complaint</label>
          <textarea id="chiefComplaint" class="form-textarea" placeholder="Patient reported symptoms..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Diagnosis</label>
          <textarea id="diagnosis" class="form-textarea" placeholder="Clinical diagnosis..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Treatment</label>
          <textarea id="treatment" class="form-textarea" placeholder="Treatment plan..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Doctor Notes *</label>
          <textarea id="doctorNotes" class="form-textarea" placeholder="Additional notes..." required></textarea>
        </div>
      </form>
    </div>

    <div class="modal-actions">
      <button type="button" class="btn-primary" onclick="closeRecordModal()" style="background:var(--surface);color:var(--navy);border:1px solid var(--border);">Cancel</button>
      <button type="button" class="btn-primary" id="submitBtn" onclick="document.getElementById('recordForm').dispatchEvent(new Event('submit'))">Save Record</button>
    </div>
  </div>
</div>

<script>
  function openRecordModal() {
    document.getElementById('modalTitle').textContent = 'Create Medical Record';
    document.getElementById('recordAction').value = 'create';
    document.getElementById('recordId').value = '';
    document.getElementById('patientId').value = '';
    document.getElementById('recordForm').reset();
    document.getElementById('recordDate').valueAsDate = new Date();
    document.getElementById('submitBtn').textContent = 'Save Record';
    document.getElementById('recordModal').classList.add('open');
  }

  function editRecord(recordId, patientName, visitType, doctorNotes) {
    document.getElementById('modalTitle').textContent = 'Edit Medical Record — ' + patientName;
    document.getElementById('recordAction').value = 'edit';
    document.getElementById('recordId').value = recordId;
    document.getElementById('visitType').value = visitType;
    document.getElementById('doctorNotes').value = doctorNotes;
    document.getElementById('patientId').disabled = true;
    document.getElementById('recordDate').disabled = true;
    document.getElementById('submitBtn').textContent = 'Update Record';
    document.getElementById('recordModal').classList.add('open');
  }

  function closeRecordModal() {
    document.getElementById('recordModal').classList.remove('open');
    document.getElementById('patientId').disabled = false;
    document.getElementById('recordDate').disabled = false;
  }

  document.getElementById('recordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const action = document.getElementById('recordAction').value;
    const patientId = document.getElementById('patientId').value;
    const recordId = document.getElementById('recordId').value;
    
    if (!patientId && action === 'create') {
      alert('Please select a patient');
      return;
    }

    const payload = {
      patient_id: patientId || null,
      record_id: recordId || null,
      record_date: document.getElementById('recordDate').value,
      visit_type: document.getElementById('visitType').value,
      chief_complaint: document.getElementById('chiefComplaint').value,
      diagnosis: document.getElementById('diagnosis').value,
      treatment: document.getElementById('treatment').value,
      doctor_notes: document.getElementById('doctorNotes').value
    };

    try {
      const endpoint = action === 'create' 
        ? '<?= url("api/doctor/records/create") ?>'
        : '<?= url("api/doctor/records/update") ?>';

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (response.ok) {
        alert(result.message || 'Record saved successfully!');
        location.reload();
      } else {
        alert('Error: ' + (result.message || 'Failed to save record'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error saving record: ' + error.message);
    }

    closeRecordModal();
  });

  function deleteRecord(recordId, patientName) {
    if (confirm('Are you sure you want to delete this record for ' + patientName + '? This cannot be undone.')) {
      fetch('<?= url("api/doctor/records/delete") ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ record_id: recordId })
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          alert('Record deleted successfully');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Failed to delete record'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error deleting record: ' + error.message);
      });
    }
  }

  function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
  }
  function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
  }
</script>
</body>
</html>
