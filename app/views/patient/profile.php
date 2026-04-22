<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . url('auth/login'));
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $phone      = trim($_POST['phone']       ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $gender        = trim($_POST['gender']        ?? '');
        $nationality   = trim($_POST['nationality']   ?? '');
        $address       = trim($_POST['address']       ?? '');

        if (empty($first_name) || empty($last_name) || empty($phone)) {
            throw new Exception('First Name, Last Name, and Phone are required.');
        }

        $db->table('user_profiles')->where('user_id', $user_id)->update([
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'phone'         => $phone,
            'date_of_birth' => $date_of_birth,
            'gender'        => $gender,
            'nationality'   => $nationality,
            'address'       => $address,
        ]);

        $db->table('patients')->where('user_id', $user_id)->update([
            'blood_type'          => trim($_POST['blood_type'] ?? '') ?: null,
            'civil_status'        => trim($_POST['civil_status'] ?? '') ?: null,
            'height_cm'           => trim($_POST['height_cm'] ?? '') ?: null,
            'weight_kg'           => trim($_POST['weight_kg'] ?? '') ?: null,
            'allergies'           => trim($_POST['allergies'] ?? '') ?: null,
            'medical_conditions'  => trim($_POST['medical_conditions'] ?? '') ?: null,
            'emergency_name'      => trim($_POST['emergency_name'] ?? '') ?: null,
            'emergency_relation'  => trim($_POST['emergency_relation'] ?? '') ?: null,
            'emergency_phone'     => trim($_POST['emergency_phone'] ?? '') ?: null,
            'insurance_number'    => trim($_POST['insurance_number'] ?? '') ?: null,
            'insurance_provider'  => trim($_POST['insurance_provider'] ?? '') ?: null,
        ]);

        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name']  = $last_name;
        $_SESSION['phone']      = $phone;
        $message = 'Profile updated successfully!';

    } catch (Exception $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
        error_log($error);
    }
}

try {
    $profile = $db->table('user_profiles')->where('user_id', $user_id)->get();
    $patient = $db->table('patients')->where('user_id', $user_id)->get();
    $user    = $db->table('users')->where('user_id', $user_id)->get();
    if (!$profile || !$patient || !$user) throw new Exception('Profile data not found.');
} catch (Exception $e) {
    error_log('Error fetching profile: ' . $e->getMessage());
    $profile = []; $patient = []; $user = [];
}

