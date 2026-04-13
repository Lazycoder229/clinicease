<?php
// Session already started in helpers.php
$success = isset($_GET['success']);
$errors  = $_SESSION['reg_errors'] ?? [];
unset($_SESSION['reg_errors']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <title>ClinicEase — Register</title>
  <style>
    :root {
      --navy:   #0f4c81;
      --teal:   #0d9488;
      --sky:    #1a6fa8;
      --light:  #f0f7ff;
      --border: #e2eaf2;
    }
    * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
    body {
      background-color: #eaf2fb;
      background-image:
        radial-gradient(at 15% 15%, rgba(99,179,237,0.2) 0px, transparent 55%),
        radial-gradient(at 85% 85%, rgba(13,148,136,0.14) 0px, transparent 50%),
        radial-gradient(at 70% 5%,  rgba(15,76,129,0.10) 0px, transparent 40%);
      min-height: 100vh;
      font-size: 16px;
    }
    .progress-track { background: #dbeafe; border-radius: 999px; height: 6px; }
    .progress-fill  { background: linear-gradient(90deg, var(--navy), var(--teal)); border-radius: 999px; height: 6px; transition: width 0.4s ease; }
    .step-dot {
      width: 36px; height: 36px; border-radius: 50%;
      border: 2px solid #cbd5e1; background: #fff; color: #94a3b8;
      font-size: 0.875rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.3s; flex-shrink: 0;
    }
    .step-dot.active  { border-color: var(--navy); background: var(--navy); color: #fff; }
    .step-dot.done    { border-color: var(--teal);  background: var(--teal);  color: #fff; }
    .step-label { font-size: 0.875rem; color: #94a3b8; font-weight: 500; margin-top: 5px; text-align: center; }
    .step-label.active { color: var(--navy); font-weight: 600; }
    .role-card {
      border: 2px solid var(--border); border-radius: 14px; padding: 22px 16px;
      cursor: pointer; background: #fff; transition: all 0.2s; text-align: center; position: relative;
    }
    .role-card:hover { border-color: var(--sky); background: #f0f7ff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,76,129,0.10); }
    .role-card.selected { border-color: var(--navy); background: linear-gradient(135deg, #eef4fb, #e8f7f5); box-shadow: 0 6px 22px rgba(15,76,129,0.15); }
    .role-card .check { position: absolute; top: 10px; right: 10px; width: 22px; height: 22px; border-radius: 50%; background: var(--teal); color: #fff; font-size: 11px; display: none; align-items: center; justify-content: center; }
    .role-card.selected .check { display: flex; }
    .role-icon { width: 56px; height: 56px; border-radius: 14px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .role-card p.role-name { font-size: 0.95rem; }
    .role-card p.role-desc { font-size: 0.82rem; }
    .field-group { margin-bottom: 20px; }
    .field-label { display: block; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .field-label span.req { color: #ef4444; margin-left: 2px; }
    .input-wrap { position: relative; }
    .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.95rem; pointer-events: none; }
    .field-input {
      display: block; width: 100%; border-radius: 12px;
      border: 1.5px solid var(--border); padding: 13px 16px 13px 44px;
      font-size: 1rem; color: #1a202c; background: #f8fafc;
      outline: none; min-height: 50px;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .field-input:focus { border-color: var(--sky); box-shadow: 0 0 0 3px rgba(26,111,168,0.12); background: #fff; }
    .field-input.no-icon { padding-left: 16px; }
    .field-input.error { border-color: #ef4444; }
    select.field-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; }
    .section-title {
      font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em;
      color: var(--sky); text-transform: uppercase;
      border-left: 3px solid var(--teal); padding-left: 8px; margin: 24px 0 16px;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--navy), var(--sky));
      color: #fff; font-weight: 600; font-size: 1rem;
      padding: 14px 32px; border-radius: 12px; border: none; cursor: pointer;
      box-shadow: 0 4px 14px rgba(15,76,129,0.35); min-height: 50px;
      transition: transform 0.15s, box-shadow 0.15s;
    }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(15,76,129,0.45); }
    .btn-outline {
      background: #fff; color: #475569; font-weight: 600; font-size: 1rem;
      padding: 14px 32px; border-radius: 12px; border: 1.5px solid var(--border);
      cursor: pointer; min-height: 50px; transition: all 0.15s;
    }
    .btn-outline:hover { border-color: var(--sky); color: var(--navy); }
    .tip-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px 16px; font-size: 0.9rem; color: #1e40af; display: flex; gap: 10px; align-items: flex-start; }
    @keyframes fadeSlideIn { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
    .animate-in { animation: fadeSlideIn 0.35s ease both; }
    .role-fields { display: none; }
    .role-fields.visible { display: block; animation: fadeSlideIn 0.35s ease both; }
    .step-panel { display: none; }
    .step-panel.active { display: block; animation: fadeSlideIn 0.35s ease both; }
    .success-ring { width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, #0f4c81, #0d9488); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 30px rgba(13,148,136,0.3); }
    .strength-bar { height: 4px; border-radius: 999px; transition: width 0.3s, background 0.3s; }
    h1 { font-size: 2rem !important; }
    h2 { font-size: 1.4rem !important; }
    p { font-size: 1rem; }
    .text-sm, p.text-sm { font-size: 0.95rem !important; }
    .text-xs { font-size: 0.85rem !important; }
    textarea.field-input { min-height: 90px; font-size: 1rem; }
  </style>
</head>
<body>

  <div class="max-w-3xl mx-auto px-4 py-8">

    <!-- Page heading -->
    <div class="text-center mb-8 animate-in">
      <span class="inline-flex items-center gap-2 bg-blue-50 border border-blue-100 text-blue-700 text-sm font-semibold px-3 py-1.5 rounded-full mb-3">
        <i class="fa-solid fa-user-plus"></i> New Account Registration
      </span>
      <h1 class="text-3xl font-bold text-gray-800" style="font-family:'Playfair Display',serif;">Create Your Account</h1>
      <p class="text-gray-500 text-sm mt-2">Fill in your details below to get started with ClinicEase</p>
    </div>

    <!-- Step tracker -->
    <div class="bg-white rounded-2xl shadow-sm border border-white/80 p-5 mb-6 animate-in" style="animation-delay:0.05s">
      <div class="flex items-start justify-between gap-2 mb-3">
        <div class="flex flex-col items-center">
          <div class="step-dot active" id="dot-1">1</div>
          <div class="step-label active" id="lbl-1">Role</div>
        </div>
        <div class="flex-1 mt-4"><div class="progress-track"><div class="progress-fill" id="line-1" style="width:0%"></div></div></div>
        <div class="flex flex-col items-center">
          <div class="step-dot" id="dot-2">2</div>
          <div class="step-label" id="lbl-2">Details</div>
        </div>
        <div class="flex-1 mt-4"><div class="progress-track"><div class="progress-fill" id="line-2" style="width:0%"></div></div></div>
        <div class="flex flex-col items-center">
          <div class="step-dot" id="dot-3">3</div>
          <div class="step-label" id="lbl-3">Account</div>
        </div>
        <div class="flex-1 mt-4"><div class="progress-track"><div class="progress-fill" id="line-3" style="width:0%"></div></div></div>
        <div class="flex flex-col items-center">
          <div class="step-dot" id="dot-4">4</div>
          <div class="step-label" id="lbl-4">Review</div>
        </div>
      </div>
    </div>

    <!-- Form card -->
    <div class="bg-white rounded-2xl shadow-md border border-white/90 p-6 sm:p-8 animate-in" style="animation-delay:0.1s">

      <!-- ── Error messages from PHP ── -->
      <?php if (!empty($errors)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 mb-5 text-sm">
        <p class="font-semibold mb-1">⚠️ Please fix the following:</p>
        <?php foreach ($errors as $err): ?>
          <p class="mt-1">• <?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form id="regForm" action="<?= url('auth/registration_process') ?>" method="post">

        <!-- ══ STEP 1 : ROLE ══ -->
        <div class="step-panel active" id="step-1">
          <h2 class="text-2xl font-bold text-gray-800 mb-1">Select Your Role</h2>
          <p class="text-gray-500 text-sm mb-6">Choose the role that best describes you.</p>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="role-card" onclick="selectRole('patient',this)">
              <div class="check"><i class="fa-solid fa-check"></i></div>
              <div class="role-icon bg-emerald-50"><i class="fa-solid fa-user-injured text-emerald-600"></i></div>
              <p class="role-name font-semibold text-gray-800">Patient</p>
              <p class="role-desc text-gray-400 mt-1">Book & manage appointments</p>
            </div>
            <div class="role-card" onclick="selectRole('doctor',this)">
              <div class="check"><i class="fa-solid fa-check"></i></div>
              <div class="role-icon bg-blue-50"><i class="fa-solid fa-user-doctor text-blue-600"></i></div>
              <p class="role-name font-semibold text-gray-800">Doctor</p>
              <p class="role-desc text-gray-400 mt-1">Manage patients & records</p>
            </div>
            <div class="role-card" onclick="selectRole('admin',this)">
              <div class="check"><i class="fa-solid fa-check"></i></div>
              <div class="role-icon bg-violet-50"><i class="fa-solid fa-user-gear text-violet-600"></i></div>
              <p class="role-name font-semibold text-gray-800">Admin</p>
              <p class="role-desc text-gray-400 mt-1">System & staff management</p>
            </div>
            <div class="role-card" onclick="selectRole('nurse',this)">
              <div class="check"><i class="fa-solid fa-check"></i></div>
              <div class="role-icon bg-pink-50"><i class="fa-solid fa-user-nurse text-pink-500"></i></div>
              <p class="role-name font-semibold text-gray-800">Nurse</p>
              <p class="role-desc text-gray-400 mt-1">Assist & coordinate care</p>
            </div>
          </div>

          <input type="hidden" name="role" id="selectedRole">

          <div class="tip-box mt-6">
            <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>
            <span>Your role determines which features and patient data you can access.</span>
          </div>
        </div>

        <!-- ══ STEP 2 : DETAILS ══ -->
        <div class="step-panel" id="step-2">
          <h2 class="text-2xl font-bold text-gray-800 mb-1">Personal Information</h2>
          <p class="text-gray-500 text-sm mb-6">Fields marked <span class="text-red-500">*</span> are required.</p>

          <div class="section-title">Basic Information</div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
            <div class="field-group">
              <label class="field-label">First Name <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-user input-icon"></i>
                <input type="text" name="first_name" id="first_name" class="field-input" placeholder="e.g. Maria">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Last Name <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-user input-icon"></i>
                <input type="text" name="last_name" id="last_name" class="field-input" placeholder="e.g. Santos">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Date of Birth <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-calendar input-icon"></i>
                <input type="date" name="dob" id="dob" class="field-input">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Gender <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-venus-mars input-icon"></i>
                <select name="gender" id="gender" class="field-input">
                  <option value="">Select gender</option>
                  <option>Male</option><option>Female</option><option>Non-binary</option><option>Prefer not to say</option>
                </select>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Phone Number <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-phone input-icon"></i>
                <input type="tel" name="phone" id="phone" class="field-input" placeholder="+63 912 345 6789">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Nationality</label>
              <div class="input-wrap"><i class="fa-solid fa-flag input-icon"></i>
                <input type="text" name="nationality" class="field-input" placeholder="e.g. Filipino">
              </div>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Complete Address <span class="req">*</span></label>
            <div class="input-wrap"><i class="fa-solid fa-location-dot input-icon"></i>
              <input type="text" name="address" id="address" class="field-input" placeholder="Street, Barangay, City, Province, ZIP">
            </div>
          </div>

          <!-- PATIENT fields -->
          <div class="role-fields" id="fields-patient">
            <div class="section-title"><i class="fa-solid fa-heart-pulse mr-1"></i> Medical Information</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="field-group">
                <label class="field-label">Blood Type</label>
                <div class="input-wrap"><i class="fa-solid fa-droplet input-icon"></i>
                  <select name="blood_type" class="field-input">
                    <option value="">Select blood type</option>
                    <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                    <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Civil Status</label>
                <div class="input-wrap"><i class="fa-solid fa-ring input-icon"></i>
                  <select name="civil_status" class="field-input">
                    <option value="">Select status</option>
                    <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Height (cm)</label>
                <div class="input-wrap"><i class="fa-solid fa-ruler-vertical input-icon"></i>
                  <input type="number" name="height_cm" class="field-input" placeholder="e.g. 165">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Weight (kg)</label>
                <div class="input-wrap"><i class="fa-solid fa-weight-scale input-icon"></i>
                  <input type="number" name="weight_kg" class="field-input" placeholder="e.g. 60">
                </div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Known Allergies</label>
              <div class="input-wrap"><i class="fa-solid fa-triangle-exclamation input-icon"></i>
                <input type="text" name="allergies" class="field-input" placeholder="e.g. Penicillin, Peanuts (or None)">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Existing Medical Conditions</label>
              <div class="input-wrap"><i class="fa-solid fa-notes-medical input-icon"></i>
                <input type="text" name="conditions" class="field-input" placeholder="e.g. Hypertension, Diabetes (or None)">
              </div>
            </div>
            <div class="section-title"><i class="fa-solid fa-person-circle-exclamation mr-1"></i> Emergency Contact</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="field-group">
                <label class="field-label">Contact Name</label>
                <div class="input-wrap"><i class="fa-solid fa-user-shield input-icon"></i>
                  <input type="text" name="emergency_name" class="field-input" placeholder="Full name">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Relationship</label>
                <div class="input-wrap"><i class="fa-solid fa-people-arrows input-icon"></i>
                  <select name="emergency_relation" class="field-input">
                    <option value="">Select relationship</option>
                    <option>Parent</option><option>Spouse</option><option>Sibling</option>
                    <option>Child</option><option>Relative</option><option>Friend</option>
                  </select>
                </div>
              </div>
              <div class="field-group sm:col-span-2">
                <label class="field-label">Emergency Phone</label>
                <div class="input-wrap"><i class="fa-solid fa-phone-volume input-icon"></i>
                  <input type="tel" name="emergency_phone" class="field-input" placeholder="+63 912 345 6789">
                </div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">PhilHealth / Insurance Number</label>
              <div class="input-wrap"><i class="fa-solid fa-id-card input-icon"></i>
                <input type="text" name="insurance_no" class="field-input" placeholder="e.g. PH-123456789">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Insurance Provider</label>
              <div class="input-wrap"><i class="fa-solid fa-building-shield input-icon"></i>
                <input type="text" name="insurance_provider" class="field-input" placeholder="e.g. PhilHealth, Maxicare">
              </div>
            </div>
          </div>

          <!-- DOCTOR fields -->
          <div class="role-fields" id="fields-doctor">
            <div class="section-title"><i class="fa-solid fa-stethoscope mr-1"></i> Professional Information</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="field-group">
                <label class="field-label">PRC License No. <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-id-badge input-icon"></i>
                  <input type="text" name="prc_license" class="field-input" placeholder="e.g. 0123456">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">License Expiry Date <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-calendar-xmark input-icon"></i>
                  <input type="date" name="license_expiry" class="field-input">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Specialization <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-microscope input-icon"></i>
                  <select name="specialization" class="field-input">
                    <option value="">Select specialization</option>
                    <option>General Medicine</option><option>Pediatrics</option><option>Cardiology</option>
                    <option>Dermatology</option><option>Orthopedics</option><option>Neurology</option>
                    <option>OB-GYN</option><option>Psychiatry</option><option>Surgery</option>
                    <option>ENT</option><option>Ophthalmology</option><option>Radiology</option><option>Other</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Years of Experience</label>
                <div class="input-wrap"><i class="fa-solid fa-clock-rotate-left input-icon"></i>
                  <input type="number" name="years_exp" min="0" class="field-input" placeholder="e.g. 5">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Medical School / University <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-graduation-cap input-icon"></i>
                  <input type="text" name="med_school" class="field-input" placeholder="e.g. UP Manila College of Medicine">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Sub-Specialty / Fellowship</label>
                <div class="input-wrap"><i class="fa-solid fa-award input-icon"></i>
                  <input type="text" name="fellowship" class="field-input" placeholder="e.g. Interventional Cardiology">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Consultation Days</label>
                <div class="input-wrap"><i class="fa-solid fa-calendar-days input-icon"></i>
                  <input type="text" name="consult_days" class="field-input" placeholder="e.g. Mon, Wed, Fri">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Consultation Hours</label>
                <div class="input-wrap"><i class="fa-solid fa-clock input-icon"></i>
                  <input type="text" name="consult_hours" class="field-input" placeholder="e.g. 9:00 AM – 5:00 PM">
                </div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Clinic / Hospital Affiliation</label>
              <div class="input-wrap"><i class="fa-solid fa-hospital input-icon"></i>
                <input type="text" name="affiliation" class="field-input" placeholder="e.g. Philippine General Hospital">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Professional Bio / About</label>
              <textarea name="doctor_bio" rows="3" class="field-input no-icon w-full" style="padding-left:16px;resize:vertical;" placeholder="Brief professional background..."></textarea>
            </div>
          </div>

          <!-- ADMIN fields -->
          <div class="role-fields" id="fields-admin">
            <div class="section-title"><i class="fa-solid fa-building mr-1"></i> Administrative Information</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="field-group">
                <label class="field-label">Employee ID <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-id-card-clip input-icon"></i>
                  <input type="text" name="employee_id" class="field-input" placeholder="e.g. EMP-20241001">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Department <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-sitemap input-icon"></i>
                  <select name="department" class="field-input">
                    <option value="">Select department</option>
                    <option>General Administration</option><option>HR & Payroll</option><option>Finance</option>
                    <option>Medical Records</option><option>IT & Systems</option><option>Billing & Insurance</option>
                    <option>Operations</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Job Title <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-briefcase input-icon"></i>
                  <input type="text" name="job_title" class="field-input" placeholder="e.g. Clinic Manager">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Access Level <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-key input-icon"></i>
                  <select name="access_level" class="field-input">
                    <option value="">Select access level</option>
                    <option value="1">Level 1 — View Only</option>
                    <option value="2">Level 2 — Edit Records</option>
                    <option value="3">Level 3 — Full Admin</option>
                    <option value="4">Level 4 — Super Admin</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Date of Hire</label>
                <div class="input-wrap"><i class="fa-solid fa-calendar-plus input-icon"></i>
                  <input type="date" name="hire_date" class="field-input">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Supervisor / Reporting To</label>
                <div class="input-wrap"><i class="fa-solid fa-user-tie input-icon"></i>
                  <input type="text" name="supervisor" class="field-input" placeholder="e.g. Dr. Reyes">
                </div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Admin Authorization Code <span class="req">*</span></label>
              <div class="input-wrap"><i class="fa-solid fa-shield-halved input-icon"></i>
                <input type="text" name="admin_code" class="field-input" placeholder="Enter code provided by your clinic IT officer">
              </div>
            </div>
            <div class="tip-box mt-1">
              <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>
              <span>The authorization code is issued by your clinic's IT administrator.</span>
            </div>
          </div>

          <!-- NURSE fields -->
          <div class="role-fields" id="fields-nurse">
            <div class="section-title"><i class="fa-solid fa-briefcase-medical mr-1"></i> Nursing Information</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
              <div class="field-group">
                <label class="field-label">PRC License No. <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-id-badge input-icon"></i>
                  <input type="text" name="nurse_license" class="field-input" placeholder="e.g. NUR-0123456">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">License Expiry <span class="req">*</span></label>
                <div class="input-wrap"><i class="fa-solid fa-calendar-xmark input-icon"></i>
                  <input type="date" name="nurse_license_expiry" class="field-input">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Years of Experience</label>
                <div class="input-wrap"><i class="fa-solid fa-clock-rotate-left input-icon"></i>
                  <input type="number" name="nurse_exp" min="0" class="field-input" placeholder="e.g. 3">
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Shift Preference</label>
                <div class="input-wrap"><i class="fa-solid fa-sun input-icon"></i>
                  <select name="shift" class="field-input">
                    <option value="">Select shift</option>
                    <option>Morning (6AM–2PM)</option><option>Afternoon (2PM–10PM)</option>
                    <option>Night (10PM–6AM)</option><option>Rotating</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Ward / Department Assignment</label>
                <div class="input-wrap"><i class="fa-solid fa-house-medical input-icon"></i>
                  <select name="ward" class="field-input">
                    <option value="">Select ward/dept</option>
                    <option>Emergency</option><option>ICU/CCU</option><option>Pediatrics</option>
                    <option>OB-GYN</option><option>Surgery</option><option>Oncology</option>
                    <option>General Ward</option><option>Out-Patient</option>
                  </select>
                </div>
              </div>
              <div class="field-group">
                <label class="field-label">Employee ID</label>
                <div class="input-wrap"><i class="fa-solid fa-id-card-clip input-icon"></i>
                  <input type="text" name="nurse_emp_id" class="field-input" placeholder="e.g. NRS-20241001">
                </div>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Supervising Doctor / Head Nurse</label>
              <div class="input-wrap"><i class="fa-solid fa-user-tie input-icon"></i>
                <input type="text" name="nurse_supervisor" class="field-input" placeholder="Name of supervisor">
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Certifications / Trainings</label>
              <div class="input-wrap"><i class="fa-solid fa-certificate input-icon"></i>
                <input type="text" name="nurse_certs" class="field-input" placeholder="e.g. BLS, ACLS, PALS">
              </div>
            </div>
          </div>
        </div>

        <!-- ══ STEP 3 : ACCOUNT ══ -->
        <div class="step-panel" id="step-3">
          <h2 class="text-lg font-bold text-gray-800 mb-1">Account Credentials</h2>
          <p class="text-gray-500 text-sm mb-6">Set up your login email and a strong password.</p>

          <div class="section-title">Login Information</div>
          <div class="field-group">
            <label class="field-label">Email Address <span class="req">*</span></label>
            <div class="input-wrap"><i class="fa-regular fa-envelope input-icon"></i>
              <input type="email" name="email" id="regEmail" class="field-input" placeholder="yourname@email.com">
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Username <span class="req">*</span></label>
            <div class="input-wrap"><i class="fa-solid fa-at input-icon"></i>
              <input type="text" name="username" id="regUsername" class="field-input" placeholder="Choose a unique username">
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Password <span class="req">*</span></label>
            <div class="input-wrap"><i class="fa-solid fa-lock input-icon"></i>
              <input type="password" name="password" id="regPassword" class="field-input" placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
            </div>
            <div class="mt-2">
              <div class="flex gap-1.5">
                <div class="flex-1 bg-gray-100 rounded-full h-1"><div class="strength-bar" id="s1"></div></div>
                <div class="flex-1 bg-gray-100 rounded-full h-1"><div class="strength-bar" id="s2"></div></div>
                <div class="flex-1 bg-gray-100 rounded-full h-1"><div class="strength-bar" id="s3"></div></div>
                <div class="flex-1 bg-gray-100 rounded-full h-1"><div class="strength-bar" id="s4"></div></div>
              </div>
              <p class="text-xs text-gray-400 mt-1.5" id="strengthLabel">Password strength</p>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Confirm Password <span class="req">*</span></label>
            <div class="input-wrap"><i class="fa-solid fa-lock input-icon"></i>
              <input type="password" name="password_confirm" id="regConfirm" class="field-input" placeholder="Re-enter your password" oninput="checkMatch()">
            </div>
            <p class="text-xs mt-1.5 hidden text-red-500" id="matchMsg"><i class="fa-solid fa-circle-xmark mr-1"></i>Passwords do not match</p>
            <p class="text-xs mt-1.5 hidden text-emerald-600" id="matchOk"><i class="fa-solid fa-circle-check mr-1"></i>Passwords match</p>
          </div>

          <div class="section-title">Security</div>
          <div class="field-group">
            <label class="field-label">Security Question</label>
            <div class="input-wrap"><i class="fa-solid fa-shield-halved input-icon"></i>
              <select name="security_q" class="field-input">
                <option value="">Choose a security question</option>
                <option>What is your mother's maiden name?</option>
                <option>What was the name of your first pet?</option>
                <option>What city were you born in?</option>
                <option>What is your childhood nickname?</option>
                <option>What was the make of your first car?</option>
              </select>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Security Answer</label>
            <div class="input-wrap"><i class="fa-solid fa-key input-icon"></i>
              <input type="text" name="security_a" class="field-input" placeholder="Your answer">
            </div>
          </div>

          <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 mt-2">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="agree_terms" id="agreeTerms" class="mt-1 accent-blue-700" style="width:18px;height:18px;flex-shrink:0;">
              <span class="text-sm text-gray-600">
                I agree to the <a href="#" class="text-blue-700 underline underline-offset-2">Terms of Service</a> and
                <a href="#" class="text-blue-700 underline underline-offset-2">Privacy Policy</a>.
                I understand that my health data is handled in accordance with HIPAA standards.
              </span>
            </label>
          </div>
        </div>

        <!-- ══ STEP 4 : REVIEW ══ -->
        <div class="step-panel" id="step-4">
          <h2 class="text-lg font-bold text-gray-800 mb-1">Review Your Information</h2>
          <p class="text-gray-500 text-sm mb-6">Please verify your details before submitting.</p>
          <div id="reviewContent" class="space-y-4"></div>
          <div class="tip-box mt-6">
            <i class="fa-solid fa-circle-info mt-0.5 flex-shrink-0"></i>
            <span>By clicking <strong>Submit Registration</strong>, your account will be created and pending approval by a clinic administrator.</span>
          </div>
        </div>

        <!-- ══ STEP 5 : SUCCESS ══ -->
        <div class="step-panel text-center py-6" id="step-5">
          <div class="success-ring">
            <i class="fa-solid fa-check text-white text-3xl"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2" style="font-family:'Playfair Display',serif;">Registration Submitted!</h2>
          <p class="text-gray-500 text-sm max-w-sm mx-auto mb-6">Your account is under review. A clinic administrator will verify your details shortly.</p>
          <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-semibold px-5 py-2.5 rounded-full mb-6">
            <i class="fa-solid fa-envelope-circle-check"></i> Confirmation email sent
          </div>
          <div class="block">
            <a href="<?= url('auth/login') ?>" class="btn-primary inline-block">
              <i class="fa-solid fa-arrow-right-to-bracket mr-2 opacity-80"></i> Go to Sign In
            </a>
          </div>
        </div>

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-100" id="navButtons">
          <button type="button" class="btn-outline" id="btnBack" onclick="prevStep()" style="display:none">
            <i class="fa-solid fa-arrow-left mr-2 text-xs"></i>Back
          </button>
          <div class="ml-auto flex gap-3">
            <button type="button" class="btn-primary" id="btnNext" onclick="nextStep()">
              Continue <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
            </button>
            <button type="submit" class="btn-primary" id="btnSubmit" style="display:none">
              <i class="fa-solid fa-paper-plane mr-2 text-xs"></i>Submit Registration
            </button>
          </div>
        </div>

      </form>
    </div>

    <div class="text-center mt-6 text-sm text-gray-400 animate-in pb-8" style="animation-delay:0.15s">
      Already have an account? <a href="index.php" class="text-blue-700 font-medium hover:underline">Sign In here</a>
      &nbsp;|&nbsp; &copy; <?= date('Y') ?> ClinicEase. All rights reserved.
    </div>
  </div>

 <script>
    let currentStep = 1;
    let selectedRole = '';
    const totalSteps = 4;

    /* ── Role selection ── */
    function selectRole(role, el) {
      selectedRole = role;
      document.getElementById('selectedRole').value = role;
      document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
      el.classList.add('selected');
    }

    /* ── Show / hide role-specific fields ── */
    function applyRoleFields(role) {
      document.querySelectorAll('.role-fields').forEach(f => f.classList.remove('visible'));
      if (role) {
        const el = document.getElementById('fields-' + role);
        if (el) el.classList.add('visible');
      }
    }

    /* ── JS Validation per step ── */
    function validateStep(step) {
      if (step === 1) {
        if (!selectedRole) {
          alert('Please select a role before continuing.');
          return false;
        }
      }

      if (step === 2) {
        const fields = [
          { id: 'first_name', label: 'First Name' },
          { id: 'last_name',  label: 'Last Name' },
          { id: 'dob',        label: 'Date of Birth' },
          { id: 'gender',     label: 'Gender' },
          { id: 'phone',      label: 'Phone Number' },
          { id: 'address',    label: 'Address' },
        ];
        for (const f of fields) {
          const el = document.getElementById(f.id);
          if (!el || !el.value.trim()) {
            if (el) { el.classList.add('error'); el.focus(); }
            alert(`Please fill in: ${f.label}`);
            return false;
          }
          el.classList.remove('error');
        }
      }

      if (step === 3) {
        const email = document.getElementById('regEmail').value.trim();
        const user  = document.getElementById('regUsername').value.trim();
        const pass  = document.getElementById('regPassword').value;
        const conf  = document.getElementById('regConfirm').value;
        const terms = document.getElementById('agreeTerms').checked;

        if (!email) {
          alert('Email address is required.');
          document.getElementById('regEmail').focus();
          return false;
        }
        if (!user) {
          alert('Username is required.');
          document.getElementById('regUsername').focus();
          return false;
        }
        if (pass.length < 8) {
          alert('Password must be at least 8 characters.');
          document.getElementById('regPassword').focus();
          return false;
        }
        if (pass !== conf) {
          alert('Passwords do not match.');
          document.getElementById('regConfirm').focus();
          return false;
        }
        if (!terms) {
          alert('You must agree to the Terms of Service to continue.');
          return false;
        }
      }

      return true;
    }

    /* ── Step navigation ── */
    function nextStep() {
      if (!validateStep(currentStep)) return;

      if (currentStep === 1) applyRoleFields(selectedRole);

      // Navigate FIRST, then build review so step-4 is active in DOM
      if (currentStep < totalSteps + 1) {
        goToStep(currentStep + 1);
      }

      // currentStep is now updated by goToStep — build review if on step 4
      if (currentStep === totalSteps) {
        buildReview();
      }
    }

    function prevStep() {
      if (currentStep > 1) goToStep(currentStep - 1);
    }

    function goToStep(n) {
      document.getElementById('step-' + currentStep).classList.remove('active');
      currentStep = n;
      document.getElementById('step-' + currentStep).classList.add('active');
      updateTracker();
      updateNavButtons();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateTracker() {
      for (let i = 1; i <= totalSteps; i++) {
        const dot   = document.getElementById('dot-' + i);
        const label = document.getElementById('lbl-' + i);
        dot.classList.remove('active', 'done');
        label.classList.remove('active');

        if (i < currentStep) {
          dot.classList.add('done');
          dot.innerHTML = '<i class="fa-solid fa-check" style="font-size:10px"></i>';
        } else if (i === currentStep) {
          dot.classList.add('active');
          dot.innerHTML = i;
          label.classList.add('active');
        } else {
          dot.innerHTML = i;
        }

        const line = document.getElementById('line-' + i);
        if (line) line.style.width = (i < currentStep ? '100%' : '0%');
      }
    }

    function updateNavButtons() {
      const isLast    = currentStep === totalSteps;
      const isSuccess = currentStep === totalSteps + 1;
      document.getElementById('btnBack').style.display    = (currentStep > 1 && !isSuccess) ? 'inline-flex' : 'none';
      document.getElementById('btnNext').style.display    = (!isLast && !isSuccess) ? 'inline-flex' : 'none';
      document.getElementById('btnSubmit').style.display  = isLast ? 'inline-flex' : 'none';
      document.getElementById('navButtons').style.display = isSuccess ? 'none' : 'flex';
    }

    /* ── Review builder ──
     */
    function buildReview() {
      selectedRole = document.getElementById('selectedRole').value;

      const form = document.getElementById('regForm');
      const data = new FormData(form);
      const roleLabels = { patient: 'Patient', doctor: 'Doctor', admin: 'Administrator', nurse: 'Nurse' };

      const sections = [
        { title: 'Role',              icon: 'fa-user-tag',      fields: ['role'] },
        { title: 'Basic Information', icon: 'fa-user',          fields: ['first_name','last_name','dob','gender','phone','nationality','address'] },
        { title: 'Account',           icon: 'fa-shield-halved', fields: ['email','username'] },
      ];

      const roleFieldMap = {
        patient: {
          title: 'Medical Info', icon: 'fa-heart-pulse',
          fields: ['blood_type','civil_status','height_cm','weight_kg','allergies','conditions',
                   'emergency_name','emergency_relation','emergency_phone','insurance_no','insurance_provider']
        },
        doctor: {
          title: 'Professional Info', icon: 'fa-stethoscope',
          fields: ['prc_license','license_expiry','specialization','years_exp','med_school',
                   'fellowship','consult_days','consult_hours','affiliation']
        },
        admin: {
          title: 'Admin Info', icon: 'fa-building',
          fields: ['employee_id','department','job_title','access_level','hire_date','supervisor']
        },
        nurse: {
          title: 'Nursing Info', icon: 'fa-briefcase-medical',
          fields: ['nurse_license','nurse_license_expiry','nurse_exp','shift','ward',
                   'nurse_emp_id','nurse_supervisor','nurse_certs']
        },
      };

      if (roleFieldMap[selectedRole]) {
        sections.splice(2, 0, roleFieldMap[selectedRole]);
      }

      const prettyLabel = key => key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

      let html = '';
      sections.forEach(sec => {
        let rows = '';
        sec.fields.forEach(f => {
          const v = data.get(f);
          if (v && v.trim() !== '') {
            const display = f === 'role' ? (roleLabels[v] || v) : v;
            rows += `
              <div class="flex justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-500 w-1/2">${prettyLabel(f)}</span>
                <span class="text-sm font-semibold text-gray-700 text-right w-1/2 break-words">${display}</span>
              </div>`;
          }
        });
        if (!rows) return;
        html += `
          <div class="bg-gray-50 rounded-xl p-4 mb-3">
            <div class="flex items-center gap-2 mb-3">
              <i class="fa-solid ${sec.icon} text-blue-700 text-sm"></i>
              <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">${sec.title}</span>
            </div>
            ${rows}
          </div>`;
      });

      document.getElementById('reviewContent').innerHTML =
        html || '<p class="text-gray-400 text-sm">No details to display.</p>';
    }

    /* ── Password strength ── */
    function checkStrength(v) {
      const bars  = ['s1','s2','s3','s4'].map(id => document.getElementById(id));
      const label = document.getElementById('strengthLabel');
      let score = 0;
      if (v.length >= 8)           score++;
      if (/[A-Z]/.test(v))         score++;
      if (/[0-9]/.test(v))         score++;
      if (/[^A-Za-z0-9]/.test(v))  score++;
      const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
      const labels = ['Weak','Fair','Good','Strong'];
      bars.forEach((b, i) => {
        b.style.width      = i < score ? '100%' : '0%';
        b.style.background = i < score ? colors[score - 1] : 'transparent';
      });
      label.textContent = score > 0 ? labels[score - 1] + ' password' : 'Password strength';
      label.style.color = score > 0 ? colors[score - 1] : '#94a3b8';
    }

    /* ── Password match ── */
    function checkMatch() {
      const pw = document.getElementById('regPassword').value;
      const c  = document.getElementById('regConfirm').value;
      document.getElementById('matchMsg').classList.toggle('hidden', pw === c || !c);
      document.getElementById('matchOk').classList.toggle('hidden',  pw !== c || !c);
    }

    /* ── Init ── */
    updateNavButtons();

    /* ── Show success screen if redirected back with ?success=1 ── */
    <?php if ($success): ?>
    window.addEventListener('DOMContentLoaded', function () {
      document.getElementById('step-1').classList.remove('active');
      document.getElementById('step-5').classList.add('active');
      document.getElementById('navButtons').style.display = 'none';
    });
    <?php endif; ?>

    /* ── On PHP error: jump to step 3 so user sees the error box ── */
    <?php if (!empty($errors)): ?>
    window.addEventListener('DOMContentLoaded', function () {
      goToStep(3);
    });
    <?php endif; ?>

  </script>
</body>
</html>