<?php
// Doctor - Profile Page

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

/* ─── Get doctor profile ─── */
$profile = $db->table('users u')
    ->select('u.user_id, u.email, u.role, up.first_name, up.last_name, up.phone, up.address, up.city, up.state, up.zip, d.specialization, d.license_number, d.clinic_name')
    ->join('user_profiles up', 'u.user_id = up.user_id')
    ->join('doctors d', 'u.user_id = d.user_id')
    ->where('u.user_id', $userId)
    ->get();

$full_name = htmlspecialchars(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — ClinicEase</title>
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

    .section { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; overflow: hidden; }

    .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
    .profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--teal), #0ea5e9); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 32px; color: #fff; flex-shrink: 0; }
    .profile-info h3 { font-size: 18px; font-weight: 700; color: var(--navy); }
    .profile-info p { font-size: 12px; color: var(--muted); margin-top: 4px; }

    .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
    .profile-field { padding: 16px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
    .profile-field-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }
    .profile-field-value { font-size: 13px; font-weight: 600; color: var(--navy); }

    .btn-primary { padding: 10px 18px; background: var(--teal); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; transition: background .2s; }
    .btn-primary:hover { background: #0d8078; }

    .quick-link { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); font-size: 12px; font-weight: 600; color: var(--navy); text-decoration: none; transition: border-color .2s, background .2s, color .2s; }
    .quick-link:hover { border-color: var(--teal); background: var(--teal-light); color: var(--teal); }

    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 45; }
    .overlay.open { display: block; }

    @keyframes fadein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-up { animation: fadein .4s ease both; }
    .d1 { animation-delay: .05s } .d2 { animation-delay: .10s } .d3 { animation-delay: .15s }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; } .hamburger { display: flex; }
      .content { padding: 20px 16px; } .topbar { padding: 16px 20px; }
      .profile-header { flex-direction: column; text-align: center; }
      .profile-grid { grid-template-columns: 1fr; }
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
        <h2>My Profile</h2>
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
      <i class="fa-solid fa-user-doctor"></i>Doctor Profile — Manage your information
    </div>

    <div class="section fade-up d2">
      <div class="profile-header">
        <div class="profile-avatar"><?= strtoupper(substr($profile['first_name'] ?? 'D', 0, 1)) ?></div>
        <div class="profile-info">
          <h3><?= $full_name ?></h3>
          <p><?= htmlspecialchars($profile['specialization'] ?? 'Doctor') ?></p>
        </div>
      </div>

      <div style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
        <h4 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:16px;">Professional Information</h4>
        <div class="profile-grid">
          <div class="profile-field">
            <div class="profile-field-label">License Number</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['license_number'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">Specialization</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['specialization'] ?? 'General Medicine') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">Clinic Name</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['clinic_name'] ?? 'N/A') ?></div>
          </div>
        </div>
      </div>

      <div>
        <h4 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:16px;">Contact Information</h4>
        <div class="profile-grid">
          <div class="profile-field">
            <div class="profile-field-label">Email</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">Phone</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['phone'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">Address</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['address'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">City</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['city'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">State</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['state'] ?? 'N/A') ?></div>
          </div>
          <div class="profile-field">
            <div class="profile-field-label">ZIP Code</div>
            <div class="profile-field-value"><?= htmlspecialchars($profile['zip'] ?? 'N/A') ?></div>
          </div>
        </div>
      </div>

      <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn-primary" onclick="alert('Edit profile feature coming soon')">
          <i class="fa-solid fa-edit" style="margin-right:6px;"></i>Edit Profile
        </button>
        <a href="<?= url('doctor/settings') ?>" class="btn-primary" style="background:var(--surface);color:var(--navy);border:1px solid var(--border);text-decoration:none;">
          <i class="fa-solid fa-gear" style="margin-right:6px;"></i>Settings
        </a>
      </div>
    </div>

    <div class="section fade-up d3">
      <div style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:var(--navy);">
        <i class="fa-solid fa-bolt" style="color:var(--accent);"></i> Quick Actions
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px;">
        <a href="<?= url('doctor/dashboard') ?>" class="quick-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="<?= url('doctor/appointments') ?>" class="quick-link"><i class="fa-solid fa-calendar"></i> Appointments</a>
        <a href="<?= url('doctor/prescriptions') ?>" class="quick-link"><i class="fa-solid fa-prescription-bottle"></i> Prescriptions</a>
        <a href="<?= url('doctor/patients') ?>" class="quick-link"><i class="fa-solid fa-stethoscope"></i> Patients</a>
        <a href="<?= url('doctor/messages') ?>" class="quick-link"><i class="fa-solid fa-envelope"></i> Messages</a>
        <a href="<?= url('doctor/records') ?>" class="quick-link"><i class="fa-solid fa-file-medical"></i> Records</a>
      </div>
    </div>

  </div>
</main>

<script>
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