$first_name          = htmlspecialchars($profile['first_name']          ?? '');
$last_name           = htmlspecialchars($profile['last_name']           ?? '');
$email               = htmlspecialchars($user['email']                  ?? '');
$username            = htmlspecialchars($user['username']               ?? '');
$phone               = htmlspecialchars($profile['phone']               ?? '');
$date_of_birth       = htmlspecialchars($profile['date_of_birth']       ?? '');
$gender              = htmlspecialchars($profile['gender']              ?? '');
$nationality         = htmlspecialchars($profile['nationality']         ?? '');
$address             = htmlspecialchars($profile['address']             ?? '');
$blood_type          = htmlspecialchars($patient['blood_type']          ?? '');
$civil_status        = htmlspecialchars($patient['civil_status']        ?? '');
$height_cm           = htmlspecialchars($patient['height_cm']           ?? '');
$weight_kg           = htmlspecialchars($patient['weight_kg']           ?? '');
$allergies           = htmlspecialchars($patient['allergies']           ?? '');
$medical_conditions  = htmlspecialchars($patient['medical_conditions']  ?? '');
$emergency_name      = htmlspecialchars($patient['emergency_name']      ?? '');
$emergency_relation  = htmlspecialchars($patient['emergency_relation']  ?? '');
$emergency_phone     = htmlspecialchars($patient['emergency_phone']     ?? '');
$insurance_number    = htmlspecialchars($patient['insurance_number']    ?? '');
$insurance_provider  = htmlspecialchars($patient['insurance_provider']  ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — My Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .main-content { min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
    @media (min-width: 1024px) { .main-content { margin-left: 16rem; } }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #0d9488;
      box-shadow: 0 0 0 3px rgba(13,148,136,.12);
    }
  </style>
</head>
<body class="bg-slate-50 h-full">

<?php include 'aside.php'; ?>
<div class="overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" id="overlay" onclick="closeSidebar()"></div>

<main class="main-content lg:ml-64">
  <?php include 'header.php'; ?>

  <div class="p-6 space-y-6">

    <!-- Profile Header Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
      <div class="flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white text-2xl shadow-sm shrink-0">
          <i class="fa-solid fa-user"></i>
        </div>
        <div>
          <h1 class="text-xl font-bold text-slate-800"><?= $first_name ?> <?= $last_name ?></h1>
          <p class="text-sm text-slate-500 mt-0.5"><strong class="text-slate-600">Email:</strong> <?= $email ?></p>
          <p class="text-sm text-slate-500"><strong class="text-slate-600">Username:</strong> <?= $username ?></p>
        </div>
      </div>
    </div>

    <!-- Flash messages -->
    <?php if ($message): ?>
      <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-medium shadow-sm">
        <i class="fa-solid fa-circle-check text-emerald-500"></i><?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm font-medium shadow-sm">
        <i class="fa-solid fa-circle-xmark text-red-400"></i><?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <input type="hidden" name="action" value="update_profile">

      <!-- Personal Information -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800 pb-4 mb-5 border-b border-slate-100">
          <i class="fa-solid fa-user-pen text-teal-600"></i> Personal Information
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?php
          $inp = fn($id,$label,$name,$val,$type='text',$extra='') =>
            "<div><label class='block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5' for='$id'>$label</label>
             <input type='$type' id='$id' name='$name' value='$val' $extra
                    class='w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all'></div>";
          ?>
          <?= $inp('first_name','First Name *','first_name',$first_name,'text','required') ?>
          <?= $inp('last_name','Last Name *','last_name',$last_name,'text','required') ?>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email Address</label>
            <input type="email" value="<?= $email ?>" readonly
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-400 bg-slate-50 cursor-not-allowed">
          </div>
          <?= $inp('phone','Phone Number *','phone',$phone,'tel','required') ?>
          <?= $inp('date_of_birth','Date of Birth','date_of_birth',$date_of_birth,'date') ?>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="gender">Gender</label>
            <select id="gender" name="gender"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all bg-white">
              <option value="">-- Select --</option>
              <?php foreach (['Male','Female','Non-binary','Prefer not to say'] as $g): ?>
                <option value="<?= $g ?>" <?= $gender===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?= $inp('nationality','Nationality','nationality',$nationality) ?>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="civil_status">Civil Status</label>
            <select id="civil_status" name="civil_status"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all bg-white">
              <option value="">-- Select --</option>
              <?php foreach (['Single','Married','Widowed','Separated'] as $cs): ?>
                <option value="<?= $cs ?>" <?= $civil_status===$cs?'selected':'' ?>><?= $cs ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= $address ?>"
                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all">
          </div>
        </div>
      </div>

      <!-- Medical Information -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800 pb-4 mb-5 border-b border-slate-100">
          <i class="fa-solid fa-heartbeat text-red-500"></i> Medical Information
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="blood_type">Blood Type</label>
            <select id="blood_type" name="blood_type"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all bg-white">
              <option value="">-- Select --</option>
              <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                <option value="<?= $bt ?>" <?= $blood_type===$bt?'selected':'' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?= $inp('height_cm','Height (cm)','height_cm',$height_cm,'number','step="0.1"') ?>
          <?= $inp('weight_kg','Weight (kg)','weight_kg',$weight_kg,'number','step="0.1"') ?>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="allergies">Allergies</label>
            <textarea id="allergies" name="allergies" rows="2"
                      placeholder="List any known allergies…"
                      class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 resize-none transition-all"><?= $allergies ?></textarea>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="medical_conditions">Medical Conditions</label>
            <textarea id="medical_conditions" name="medical_conditions" rows="2"
                      placeholder="List any chronic conditions or medical history…"
                      class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 resize-none transition-all"><?= $medical_conditions ?></textarea>
          </div>
        </div>
      </div>

      <!-- Emergency Contact -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800 pb-4 mb-5 border-b border-slate-100">
          <i class="fa-solid fa-phone text-green-600"></i> Emergency Contact
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $inp('emergency_name','Contact Name','emergency_name',$emergency_name) ?>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5" for="emergency_relation">Relationship</label>
            <select id="emergency_relation" name="emergency_relation"
                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 transition-all bg-white">
              <option value="">-- Select --</option>
              <?php foreach (['Parent','Spouse','Sibling','Child','Relative','Friend'] as $rel): ?>
                <option value="<?= $rel ?>" <?= $emergency_relation===$rel?'selected':'' ?>><?= $rel ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?= $inp('emergency_phone','Emergency Phone','emergency_phone',$emergency_phone,'tel') ?>
        </div>
      </div>

      <!-- Insurance -->
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800 pb-4 mb-5 border-b border-slate-100">
          <i class="fa-solid fa-shield text-blue-600"></i> Insurance Information
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $inp('insurance_number','Insurance Number','insurance_number',$insurance_number) ?>
          <?= $inp('insurance_provider','Insurance Provider','insurance_provider',$insurance_provider) ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 pb-6">
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition-colors">
          <i class="fa-solid fa-floppy-disk text-xs"></i> Save Changes
        </button>
        <button type="reset"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-colors">
          <i class="fa-solid fa-arrow-rotate-left mr-1.5"></i> Reset
        </button>
      </div>

    </form>
  </div>
</main>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('overlay').classList.add('hidden');
  }
</script>
</body>
</html>