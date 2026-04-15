<?php
// Session already started in helpers.php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ' . url('auth/login'));
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// ── Handle Profile Update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        // Sanitize inputs
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $nationality = trim($_POST['nationality'] ?? '');
        $address = trim($_POST['address'] ?? '');

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($phone)) {
            throw new Exception('First Name, Last Name, and Phone are required.');
        }

        // Update user_profiles table
        $db->table('user_profiles')
            ->where('user_id', $user_id)
            ->update([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'nationality' => $nationality,
                'address' => $address
            ]);

        // Update patient-specific fields
        $blood_type = trim($_POST['blood_type'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? '');
        $height_cm = trim($_POST['height_cm'] ?? '');
        $weight_kg = trim($_POST['weight_kg'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $medical_conditions = trim($_POST['medical_conditions'] ?? '');
        $emergency_name = trim($_POST['emergency_name'] ?? '');
        $emergency_relation = trim($_POST['emergency_relation'] ?? '');
        $emergency_phone = trim($_POST['emergency_phone'] ?? '');
        $insurance_number = trim($_POST['insurance_number'] ?? '');
        $insurance_provider = trim($_POST['insurance_provider'] ?? '');

        $db->table('patients')
            ->where('user_id', $user_id)
            ->update([
                'blood_type' => $blood_type ?: null,
                'civil_status' => $civil_status ?: null,
                'height_cm' => $height_cm ?: null,
                'weight_kg' => $weight_kg ?: null,
                'allergies' => $allergies ?: null,
                'medical_conditions' => $medical_conditions ?: null,
                'emergency_name' => $emergency_name ?: null,
                'emergency_relation' => $emergency_relation ?: null,
                'emergency_phone' => $emergency_phone ?: null,
                'insurance_number' => $insurance_number ?: null,
                'insurance_provider' => $insurance_provider ?: null
            ]);

        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['phone'] = $phone;

        $message = 'Profile updated successfully!';

    } catch (Exception $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
        error_log($error);
    }
}

// ── Fetch patient data ─────────────────────────────────────────
try {
    $profile = $db->table('user_profiles')
        ->where('user_id', $user_id)
        ->get();

    $patient = $db->table('patients')
        ->where('user_id', $user_id)
        ->get();

    $user = $db->table('users')
        ->where('user_id', $user_id)
        ->get();

    if (!$profile || !$patient || !$user) {
        throw new Exception('Profile data not found.');
    }

} catch (Exception $e) {
    error_log('Error fetching profile: ' . $e->getMessage());
    $profile = [];
    $patient = [];
    $user = [];
}

$first_name = htmlspecialchars($profile['first_name'] ?? '');
$last_name = htmlspecialchars($profile['last_name'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$username = htmlspecialchars($user['username'] ?? '');
$phone = htmlspecialchars($profile['phone'] ?? '');
$date_of_birth = htmlspecialchars($profile['date_of_birth'] ?? '');
$gender = htmlspecialchars($profile['gender'] ?? '');
$nationality = htmlspecialchars($profile['nationality'] ?? '');
$address = htmlspecialchars($profile['address'] ?? '');
$blood_type = htmlspecialchars($patient['blood_type'] ?? '');
$civil_status = htmlspecialchars($patient['civil_status'] ?? '');
$height_cm = htmlspecialchars($patient['height_cm'] ?? '');
$weight_kg = htmlspecialchars($patient['weight_kg'] ?? '');
$allergies = htmlspecialchars($patient['allergies'] ?? '');
$medical_conditions = htmlspecialchars($patient['medical_conditions'] ?? '');
$emergency_name = htmlspecialchars($patient['emergency_name'] ?? '');
$emergency_relation = htmlspecialchars($patient['emergency_relation'] ?? '');
$emergency_phone = htmlspecialchars($patient['emergency_phone'] ?? '');
$insurance_number = htmlspecialchars($patient['insurance_number'] ?? '');
$insurance_provider = htmlspecialchars($patient['insurance_provider'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — My Profile</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
  <style>
    .profile-container {
      max-width: 900px;
      margin: 0 auto;
    }
    
    .profile-section {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 15px;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-row.full {
      grid-template-columns: 1fr;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      color: #374151;
      margin-bottom: 6px;
      font-size: 14px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: #0d9488;
      box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }

    .form-group input[readonly] {
      background-color: #f3f4f6;
      cursor: not-allowed;
    }

    .btn-group {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
    }

    .btn-primary {
      background-color: #0d9488;
      color: white;
    }

    .btn-primary:hover {
      background-color: #0f766e;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }

    .btn-secondary {
      background-color: #e5e7eb;
      color: #374151;
    }

    .btn-secondary:hover {
      background-color: #d1d5db;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert-success {
      background-color: #d1fae5;
      color: #065f46;
      border-left: 4px solid #10b981;
    }

    .alert-error {
      background-color: #fee2e2;
      color: #7f1d1d;
      border-left: 4px solid #ef4444;
    }

    .profile-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 24px;
    }

    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d9488, #14b8a6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 32px;
      box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }

    .profile-info h1 {
      font-size: 24px;
      font-weight: 700;
      color: #1f2937;
      margin: 0;
    }

    .profile-info p {
      color: #6b7280;
      font-size: 14px;
      margin: 4px 0;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-bottom: 12px;
    }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
      .profile-container {
        padding: 12px;
      }
      .profile-section {
        padding: 16px;
      }
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
    <div class="profile-container">

      <!-- Header with Avatar -->
      <div class="profile-section">
        <div class="profile-header">
          <div class="profile-avatar">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="profile-info">
            <h1><?= $first_name ?> <?= $last_name ?></h1>
            <p><strong>Email:</strong> <?= $email ?></p>
            <p><strong>Username:</strong> <?= $username ?></p>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <?php if ($message): ?>
        <div class="alert alert-success">
          <i class="fa-solid fa-circle-check"></i>
          <span><?= esc($message) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fa-solid fa-circle-xmark"></i>
          <span><?= esc($error) ?></span>
        </div>
      <?php endif; ?>

      <!-- Edit Profile Form -->
      <form method="POST" class="profile-form">
        <input type="hidden" name="action" value="update_profile">

        <!-- Personal Information Section -->
        <div class="profile-section">
          <div class="section-title">
            <i class="fa-solid fa-user-pen"></i> Personal Information
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name *</label>
              <input type="text" id="first_name" name="first_name" value="<?= $first_name ?>" required>
            </div>
            <div class="form-group">
              <label for="last_name">Last Name *</label>
              <input type="text" id="last_name" name="last_name" value="<?= $last_name ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" value="<?= $email ?>" readonly>
            </div>
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <input type="tel" id="phone" name="phone" value="<?= $phone ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="date_of_birth">Date of Birth</label>
              <input type="date" id="date_of_birth" name="date_of_birth" value="<?= $date_of_birth ?>">
            </div>
            <div class="form-group">
              <label for="gender">Gender</label>
              <select id="gender" name="gender">
                <option value="">-- Select --</option>
                <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Non-binary" <?= $gender === 'Non-binary' ? 'selected' : '' ?>>Non-binary</option>
                <option value="Prefer not to say" <?= $gender === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="nationality">Nationality</label>
              <input type="text" id="nationality" name="nationality" value="<?= $nationality ?>">
            </div>
            <div class="form-group">
              <label for="civil_status">Civil Status</label>
              <select id="civil_status" name="civil_status">
                <option value="">-- Select --</option>
                <option value="Single" <?= $civil_status === 'Single' ? 'selected' : '' ?>>Single</option>
                <option value="Married" <?= $civil_status === 'Married' ? 'selected' : '' ?>>Married</option>
                <option value="Widowed" <?= $civil_status === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                <option value="Separated" <?= $civil_status === 'Separated' ? 'selected' : '' ?>>Separated</option>
              </select>
            </div>
          </div>

          <div class="form-group form-row full">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= $address ?>">
          </div>
        </div>

        <!-- Medical Information Section -->
        <div class="profile-section">
          <div class="section-title">
            <i class="fa-solid fa-heartbeat"></i> Medical Information
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="blood_type">Blood Type</label>
              <select id="blood_type" name="blood_type">
                <option value="">-- Select --</option>
                <option value="A+" <?= $blood_type === 'A+' ? 'selected' : '' ?>>A+</option>
                <option value="A-" <?= $blood_type === 'A-' ? 'selected' : '' ?>>A-</option>
                <option value="B+" <?= $blood_type === 'B+' ? 'selected' : '' ?>>B+</option>
                <option value="B-" <?= $blood_type === 'B-' ? 'selected' : '' ?>>B-</option>
                <option value="AB+" <?= $blood_type === 'AB+' ? 'selected' : '' ?>>AB+</option>
                <option value="AB-" <?= $blood_type === 'AB-' ? 'selected' : '' ?>>AB-</option>
                <option value="O+" <?= $blood_type === 'O+' ? 'selected' : '' ?>>O+</option>
                <option value="O-" <?= $blood_type === 'O-' ? 'selected' : '' ?>>O-</option>
              </select>
            </div>
            <div class="form-group">
              <label for="height_cm">Height (cm)</label>
              <input type="number" id="height_cm" name="height_cm" value="<?= $height_cm ?>" step="0.1">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="weight_kg">Weight (kg)</label>
              <input type="number" id="weight_kg" name="weight_kg" value="<?= $weight_kg ?>" step="0.1">
            </div>
          </div>

          <div class="form-group form-row full">
            <label for="allergies">Allergies</label>
            <textarea id="allergies" name="allergies" placeholder="List any known allergies..."><?= $allergies ?></textarea>
          </div>

          <div class="form-group form-row full">
            <label for="medical_conditions">Medical Conditions</label>
            <textarea id="medical_conditions" name="medical_conditions" placeholder="List any chronic conditions or medical history..."><?= $medical_conditions ?></textarea>
          </div>
        </div>

        <!-- Emergency Contact Section -->
        <div class="profile-section">
          <div class="section-title">
            <i class="fa-solid fa-phone"></i> Emergency Contact
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="emergency_name">Emergency Contact Name</label>
              <input type="text" id="emergency_name" name="emergency_name" value="<?= $emergency_name ?>">
            </div>
            <div class="form-group">
              <label for="emergency_relation">Relationship</label>
              <select id="emergency_relation" name="emergency_relation">
                <option value="">-- Select --</option>
                <option value="Parent" <?= $emergency_relation === 'Parent' ? 'selected' : '' ?>>Parent</option>
                <option value="Spouse" <?= $emergency_relation === 'Spouse' ? 'selected' : '' ?>>Spouse</option>
                <option value="Sibling" <?= $emergency_relation === 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                <option value="Child" <?= $emergency_relation === 'Child' ? 'selected' : '' ?>>Child</option>
                <option value="Relative" <?= $emergency_relation === 'Relative' ? 'selected' : '' ?>>Relative</option>
                <option value="Friend" <?= $emergency_relation === 'Friend' ? 'selected' : '' ?>>Friend</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="emergency_phone">Emergency Phone Number</label>
              <input type="tel" id="emergency_phone" name="emergency_phone" value="<?= $emergency_phone ?>">
            </div>
          </div>
        </div>

        <!-- Insurance Information Section -->
        <div class="profile-section">
          <div class="section-title">
            <i class="fa-solid fa-shield"></i> Insurance Information
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="insurance_number">Insurance Number</label>
              <input type="text" id="insurance_number" name="insurance_number" value="<?= $insurance_number ?>">
            </div>
            <div class="form-group">
              <label for="insurance_provider">Insurance Provider</label>
              <input type="text" id="insurance_provider" name="insurance_provider" value="<?= $insurance_provider ?>">
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="profile-section">
          <div class="btn-group">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
            <button type="reset" class="btn btn-secondary">
              <i class="fa-solid fa-arrow-rotate-left"></i> Reset
            </button>
          </div>
        </div>

      </form>

    </div>
  </div>

</main>

<script src="<?= url('public/js/dashboard.js') ?>"></script>
</body>
</html>
