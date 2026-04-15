<?php
// Session already started in helpers.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . url('auth/login'));
    exit;
}

$db = new Database();
$patient_user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// ── Handle Send Message ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'send_message') {
        try {
            $recipient_user_id = intval($_POST['recipient_user_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message_type = trim($_POST['message_type'] ?? 'General Inquiry');

            if (!$recipient_user_id || empty($content)) {
                throw new Exception('Recipient and message content are required.');
            }

            // Verify recipient is a doctor
            $recipient = $db->table('users')
                ->where('user_id', $recipient_user_id)
                ->where('role', 'doctor')
                ->get();

            if (!$recipient) {
                throw new Exception('Invalid recipient.');
            }

            // Get or create conversation
            $conversation = $db->table('messages')
                ->where('sender_id', $patient_user_id)
                ->where('recipient_id', $recipient_user_id)
                ->or_where('sender_id', $recipient_user_id)
                ->where('recipient_id', $patient_user_id)
                ->get();

            $conversation_id = $conversation['conversation_id'] ?? null;

            // Insert message
            $db->table('messages')->insert([
                'sender_id' => $patient_user_id,
                'recipient_id' => $recipient_user_id,
                'conversation_id' => $conversation_id,
                'subject' => $subject ?: 'No Subject',
                'content' => $content,
                'message_type' => $message_type,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $message = 'Message sent successfully!';

        } catch (Exception $e) {
            $error = 'Error sending message: ' . $e->getMessage();
            error_log($error);
        }
    }
}

// ── Mark message as read ───────────────────────────────────────
if (isset($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $db->table('messages')
        ->where('message_id', $message_id)
        ->where('recipient_id', $patient_user_id)
        ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
}

// ── Get selected conversation ──────────────────────────────────
$selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// ── Fetch all conversations (unique doctors) ───────────────────
$stmt = $db->raw(
    "SELECT DISTINCT 
        CASE 
            WHEN sender_id = ? THEN recipient_id 
            ELSE sender_id 
        END as other_user_id,
        (SELECT COUNT(*) FROM messages m2 
         WHERE ((m2.sender_id = ? AND m2.recipient_id = other_user_id) 
            OR (m2.sender_id = other_user_id AND m2.recipient_id = ?))
         AND m2.is_read = 0 AND m2.recipient_id = ?) as unread_count,
        MAX(created_at) as last_message_time
    FROM messages 
    WHERE sender_id = ? OR recipient_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_time DESC",
    [$patient_user_id, $patient_user_id, $patient_user_id, $patient_user_id, $patient_user_id, $patient_user_id]
);
$conversations = $stmt->fetchAll();

// ── Fetch selected conversation messages ───────────────────────
$conversation_messages = [];
if ($selected_doctor_id > 0) {
    $stmt = $db->raw(
        "SELECT m.*, 
                CASE WHEN m.sender_id = ? THEN up.first_name ELSE 'You' END as sender_name,
                up.first_name, up.last_name
         FROM messages m
         LEFT JOIN user_profiles up ON m.sender_id = up.user_id
         WHERE (m.sender_id = ? AND m.recipient_id = ?) 
            OR (m.sender_id = ? AND m.recipient_id = ?)
         ORDER BY m.created_at ASC",
        [$selected_doctor_id, $patient_user_id, $selected_doctor_id, $selected_doctor_id, $patient_user_id]
    );
    $conversation_messages = $stmt->fetchAll();

    // Mark messages as read
    $db->raw(
        "UPDATE messages SET is_read = 1, read_at = NOW()
         WHERE recipient_id = ? AND sender_id = ? AND is_read = 0",
        [$patient_user_id, $selected_doctor_id]
    );
}

// ── Fetch list of available doctors ────────────────────────────
try {
    $stmt = $db->raw(
        "SELECT u.user_id, up.first_name, up.last_name, d.specialization, up.phone
         FROM users u
         JOIN user_profiles up ON u.user_id = up.user_id
         JOIN doctors d ON u.user_id = d.user_id
         WHERE u.role = 'doctor' AND u.account_status IN ('active', 'pending')
         ORDER BY up.first_name, up.last_name"
    );
    $available_doctors = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching doctors: ' . $e->getMessage());
    $available_doctors = [];
}

// Get selected doctor info
$selected_doctor = null;
if ($selected_doctor_id > 0) {
    $stmt = $db->raw(
        "SELECT u.user_id, up.first_name, up.last_name, d.specialization, d.bio, up.phone, d.consult_hours
         FROM users u
         JOIN user_profiles up ON u.user_id = up.user_id
         JOIN doctors d ON u.user_id = d.user_id
         WHERE u.user_id = ? AND u.role = 'doctor'",
        [$selected_doctor_id]
    );
    $results = $stmt->fetchAll();
    $selected_doctor = $results[0] ?? null;
}

function formatTime($datetime) {
    $dt = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($dt);

    if ($diff->days == 0) {
        if ($diff->h == 0 && $diff->i < 5) {
            return 'Just now';
        }
        return $dt->format('H:i');
    } elseif ($diff->days == 1) {
        return 'Yesterday';
    } elseif ($diff->days < 7) {
        return $diff->days . ' days ago';
    } else {
        return $dt->format('M d, Y');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Messages</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
  <style>
    /* Custom Tailwind utility for message container height */
    .messages-h {
      height: calc(100vh - 150px);
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
    <div class="flex gap-0 messages-h bg-gray-50">

      <!-- Conversations Sidebar -->
      <div class="w-80 bg-white border-r border-gray-200 overflow-y-auto flex flex-col">
        <div class="p-4 border-b border-gray-200 bg-white sticky top-0 z-10">
          <h2 class="text-lg font-semibold text-gray-800"><i class="fa-solid fa-comments mr-2"></i>Messages</h2>
        </div>
        <button class="mx-2 my-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg transition" onclick="openNewConversationModal()">
          <i class="fa-solid fa-plus mr-2"></i>New Message
        </button>
        <div class="flex-1 overflow-y-auto">
          <?php if (empty($conversations)): ?>
            <div class="p-6 text-center text-gray-400">
              <p>No conversations yet</p>
            </div>
          <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
              <?php
                $conv_doctor_id = $conv['other_user_id'];
                $stmt = $db->raw(
                  "SELECT u.user_id, up.first_name, up.last_name, d.specialization
                   FROM users u
                   JOIN user_profiles up ON u.user_id = up.user_id
                   JOIN doctors d ON u.user_id = d.user_id
                   WHERE u.user_id = ?",
                  [$conv_doctor_id]
                );
                $doctor_results = $stmt->fetchAll();
                $doctor = $doctor_results[0] ?? null;

                if (!$doctor) continue;
              ?>
              <a href="<?= url('patient/messages?doctor_id=' . $conv_doctor_id) ?>" class="flex items-center justify-between p-3.5 border-b border-gray-100 cursor-pointer transition bg-white border-l-4 border-transparent hover:bg-gray-50 <?= $selected_doctor_id == $conv_doctor_id ? 'bg-emerald-50 border-l-teal-600' : '' ?>">
                <div class="font-semibold text-gray-800 text-sm"><?= esc($doctor['first_name'] . ' ' . $doctor['last_name']) ?></div>
                <?php if ($conv['unread_count'] > 0): ?>
                  <div class="w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0"><?= $conv['unread_count'] ?></div>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="flex-1 flex flex-col bg-white">
        <?php if ($message): ?>
          <div class="px-6 py-3">
            <div class="flex items-center gap-3 p-3 bg-emerald-100 text-emerald-900 rounded-lg border-l-4 border-emerald-500">
              <i class="fa-solid fa-circle-check"></i>
              <span><?= esc($message) ?></span>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="px-6 py-3">
            <div class="flex items-center gap-3 p-3 bg-red-50 text-red-900 rounded-lg border-l-4 border-red-500">
              <i class="fa-solid fa-circle-xmark"></i>
              <span><?= esc($error) ?></span>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($available_doctors)): ?>
          <div class="px-6 py-3">
            <div class="flex items-center gap-3 p-3 bg-red-50 text-red-900 rounded-lg border-l-4 border-red-500">
              <i class="fa-solid fa-exclamation-triangle"></i>
              <span>No active doctors available. Please check with system administrator.</span>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($selected_doctor): ?>

          <!-- Chat Header -->
          <div class="flex items-center justify-between border-b border-gray-200 bg-white p-6">
            <div class="flex items-center gap-4">
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($selected_doctor['first_name'] . ' ' . $selected_doctor['last_name']) ?>&background=0D8ABC&color=fff" alt="Doctor Avatar" class="w-12 h-12 rounded-full">
              <div>
                <div class="font-semibold text-gray-900">Dr. <?= esc($selected_doctor['first_name'] . ' ' . $selected_doctor['last_name']) ?></div>
                <div class="text-sm text-gray-500"><?= esc($selected_doctor['specialization']) ?></div>
              </div>
            </div>
            <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
              <i class="fa-solid fa-phone text-lg"></i>
            </button>
          </div>

          <!-- Messages List -->
          <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
            <?php if (empty($conversation_messages)): ?>
              <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 py-12">
                <i class="fa-solid fa-message text-4xl mb-3 opacity-50"></i>
                <div class="text-sm">Start a conversation with this doctor</div>
              </div>
            <?php else: ?>
              <?php foreach ($conversation_messages as $msg): ?>
                <?php $is_sent = ($msg['sender_id'] == $patient_user_id); ?>
                <div class="flex <?= $is_sent ? 'justify-end' : 'justify-start' ?>">
                  <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                    <div class="p-3 px-4 rounded-lg break-words <?= $is_sent ? 'bg-teal-600 text-white rounded-br-none' : 'bg-gray-100 text-gray-900 rounded-bl-none' ?>">
                      <?php if ($msg['subject'] && $msg['subject'] !== 'No Subject'): ?>
                        <div class="font-semibold mb-1"><?= esc($msg['subject']) ?></div>
                      <?php endif; ?>
                      <?= nl2br(esc($msg['content'])) ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 <?= $is_sent ? 'text-right' : 'text-left' ?>">
                      <?= formatTime($msg['created_at']) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Compose Area -->
          <div class="border-t border-gray-200 bg-white p-5">
            <form method="POST" class="flex flex-col gap-3">
              <input type="hidden" name="action" value="send_message">
              <input type="hidden" name="recipient_user_id" value="<?= $selected_doctor['user_id'] ?>">
              
              <textarea 
                name="content" 
                placeholder="Type your message..."
                class="flex-1 p-3 border border-gray-300 rounded-lg focus:border-teal-600 focus:ring-1 focus:ring-teal-600/10 resize-none"
                rows="3"
                required
              ></textarea>
              <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg transition flex items-center gap-2 self-end">
                <i class="fa-solid fa-paper-plane"></i>
                Send
              </button>
            </form>
          </div>

        <?php else: ?>

          <!-- Empty State -->
          <div class="flex flex-col items-center justify-center h-full text-center py-12">
            <i class="fa-solid fa-inbox text-6xl mb-4 text-gray-400 opacity-50"></i>
            <div class="text-lg text-gray-500 mb-6">Select a conversation or start a new message</div>
            <button onclick="openNewConversationModal()" class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-2 transition">
              <i class="fa-solid fa-plus"></i> New Message
            </button>
          </div>

        <?php endif; ?>
      </div>

    </div>
  </div>

</main>

<!-- New Conversation Modal -->
<div id="newConversationModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full flex flex-col max-h-[90vh] overflow-hidden">
    <!-- Modal Header -->
    <div class="border-b border-gray-200 px-7 py-7 flex items-center justify-between flex-shrink-0 bg-white">
      <h2 class="text-2xl font-bold text-gray-900">New Message</h2>
      <button onclick="closeNewConversationModal()" class="text-gray-400 hover:text-gray-600 transition ml-4">
        <i class="fa-solid fa-xmark text-xl"></i>
      </button>
    </div>
    
    <!-- Modal Body - Scrollable -->
    <form method="POST" id="newMessageForm" class="flex-1 overflow-y-auto px-7 py-7 space-y-6">
      <input type="hidden" name="action" value="send_message">
      
      <!-- Doctor Selection -->
      <div class="flex flex-col gap-3">
        <label for="doctor_select" class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
          Select Doctor <span class="text-red-500">*</span>
        </label>
        <?php if (empty($available_doctors)): ?>
          <p class="text-red-500 text-sm bg-red-50 p-3 rounded-lg">No active doctors available at this time.</p>
        <?php else: ?>
          <select id="doctor_select" name="recipient_user_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 text-gray-900 bg-white transition">
            <option value="">-- Choose a Doctor --</option>
            <?php foreach ($available_doctors as $doctor): ?>
              <option value="<?= $doctor['user_id'] ?>">
                Dr. <?= esc($doctor['first_name'] . ' ' . $doctor['last_name']) ?> - <?= esc($doctor['specialization']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <!-- Subject -->
      <div class="flex flex-col gap-3">
        <label for="msg_subject" class="font-semibold text-gray-900 text-sm uppercase tracking-wide">Subject</label>
        <input 
          type="text" 
          id="msg_subject" 
          name="subject" 
          placeholder="e.g., Follow-up appointment" 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 text-gray-900 placeholder-gray-400 transition"
        >
      </div>

      <!-- Message Type -->
      <div class="flex flex-col gap-3">
        <label for="msg_type" class="font-semibold text-gray-900 text-sm uppercase tracking-wide">Message Type</label>
        <select id="msg_type" name="message_type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 text-gray-900 bg-white transition">
          <option value="General Inquiry">General Inquiry</option>
          <option value="Appointment">Appointment</option>
          <option value="Prescription">Prescription</option>
          <option value="Lab Result">Lab Result</option>
          <option value="Follow-up">Follow-up</option>
          <option value="Urgent">Urgent</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <!-- Message Content -->
      <div class="flex flex-col gap-3">
        <label for="msg_content" class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
          Message <span class="text-red-500">*</span>
        </label>
        <textarea 
          id="msg_content" 
          name="content" 
          placeholder="Type your message here..." 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 text-gray-900 placeholder-gray-400 resize-none min-h-40 transition"
          required
        ></textarea>
      </div>
    </form>

    <!-- Modal Footer -->
    <div class="border-t border-gray-200 px-7 py-6 flex gap-4 flex-shrink-0 bg-gray-50">
      <button 
        type="button" 
        onclick="closeNewConversationModal()" 
        class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 font-semibold rounded-lg transition duration-200"
      >
        Cancel
      </button>
      <button 
        type="submit" 
        form="newMessageForm"
        class="flex-1 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg transition duration-200 flex items-center justify-center gap-2"
      >
        <i class="fa-solid fa-paper-plane"></i>
        Send Message
      </button>
    </div>
  </div>
</div>

<script>
function openNewConversationModal() {
  document.getElementById('newConversationModal').classList.remove('hidden');
}

function closeNewConversationModal() {
  document.getElementById('newConversationModal').classList.add('hidden');
  document.getElementById('newMessageForm').reset();
}

// Auto-scroll to bottom of messages
window.addEventListener('load', function() {
  const messagesContainer = document.querySelector('.flex-1.overflow-y-auto');
  if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
});
</script>

</body>
</html>
