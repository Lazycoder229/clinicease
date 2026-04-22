<?php
// Doctor - Messages Page

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

/* ─── Get messages ─── */
$messages = $db->table('messages m')
    ->select('m.message_id, m.sender_id, m.recipient_id, m.subject, m.content, m.is_read, m.created_at, m.updated_at, CONCAT(up.first_name, \' \', up.last_name) AS sender_name')
    ->join('users u', 'm.sender_id = u.user_id')
    ->join('user_profiles up', 'u.user_id = up.user_id')
    ->where('m.recipient_id', $userId)
    ->order_by('m.created_at DESC')
    ->get_all();

/* ─── Stats ─── */
$totalMessages = count($messages);
$unreadCount = $db->table('messages')
    ->where('recipient_id', $userId)
    ->where('is_read', 0)
    ->count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Messages — ClinicEase</title>
  <link rel="stylesheet" href="../../public/css/output.css"/>
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

    .message-item { padding: 16px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; transition: all .2s; position: relative; }
    .message-item:hover { border-color: var(--teal); background: var(--surface); }
    .message-item.unread { background: var(--teal-light); border-color: var(--teal); }
    .message-sender { font-weight: 700; font-size: 13px; color: var(--navy); }
    .message-subject { font-size: 12px; color: var(--muted); margin-top: 4px; }
    .message-preview { font-size: 12px; color: var(--navy); margin-top: 8px; line-height: 1.5; }
    .message-date { font-size: 11px; color: var(--muted); margin-top: 8px; }
    .unread-badge { position: absolute; top: 16px; right: 16px; width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }

    .quick-link { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); font-size: 12px; font-weight: 600; color: var(--navy); text-decoration: none; transition: border-color .2s, background .2s, color .2s; }
    .quick-link:hover { border-color: var(--teal); background: var(--teal-light); color: var(--teal); }

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
        <h2>Messages</h2>
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
      <i class="fa-solid fa-envelope"></i>Messages — Communicate with patients
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
      <div class="stat-card fade-up d1">
        <div class="stat-icon" style="background:#dbeafe;color:#0284c7;">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <div>
          <div class="stat-value"><?= $totalMessages ?></div>
          <div class="stat-label">Total Messages</div>
        </div>
      </div>

      <div class="stat-card fade-up d1">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
          <i class="fa-solid fa-star"></i>
        </div>
        <div>
          <div class="stat-value"><?= $unreadCount ?></div>
          <div class="stat-label">Unread</div>
        </div>
      </div>
    </div>

    <div class="section fade-up d2">
      <div style="margin-bottom:20px;">
        <h3 style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:4px;">Inbox</h3>
        <p style="font-size:12px;color:var(--muted);">Manage your messages and communications</p>
      </div>

      <?php if ($totalMessages > 0): ?>
      
        <div style="display:grid;gap:12px;">
          <?php foreach ($messages as $msg): ?>
          <div class="message-item fade-up <?= !($msg['is_read'] ?? true) ? 'unread' : '' ?>">
            <?php if (!($msg['is_read'] ?? true)): ?>
            <div class="unread-badge"></div>
            <?php endif; ?>
            <div class="message-sender">
              <i class="fa-solid fa-circle" style="font-size:6px;color:var(--teal);margin-right:8px;"></i>
              <?= htmlspecialchars($msg['sender_name']) ?>
            </div>
            <div class="message-subject"><?= htmlspecialchars($msg['subject'] ?? 'No subject') ?></div>
            <div class="message-preview"><?= htmlspecialchars(substr($msg['content'], 0, 100)) ?><?= strlen($msg['content']) > 100 ? '...' : '' ?></div>
            <div class="message-date"><?= date('M d, Y • h:i A', strtotime($msg['created_at'])) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
        <h4>No messages</h4>
        <p>Your messages will appear here</p>
      </div>
      <?php endif; ?>

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
        <a href="<?= url('doctor/records') ?>" class="quick-link"><i class="fa-solid fa-file-medical"></i> Records</a>
        <a href="<?= url('doctor/profile') ?>" class="quick-link"><i class="fa-solid fa-user"></i> Profile</a>
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
