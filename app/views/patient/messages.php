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
    ->select('u.user_id, u.username, u.role, CONCAT(p.first_name, \' \', p.last_name) AS full_name')
    ->inner_join('user_profiles p', 'u.user_id = p.user_id')
    ->where('u.user_id', $userId)
    ->get();
$full_name = htmlspecialchars($currentUser['full_name'] ?? '');

/* ── POST handler ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'send_message' && isset($_POST['recipient_id'])) {
        $recipientId    = (int) ($_POST['recipient_id'] ?? 0);
        $subject        = trim($_POST['subject'] ?? '') ?: null;
        $content        = trim($_POST['content'] ?? '');
        $messageType    = trim($_POST['message_type'] ?? 'General Inquiry');
        $conversationId = (int) ($_POST['conversation_id'] ?? 0) ?: null;

        if (!$recipientId || !$content) {
            $_SESSION['msg_error'] = 'Please provide recipient and message content.';
            header('Location: ' . url('patient/messages')); exit;
        }
        if (strlen($content) > 5000) {
            $_SESSION['msg_error'] = 'Message cannot exceed 5000 characters.';
            header('Location: ' . url('patient/messages')); exit;
        }
        try {
            $db->table('messages')->insert([
                'sender_id'       => $userId,
                'recipient_id'    => $recipientId,
                'conversation_id' => $conversationId,
                'subject'         => $subject,
                'content'         => $content,
                'message_type'    => $messageType,
                'is_read'         => 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['msg_success'] = 'Message sent successfully.';
        } catch (Exception $e) {
            error_log('Send message error: ' . $e->getMessage());
            $_SESSION['msg_error'] = 'Failed to send message. Please try again.';
        }
        header('Location: ' . url('patient/messages')); exit;
    }

    if ($action === 'mark_read' && isset($_POST['message_id'])) {
        $db->table('messages')->where('message_id',(int)$_POST['message_id'])->where('recipient_id',$userId)
            ->update(['is_read'=>1,'read_at'=>date('Y-m-d H:i:s')]);
        header('Location: ' . url('patient/messages')); exit;
    }

    if ($action === 'archive' && isset($_POST['message_id'])) {
        $db->table('messages')->where('message_id',(int)$_POST['message_id'])->where('recipient_id',$userId)
            ->update(['is_archived'=>1,'archived_at'=>date('Y-m-d H:i:s')]);
        header('Location: ' . url('patient/messages')); exit;
    }

    header('Location: ' . url('patient/messages')); exit;
}

$success = $_SESSION['msg_success'] ?? '';
$error   = $_SESSION['msg_error']   ?? '';
unset($_SESSION['msg_success'], $_SESSION['msg_error']);

$filter = in_array($_GET['filter'] ?? '', ['all','unread','archived']) ? $_GET['filter'] : 'all';

$allMessages      = $db->table('messages')->where('sender_id',    $userId)->get_all();
$receivedMessages = $db->table('messages')->where('recipient_id', $userId)->get_all();
$messages = array_merge($allMessages, $receivedMessages);
usort($messages, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));

foreach ($messages as &$msg) {
    $su = $db->table('users u')->select('u.role, CONCAT(p.first_name,\' \',p.last_name) AS full_name')
             ->join('user_profiles p','u.user_id=p.user_id')->where('u.user_id',$msg['sender_id'])->get();
    $msg['sender_name'] = $su['full_name'] ?? 'Unknown';
    $msg['sender_role'] = $su['role']      ?? 'unknown';
}
unset($msg);

if ($filter === 'unread')   $messages = array_values(array_filter($messages, fn($m) => $m['recipient_id']==$userId && !$m['is_read']));
elseif ($filter === 'archived') $messages = array_values(array_filter($messages, fn($m) => $m['is_archived']));

$conversations = [];
foreach ($messages as $msg) {
    $convId = $msg['conversation_id'] ?? md5($msg['sender_id'].'-'.$msg['recipient_id']);
    if (!isset($conversations[$convId])) {
        $participantId = $msg['sender_id'] == $userId ? $msg['recipient_id'] : $msg['sender_id'];
        $pName = ($msg['sender_id'] == $userId)
            ? ($db->table('users u')->join('user_profiles p','u.user_id=p.user_id')->where('u.user_id',$participantId)->select('CONCAT(p.first_name,\' \',p.last_name) AS name')->get()['name'] ?? 'Unknown')
            : $msg['sender_name'];
        $conversations[$convId] = ['messages'=>[],'participant'=>$participantId,'participant_name'=>$pName,'unread_count'=>0,'last_message'=>null,'last_timestamp'=>null];
    }
    $conversations[$convId]['messages'][] = $msg;
    if ($msg['recipient_id']==$userId && !$msg['is_read']) $conversations[$convId]['unread_count']++;
    if (!$conversations[$convId]['last_timestamp'] || strtotime($msg['created_at']) > strtotime($conversations[$convId]['last_timestamp'])) {
        $conversations[$convId]['last_message']   = $msg;
        $conversations[$convId]['last_timestamp'] = $msg['created_at'];
    }
}

$totalMessages = count($messages);
$unreadCount   = count(array_filter($messages, fn($m) => $m['recipient_id']==$userId && !$m['is_read']));
$archivedCount = count(array_filter($messages, fn($m) => $m['is_archived']));

$messageTypeMap = [
    'Appointment'         => ['icon'=>'fa-calendar-check',                'classes'=>'bg-teal-100 text-teal-700'],
    'Prescription'        => ['icon'=>'fa-prescription-bottle-medical',   'classes'=>'bg-purple-100 text-purple-700'],
    'Lab Result'          => ['icon'=>'fa-flask',                         'classes'=>'bg-blue-100 text-blue-700'],
    'General Inquiry'     => ['icon'=>'fa-envelope',                      'classes'=>'bg-amber-100 text-amber-700'],
    'Follow-up'           => ['icon'=>'fa-rotate-right',                  'classes'=>'bg-sky-100 text-sky-600'],
    'Urgent'              => ['icon'=>'fa-exclamation',                   'classes'=>'bg-red-100 text-red-600'],
    'System Notification' => ['icon'=>'fa-bell',                          'classes'=>'bg-emerald-100 text-emerald-700'],
    'Other'               => ['icon'=>'fa-message',                       'classes'=>'bg-slate-100 text-slate-600'],
];

$doctorsData = $db->table('doctors')->get_all();
$doctors = [];
foreach ($doctorsData as $doc) {
    $ui = $db->table('users u')->select('u.user_id, CONCAT(p.first_name,\' \',p.last_name) AS full_name')
             ->join('user_profiles p','u.user_id=p.user_id')->where('u.user_id',$doc['user_id'])->get();
    if ($ui) $doctors[] = ['user_id'=>$ui['user_id'],'full_name'=>$ui['full_name'],'specialization'=>$doc['specialization']];
}
usort($doctors, fn($a,$b) => strcmp($a['full_name'],$b['full_name']));
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ClinicEase — Messages</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
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

    <!-- Page Header -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-800">Messages</h2>
        <p class="text-sm text-slate-500 mt-0.5">Secure messaging with your care team</p>
      </div>
      <button onclick="openModal('composeModal')"
              class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition-colors">
        <i class="fa-solid fa-pen-to-square text-xs"></i> Compose
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <?php
      $stats = [
        ['label'=>'Conversations', 'value'=>count($conversations), 'icon'=>'fa-envelope',  'classes'=>'bg-blue-100 text-blue-600'],
        ['label'=>'Unread',        'value'=>$unreadCount,          'icon'=>'fa-circle',     'classes'=>'bg-red-100 text-red-500'],
        ['label'=>'Archived',      'value'=>$archivedCount,        'icon'=>'fa-archive',    'classes'=>'bg-purple-100 text-purple-600'],
        ['label'=>'Total',         'value'=>$totalMessages,        'icon'=>'fa-message',    'classes'=>'bg-emerald-100 text-emerald-600'],
      ];
      foreach ($stats as $st): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
        <div class="w-11 h-11 rounded-xl <?= $st['classes'] ?> flex items-center justify-center shrink-0">
          <i class="fa-solid <?= $st['icon'] ?>"></i>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-800"><?= $st['value'] ?></div>
          <div class="text-xs font-medium text-slate-500 uppercase tracking-wider"><?= $st['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2">
      <?php foreach (['all'=>'All','unread'=>'Unread','archived'=>'Archived'] as $f => $label): ?>
      <button onclick="window.location='<?= url('patient/messages') ?>?filter=<?= $f ?>'"
              class="px-4 py-2 rounded-xl text-sm font-medium transition-colors <?= $filter===$f ? 'bg-teal-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Messaging UI -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" style="min-height:560px;">

      <!-- Conversation List -->
      <div class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
          <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Conversations</h3>
        </div>
        <div class="flex-1 overflow-y-auto">
          <?php if (empty($conversations)): ?>
            <div class="flex flex-col items-center justify-center h-full p-6 text-center">
              <i class="fa-solid fa-inbox text-3xl text-slate-300 mb-3"></i>
              <p class="text-sm font-medium text-slate-500">No conversations</p>
              <p class="text-xs text-slate-400 mt-1">Compose a new message to begin</p>
            </div>
          <?php else: ?>
            <?php $isFirst = true; foreach ($conversations as $convId => $conv):
              $lastMsg  = $conv['last_message'];
              $initials = strtoupper(substr($conv['participant_name'], 0, 1));
              $isUnread = $conv['unread_count'] > 0;
            ?>
            <button onclick="selectConversation('<?= htmlspecialchars($convId) ?>', this)"
                    data-conv-id="<?= htmlspecialchars($convId) ?>"
                    class="w-full p-4 border-b border-slate-100 hover:bg-slate-50 text-left transition flex gap-3 <?= $isFirst ? 'bg-teal-50 border-l-4 border-l-teal-600' : '' ?>">
              <div class="w-9 h-9 rounded-full bg-gradient-to-br from-teal-500 to-blue-500 text-white text-sm font-bold flex items-center justify-center shrink-0">
                <?= $initials ?>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1">
                  <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($conv['participant_name']) ?></p>
                  <?php if ($isUnread): ?>
                    <span class="w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shrink-0"><?= $conv['unread_count'] ?></span>
                  <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars(substr($lastMsg['content'], 0, 40)) ?>…</p>
                <p class="text-xs text-slate-400 mt-0.5"><?= date('M j, H:i', strtotime($lastMsg['created_at'])) ?></p>
              </div>
            </button>
            <?php $isFirst = false; endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Message Thread -->
      <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <?php if (empty($conversations)): ?>
          <div class="flex-1 flex flex-col items-center justify-center text-center p-6">
            <i class="fa-solid fa-message text-5xl text-slate-300 mb-4"></i>
            <p class="text-sm font-medium text-slate-600">No messages yet</p>
            <p class="text-xs text-slate-400 mt-1">Select a conversation or compose a new message</p>
          </div>
        <?php else:
          $firstConv   = reset($conversations);
          $firstConvId = key($conversations);
          $partnerRole = $db->table('users')->where('user_id',$firstConv['participant'])->select('role')->get()['role'] ?? 'User';
        ?>
          <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-sm font-bold text-slate-800"><?= htmlspecialchars($firstConv['participant_name']) ?></h3>
            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars(ucfirst($partnerRole)) ?></p>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <?php foreach (array_reverse($firstConv['messages']) as $msg):
              $isOwn  = $msg['sender_id'] == $userId;
              $mType  = $messageTypeMap[$msg['message_type']] ?? $messageTypeMap['Other'];
            ?>
              <div class="flex <?= $isOwn ? 'justify-end' : 'justify-start' ?> gap-2">
                <?php if (!$isOwn): ?>
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-500 to-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0">
                    <?= strtoupper(substr($msg['sender_name'],0,1)) ?>
                  </div>
                <?php endif; ?>

                <div class="flex flex-col <?= $isOwn ? 'items-end' : 'items-start' ?> gap-1 max-w-xs lg:max-w-md">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold <?= $mType['classes'] ?>">
                    <i class="fa-solid <?= $mType['icon'] ?>"></i><?= htmlspecialchars($msg['message_type']) ?>
                  </span>
                  <?php if ($msg['subject']): ?>
                    <p class="text-xs font-semibold text-slate-700 px-1"><?= htmlspecialchars($msg['subject']) ?></p>
                  <?php endif; ?>
                  <div class="<?= $isOwn ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-800' ?> rounded-xl px-4 py-2.5">
                    <p class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                  </div>
                  <p class="text-xs text-slate-400 px-1">
                    <?= date('M j, H:i', strtotime($msg['created_at'])) ?>
                    <?php if ($isOwn && $msg['is_read']): ?><i class="fa-solid fa-check-double ml-1 opacity-60"></i><?php endif; ?>
                  </p>
                </div>

                <?php if ($isOwn): ?>
                  <div class="w-8 h-8 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                    <?= strtoupper(substr($full_name,0,1)) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="p-4 border-t border-slate-100 bg-slate-50">
            <form method="POST" class="flex gap-3">
              <input type="hidden" name="action" value="send_message">
              <input type="hidden" name="recipient_id" value="<?= $firstConv['participant'] ?>">
              <input type="hidden" name="conversation_id" value="<?= htmlspecialchars($firstConvId) ?>">
              <input type="hidden" name="message_type" value="General Inquiry">
              <textarea name="content" placeholder="Type your message…" required
                        class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none"
                        style="min-height:40px;max-height:100px;"></textarea>
              <button type="submit"
                      class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-xl transition-colors shrink-0">
                <i class="fa-solid fa-paper-plane"></i>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<!-- Compose Modal -->
<div class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4" id="composeModal">
  <div class="modal-animate bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
      <h2 class="text-base font-bold text-slate-800">New Message</h2>
      <button onclick="closeModal('composeModal')"
              class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200 transition-colors">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
      <input type="hidden" name="action" value="send_message">

      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Recipient</label>
        <select name="recipient_id" required
                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <option value="">Select a doctor…</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['user_id'] ?>">
              Dr. <?= htmlspecialchars($doc['full_name']) ?> · <?= htmlspecialchars($doc['specialization']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Message Type</label>
        <select name="message_type" required
                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
          <?php foreach (['General Inquiry','Appointment','Prescription','Lab Result','Follow-up','Urgent'] as $mt): ?>
            <option value="<?= $mt ?>"><?= $mt ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Subject <span class="font-normal normal-case text-slate-400">(optional)</span></label>
        <input type="text" name="subject" placeholder="Message subject…"
               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Message</label>
        <textarea name="content" rows="4" placeholder="Type your message…" required
                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
      </div>

      <div class="flex gap-3 pt-1">
        <button type="button" onclick="closeModal('composeModal')"
                class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
          Cancel
        </button>
        <button type="submit"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors">
          <i class="fa-solid fa-paper-plane text-xs"></i> Send
        </button>
      </div>
    </form>
  </div>
</div>

<script>
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

  function selectConversation(convId, btn) {
    document.querySelectorAll('[data-conv-id]').forEach(b => b.classList.remove('bg-teal-50','border-l-4','border-l-teal-600'));
    if (btn) btn.classList.add('bg-teal-50','border-l-4','border-l-teal-600');
  }
</script>
</body>
</html>