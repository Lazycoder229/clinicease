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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans:  ['Sora', 'sans-serif'],
            serif: ['Instrument Serif', 'serif'],
          },
          keyframes: {
            fadeUp: {
              '0%':   { opacity: '0', transform: 'translateY(12px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' },
            }
          },
          animation: { 'fu': 'fadeUp .35s ease both' }
        }
      }
    }
  </script>
  <style>
    /* Step panel */
    .step-panel { display:none; animation: fadeUp .3s ease both; }
    .step-panel.active { display:block; }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(10px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* Scrollbar */
    .scroll-area::-webkit-scrollbar { width:4px; }
    .scroll-area::-webkit-scrollbar-track { background:#f1f5f9; border-radius:99px; }
    .scroll-area::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }

    /* Inputs */
    .field-input:focus { outline:none; border-color:#0d9488; box-shadow:0 0 0 3px #0d948818; background:#fff; }

    /* Role card */
    .role-card.selected { border-color:#0d9488!important; background:#f0fdfa!important; box-shadow:0 0 0 3px #0d948820; }
    .role-card.selected .check-icon { opacity:1!important; }

    /* Strength bar */
    .sbar { transition:width .3s,background .3s; height:100%; border-radius:99px; width:0; }

    /* Role fields */
    .role-fields { display:none; }
    .role-fields.visible { display:block; }

    /* Mobile: hide left panel headline/desc on very small screens to save space */
    @media (max-width:767px) {
      .left-headline { display:none; }
    }
  </style>
  <title>ClinicEase — Register</title>
</head>

<!-- Mobile: scrollable page; Desktop: centered fixed card -->
<body class="font-sans bg-slate-100 min-h-screen"
      style="background: radial-gradient(ellipse 70% 55% at 5% 0%,#ccfbf155,transparent 55%), radial-gradient(ellipse 55% 45% at 95% 100%,#0d948820,transparent 55%),#f1f5f9;">

  <!-- ═══ Wrapper — stacks on mobile, side-by-side on md+ ═══ -->
  <div class="w-full md:min-h-screen md:flex md:items-center md:justify-center md:p-6">

    <div class="w-full md:max-w-[1020px] md:rounded-3xl overflow-hidden grid grid-cols-1 md:grid-cols-5"
         style="box-shadow:0 0 0 1px #cbd5e133,0 32px 80px -12px #0f1c2e2a,0 8px 24px -4px #0f1c2e14;
                height:auto; /* mobile: natural height */
                md-height:clamp(540px,82vh,760px);"><!-- overridden below via JS class -->

      <!-- ────────────────────
           LEFT PANEL
      ─────────────────────── -->
      <div class="md:col-span-2 relative flex flex-col p-6 md:p-9 overflow-hidden"
           style="background:#0f1c2e; min-height:0;">

        <!-- Glow blobs -->
        <div class="pointer-events-none absolute -top-20 -right-20 w-72 h-72 rounded-full hidden md:block"
             style="background:radial-gradient(circle,#0d948830 0%,transparent 70%);"></div>
        <div class="pointer-events-none absolute -bottom-14 -left-14 w-48 h-48 rounded-full hidden md:block"
             style="background:radial-gradient(circle,#0d948820 0%,transparent 70%);"></div>

        <div class="relative z-10 flex flex-col h-full">

          <!-- Logo + badge row — always visible -->
          <div class="flex items-center justify-between gap-3 mb-4 md:mb-0 md:block">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-teal-600 flex items-center justify-center shadow-md flex-shrink-0">
                <i class="fa-solid fa-plus text-white text-xs md:text-sm"></i>
              </div>
              <span class="text-white font-bold text-base md:text-lg tracking-wide">ClinicEase</span>
            </div>
            <!-- Mobile: compact step pills -->
            <div class="flex items-center gap-1.5 md:hidden" id="mobilePills">
              <?php for($i=1;$i<=4;$i++): ?>
              <div class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold transition-all duration-300 mobile-pill"
                   id="mpill-<?= $i ?>"
                   style="background:#ffffff12; color:#64748b;">
                <?= $i ?>
              </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Desktop: badge + headline + desc -->
          <div class="left-headline md:mt-6">
            <div class="inline-flex items-center gap-1.5 border border-white/20 bg-white/10 text-teal-300 text-[10px] font-semibold tracking-widest px-3 py-1.5 rounded-full mb-5 w-fit">
              <i class="fa-solid fa-circle-check text-[9px]"></i>
              NEW ACCOUNT REGISTRATION
            </div>
            <h1 class="font-serif text-white text-[1.9rem] leading-snug mb-2">
              Join<br><em class="italic text-teal-300">ClinicEase</em><br>Today.
            </h1>
            <p class="text-slate-400 text-[13px] leading-relaxed max-w-[230px] mb-6">
              Set up your account in a few easy steps and get instant access to your health portal.
            </p>
          </div>

          <!-- Desktop sidebar step tracker -->
          <div class="hidden md:flex flex-col gap-2.5 mt-auto">
            <?php
            $steps = [
              ['fa-user-tag',       'Select Role',   'Choose your access type'],
              ['fa-id-card',        'Your Details',  'Personal & role-specific info'],
              ['fa-lock',           'Credentials',   'Email, username & password'],
              ['fa-clipboard-check','Review',        'Confirm & submit'],
            ];
            foreach ($steps as $i => $s): $n=$i+1; ?>
            <div class="flex items-center gap-3">
              <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-[11px] font-bold transition-all duration-300"
                   id="sdot-<?= $n ?>" style="background:#ffffff12; color:#64748b; border:1.5px solid #ffffff18;">
                <?= $n ?>
              </div>
              <div>
                <p class="text-[11.5px] font-semibold" id="slbl-<?= $n ?>" style="color:#475569;"><?= $s[1] ?></p>
                <p class="text-[10px] text-slate-600"><?= $s[2] ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Sign in link -->
          <div class="hidden md:block mt-5 pt-4 border-t border-white/10">
            <p class="text-[11px] text-slate-500">
              Already have an account?
              <a href="<?= url('auth/login') ?>" class="text-teal-400 font-semibold hover:underline underline-offset-2 ml-1">Sign In</a>
            </p>
          </div>
        </div>
      </div>

      <!-- ────────────────────
           RIGHT PANEL
      ─────────────────────── -->
      <div class="md:col-span-3 bg-white flex flex-col" style="min-height:0;">

        <!-- Top bar -->
        <div class="px-5 md:px-8 pt-5 md:pt-6 pb-4 border-b border-slate-100 flex-shrink-0">
          <div class="flex items-center justify-between mb-2.5">
            <div>
              <p class="text-[10px] font-semibold tracking-widest text-slate-400 uppercase" id="stepLabel">Step 1 of 4</p>
              <h2 class="font-serif text-slate-800 text-xl md:text-2xl leading-tight mt-0.5" id="stepTitle">
                Select Your <em class="italic text-teal-600">Role</em>
              </h2>
            </div>
            <!-- Progress ring -->
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-teal-500"
                 style="background:conic-gradient(#0d9488 0%,#e2e8f0 0%)" id="progressRing">
              <span class="text-[10px] font-bold text-teal-700" id="progressPct">0%</span>
            </div>
          </div>
          <div class="w-full bg-slate-100 rounded-full h-1">
            <div class="h-1 rounded-full transition-all duration-500" style="background:linear-gradient(90deg,#0f766e,#14b8a6);width:0%" id="progressBar"></div>
          </div>
        </div>

        <!-- Scrollable content -->
        <div class="flex-1 overflow-y-auto scroll-area px-5 md:px-8 py-4 md:py-5" id="stepContentArea" style="min-height:0;">

          <?php if (!empty($errors)): ?>
          <div class="flex gap-3 items-start rounded-2xl border border-red-200 bg-red-50 p-3 mb-4">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-px flex-shrink-0"></i>
            <ul class="text-[12px] text-red-700 space-y-0.5 list-disc list-inside">
              <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form id="regForm" action="<?= url('auth/registration_process') ?>" method="post">

            <!-- ══ STEP 1: ROLE ══ -->
            <div class="step-panel active" id="step-1">
              <p class="text-slate-400 text-[12.5px] mb-4">Choose the role that best describes you.</p>
              <div class="grid grid-cols-2 gap-2.5">

                <?php
                $roles = [
                  ['patient','fa-user-injured','emerald','Patient','Book & manage appointments'],
                  ['doctor', 'fa-user-doctor', 'blue',   'Doctor', 'Manage patients & records'],
                  ['admin',  'fa-user-gear',   'violet', 'Admin',  'System & staff management'],
                  ['nurse',  'fa-user-nurse',  'pink',   'Nurse',  'Assist & coordinate care'],
                ];
                foreach ($roles as [$val,$icon,$color,$name,$desc]): ?>
                <div class="role-card relative rounded-2xl border-2 border-slate-200 bg-slate-50 p-3 md:p-4 cursor-pointer transition-all hover:border-teal-400 hover:bg-teal-50/40"
                     onclick="selectRole('<?= $val ?>',this)">
                  <div class="check-icon absolute top-2.5 right-2.5 w-4 h-4 bg-teal-500 rounded-full flex items-center justify-center opacity-0 transition-opacity">
                    <i class="fa-solid fa-check text-white text-[8px]"></i>
                  </div>
                  <div class="w-9 h-9 rounded-xl bg-<?= $color ?>-100 flex items-center justify-center mb-2.5">
                    <i class="fa-solid <?= $icon ?> text-<?= $color ?>-600 text-sm"></i>
                  </div>
                  <p class="text-slate-800 font-semibold text-[13px]"><?= $name ?></p>
                  <p class="text-slate-400 text-[11px] mt-0.5 leading-snug"><?= $desc ?></p>
                </div>
                <?php endforeach; ?>

              </div>
              <input type="hidden" name="role" id="selectedRole">
              <div class="flex gap-2.5 items-start bg-amber-50 border border-amber-100 rounded-xl p-3 mt-4 text-[11.5px] text-amber-800">
                <i class="fa-solid fa-circle-info text-amber-400 text-[12px] mt-px flex-shrink-0"></i>
                Your role determines which features and patient data you can access.
              </div>
            </div>

            <!-- ══ STEP 2: DETAILS ══ -->
            <div class="step-panel" id="step-2">
              <p class="text-slate-400 text-[12.5px] mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

              <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 flex items-center gap-1.5">
                <i class="fa-solid fa-user text-teal-500"></i> Basic Information
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-0">
                <?php
                $basicFields = [
                  ['first_name','text',  'First Name *',  'e.g. Maria',          'fa-user'],
                  ['last_name', 'text',  'Last Name *',   'e.g. Santos',         'fa-user'],
                  ['dob',       'date',  'Date of Birth *','',                   'fa-calendar'],
                  ['gender',    'select','Gender *',       '',                   'fa-venus-mars', ['Male','Female','Non-binary','Prefer not to say']],
                  ['phone',     'tel',   'Phone Number *', '+63 912 345 6789',   'fa-phone'],
                  ['nationality','text', 'Nationality',    'e.g. Filipino',      'fa-flag'],
                ];
                foreach ($basicFields as $f): ?>
                <div class="mb-3">
                  <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                  <div class="relative flex items-center">
                    <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                      <i class="fa-solid <?= $f[4] ?>"></i>
                    </span>
                    <?php if ($f[1]==='select'): ?>
                    <select name="<?= $f[0] ?>" id="<?= $f[0] ?>"
                            class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                      <option value="">Select</option>
                      <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" id="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                           class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="mb-3.5">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1">Complete Address *</label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="fa-solid fa-location-dot"></i>
                  </span>
                  <input type="text" name="address" id="address" placeholder="Street, Barangay, City, Province, ZIP"
                         class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                </div>
              </div>

              <!-- PATIENT fields -->
              <div class="role-fields" id="fields-patient">
                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-heart-pulse text-teal-500"></i> Medical Information
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                  <?php
                  $patientFields = [
                    ['blood_type',  'select','Blood Type',   '','fa-droplet',      ['A+','A-','B+','B-','AB+','AB-','O+','O-']],
                    ['civil_status','select','Civil Status', '','fa-ring',         ['Single','Married','Widowed','Separated']],
                    ['height_cm',   'number','Height (cm)',  'e.g. 165','fa-ruler-vertical'],
                    ['weight_kg',   'number','Weight (kg)',  'e.g. 60', 'fa-weight-scale'],
                  ];
                  foreach ($patientFields as $f): ?>
                  <div class="mb-3">
                    <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                    <div class="relative flex items-center">
                      <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                        <i class="fa-solid <?= $f[4] ?>"></i>
                      </span>
                      <?php if ($f[1]==='select'): ?>
                      <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                        <option value="">Select</option>
                        <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                      </select>
                      <?php else: ?>
                      <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                             class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php foreach ([
                  ['allergies','text','Known Allergies','e.g. Penicillin, Peanuts (or None)','fa-triangle-exclamation'],
                  ['conditions','text','Existing Medical Conditions','e.g. Hypertension, Diabetes (or None)','fa-notes-medical'],
                ] as $f): ?>
                <div class="mb-3">
                  <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                  <div class="relative flex items-center">
                    <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                      <i class="fa-solid <?= $f[4] ?>"></i>
                    </span>
                    <input type="text" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                           class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                  </div>
                </div>
                <?php endforeach; ?>

                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-person-circle-exclamation text-teal-500"></i> Emergency Contact
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                  <?php foreach ([
                    ['emergency_name',    'text',  'Contact Name',             'Full name',                'fa-user-shield'],
                    ['emergency_relation','select','Relationship',              '',                         'fa-people-arrows', ['Parent','Spouse','Sibling','Child','Relative','Friend']],
                    ['emergency_phone',   'tel',   'Emergency Phone',          '+63 912 345 6789',         'fa-phone-volume'],
                    ['insurance_no',      'text',  'PhilHealth / Insurance No.','e.g. PH-123456789',       'fa-id-card'],
                    ['insurance_provider','text',  'Insurance Provider',       'e.g. PhilHealth, Maxicare','fa-building-shield'],
                  ] as $f): ?>
                  <div class="mb-3 <?= $f[0]==='emergency_phone'?'sm:col-span-2':'' ?>">
                    <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                    <div class="relative flex items-center">
                      <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                        <i class="fa-solid <?= $f[4] ?>"></i>
                      </span>
                      <?php if ($f[1]==='select'): ?>
                      <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                        <option value="">Select</option>
                        <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                      </select>
                      <?php else: ?>
                      <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                             class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- DOCTOR fields -->
              <div class="role-fields" id="fields-doctor">
                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-stethoscope text-teal-500"></i> Professional Information
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                  <?php foreach ([
                    ['prc_license',  'text',  'PRC License No. *',         'e.g. 0123456',                   'fa-id-badge'],
                    ['license_expiry','date', 'License Expiry *',           '',                               'fa-calendar-xmark'],
                    ['specialization','select','Specialization *',          '',                               'fa-microscope', ['General Medicine','Pediatrics','Cardiology','Dermatology','Orthopedics','Neurology','OB-GYN','Psychiatry','Surgery','ENT','Ophthalmology','Radiology','Other']],
                    ['years_exp',    'number','Years of Experience',        'e.g. 5',                         'fa-clock-rotate-left'],
                    ['med_school',   'text',  'Medical School *',           'e.g. UP Manila College of Medicine','fa-graduation-cap'],
                    ['fellowship',   'text',  'Sub-Specialty / Fellowship', 'e.g. Interventional Cardiology', 'fa-award'],
                    ['consult_days', 'text',  'Consultation Days',          'e.g. Mon, Wed, Fri',             'fa-calendar-days'],
                    ['consult_hours','text',  'Consultation Hours',         'e.g. 9:00 AM – 5:00 PM',        'fa-clock'],
                    ['affiliation',  'text',  'Hospital Affiliation',       'e.g. Philippine General Hospital','fa-hospital'],
                  ] as $f):
                    $span = in_array($f[0],['med_school','affiliation']) ? 'sm:col-span-2' : '';
                  ?>
                  <div class="mb-3 <?= $span ?>">
                    <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                    <div class="relative flex items-center">
                      <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                        <i class="fa-solid <?= $f[4] ?>"></i>
                      </span>
                      <?php if ($f[1]==='select'): ?>
                      <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                        <option value="">Select</option>
                        <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                      </select>
                      <?php else: ?>
                      <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                             class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="mb-3">
                  <label class="block text-[11.5px] font-semibold text-slate-600 mb-1">Professional Bio</label>
                  <textarea name="doctor_bio" rows="2" placeholder="Brief professional background..."
                            class="field-input w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition resize-y"></textarea>
                </div>
              </div>

              <!-- ADMIN fields -->
              <div class="role-fields" id="fields-admin">
                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-building text-teal-500"></i> Administrative Information
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                  <?php foreach ([
                    ['employee_id', 'text',  'Employee ID *',              'e.g. EMP-20241001',              'fa-id-card-clip'],
                    ['department',  'select','Department *',               '',                               'fa-sitemap', ['General Administration','HR & Payroll','Finance','Medical Records','IT & Systems','Billing & Insurance','Operations']],
                    ['job_title',   'text',  'Job Title *',                'e.g. Clinic Manager',            'fa-briefcase'],
                    ['access_level','select','Access Level *',             '',                               'fa-key', ['Level 1 — View Only','Level 2 — Edit Records','Level 3 — Full Admin','Level 4 — Super Admin']],
                    ['hire_date',   'date',  'Date of Hire',               '',                               'fa-calendar-plus'],
                    ['supervisor',  'text',  'Supervisor / Reporting To',  'e.g. Dr. Reyes',                 'fa-user-tie'],
                    ['admin_code',  'text',  'Admin Authorization Code *', 'Enter code from your IT officer','fa-shield-halved'],
                  ] as $f):
                    $span = $f[0]==='admin_code' ? 'sm:col-span-2' : '';
                  ?>
                  <div class="mb-3 <?= $span ?>">
                    <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                    <div class="relative flex items-center">
                      <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                        <i class="fa-solid <?= $f[4] ?>"></i>
                      </span>
                      <?php if ($f[1]==='select'): ?>
                      <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                        <option value="">Select</option>
                        <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                      </select>
                      <?php else: ?>
                      <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                             class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- NURSE fields -->
              <div class="role-fields" id="fields-nurse">
                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-briefcase-medical text-teal-500"></i> Nursing Information
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3">
                  <?php foreach ([
                    ['nurse_license',       'text',  'PRC License No. *',          'e.g. NUR-0123456',    'fa-id-badge'],
                    ['nurse_license_expiry','date',  'License Expiry *',            '',                    'fa-calendar-xmark'],
                    ['nurse_exp',           'number','Years of Experience',         'e.g. 3',              'fa-clock-rotate-left'],
                    ['shift',               'select','Shift Preference',            '',                    'fa-sun',          ['Morning (6AM–2PM)','Afternoon (2PM–10PM)','Night (10PM–6AM)','Rotating']],
                    ['ward',                'select','Ward / Department',           '',                    'fa-house-medical',['Emergency','ICU/CCU','Pediatrics','OB-GYN','Surgery','Oncology','General Ward','Out-Patient']],
                    ['nurse_emp_id',        'text',  'Employee ID',                 'e.g. NRS-20241001',   'fa-id-card-clip'],
                    ['nurse_supervisor',    'text',  'Supervising Doctor / Head Nurse','Name of supervisor','fa-user-tie'],
                    ['nurse_certs',         'text',  'Certifications / Trainings',  'e.g. BLS, ACLS, PALS','fa-certificate'],
                  ] as $f): ?>
                  <div class="mb-3">
                    <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                    <div class="relative flex items-center">
                      <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                        <i class="fa-solid <?= $f[4] ?>"></i>
                      </span>
                      <?php if ($f[1]==='select'): ?>
                      <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                        <option value="">Select</option>
                        <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                      </select>
                      <?php else: ?>
                      <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[3] ?>"
                             class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- ══ STEP 3: ACCOUNT ══ -->
            <div class="step-panel" id="step-3">
              <p class="text-slate-400 text-[12.5px] mb-4">Set up your login email and a strong password.</p>

              <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 flex items-center gap-1.5">
                <i class="fa-solid fa-at text-teal-500"></i> Login Information
              </p>

              <?php foreach ([
                ['email',   'email','Email Address *',     'yourname@email.com',        'fa-regular fa-envelope','regEmail'],
                ['username','text', 'Username *',          'Choose a unique username',   'fa-solid fa-at',       'regUsername'],
              ] as $f): ?>
              <div class="mb-3">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="<?= $f[4] ?>"></i>
                  </span>
                  <input type="<?= $f[1] ?>" name="<?= $f[0] ?>" id="<?= $f[5] ?>" placeholder="<?= $f[3] ?>"
                         class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Password -->
              <div class="mb-3">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1">Password *</label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="fa-solid fa-lock"></i>
                  </span>
                  <input type="password" name="password" id="regPassword" placeholder="Min. 8 characters"
                         oninput="checkStrength(this.value)"
                         class="field-input w-full pl-8 pr-8 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                  <button type="button" onclick="togglePwd('regPassword','togglePwd1')"
                          id="togglePwd1"
                          class="absolute inset-y-0 right-0 w-8 flex items-center justify-center text-slate-400 hover:text-teal-600 transition-colors text-[11.5px]">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
                <div class="mt-1.5 flex gap-1">
                  <?php for($i=1;$i<=4;$i++): ?>
                  <div class="flex-1 bg-slate-100 rounded-full h-1"><div class="sbar" id="s<?= $i ?>"></div></div>
                  <?php endfor; ?>
                </div>
                <p class="text-[10.5px] text-slate-400 mt-1" id="strengthLabel">Password strength</p>
              </div>

              <!-- Confirm Password -->
              <div class="mb-3">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1">Confirm Password *</label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="fa-solid fa-lock"></i>
                  </span>
                  <input type="password" name="password_confirm" id="regConfirm" placeholder="Re-enter your password"
                         oninput="checkMatch()"
                         class="field-input w-full pl-8 pr-8 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                  <button type="button" onclick="togglePwd('regConfirm','togglePwd2')"
                          id="togglePwd2"
                          class="absolute inset-y-0 right-0 w-8 flex items-center justify-center text-slate-400 hover:text-teal-600 transition-colors text-[11.5px]">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
                <p class="text-[10.5px] mt-1 hidden text-red-500" id="matchMsg"><i class="fa-solid fa-circle-xmark mr-1"></i>Passwords do not match</p>
                <p class="text-[10.5px] mt-1 hidden text-emerald-600" id="matchOk"><i class="fa-solid fa-circle-check mr-1"></i>Passwords match</p>
              </div>

              <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2.5 mt-3 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-halved text-teal-500"></i> Security
              </p>

              <?php foreach ([
                ['security_q','select','Security Question','','fa-shield-halved', ['What is your mother\'s maiden name?','What was the name of your first pet?','What city were you born in?','What is your childhood nickname?','What was the make of your first car?']],
              ] as $f): ?>
              <div class="mb-3">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1"><?= $f[2] ?></label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="fa-solid <?= $f[4] ?>"></i>
                  </span>
                  <select name="<?= $f[0] ?>" class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 transition">
                    <option value="">Choose a security question</option>
                    <?php foreach ($f[5] as $opt): ?><option><?= $opt ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
              <?php endforeach; ?>

              <div class="mb-4">
                <label class="block text-[11.5px] font-semibold text-slate-600 mb-1">Security Answer</label>
                <div class="relative flex items-center">
                  <span class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 text-[11.5px]">
                    <i class="fa-solid fa-key"></i>
                  </span>
                  <input type="text" name="security_a" placeholder="Your answer"
                         class="field-input w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-[12.5px] text-slate-800 placeholder-slate-300 transition">
                </div>
              </div>

              <!-- Terms -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                <label class="flex items-start gap-3 cursor-pointer">
                  <input type="checkbox" name="agree_terms" id="agreeTerms"
                         class="mt-0.5 w-4 h-4 rounded border-slate-300 accent-teal-600 cursor-pointer flex-shrink-0">
                  <span class="text-[12px] text-slate-600 leading-relaxed">
                    I agree to the <a href="#" class="text-teal-700 underline underline-offset-2 font-semibold">Terms of Service</a> and
                    <a href="#" class="text-teal-700 underline underline-offset-2 font-semibold">Privacy Policy</a>.
                    My health data is handled per HIPAA standards.
                  </span>
                </label>
              </div>
            </div>

            <!-- ══ STEP 4: REVIEW ══ -->
            <div class="step-panel" id="step-4">
              <p class="text-slate-400 text-[12.5px] mb-4">Please verify your details before submitting.</p>
              <div id="reviewContent" class="space-y-2.5"></div>
              <div class="flex gap-2.5 items-start bg-amber-50 border border-amber-100 rounded-xl p-3 mt-4 text-[11.5px] text-amber-800">
                <i class="fa-solid fa-circle-info text-amber-400 text-[12px] mt-px flex-shrink-0"></i>
                By clicking <strong class="mx-0.5">Submit Registration</strong>, your account will be pending approval by a clinic administrator.
              </div>
            </div>

            <!-- ══ STEP 5: SUCCESS ══ -->
            <div class="step-panel text-center py-8" id="step-5">
              <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-br from-teal-500 to-emerald-400 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-teal-500/30">
                <i class="fa-solid fa-check text-white text-2xl md:text-3xl"></i>
              </div>
              <h2 class="font-serif text-slate-800 text-xl md:text-2xl mb-2">Registration <em class="italic text-teal-600">Submitted!</em></h2>
              <p class="text-slate-400 text-[12.5px] max-w-xs mx-auto mb-5 leading-relaxed">Your account is under review. A clinic administrator will verify your details shortly.</p>
              <div class="inline-flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11.5px] font-semibold px-4 py-2 rounded-full mb-5">
                <i class="fa-solid fa-envelope-circle-check"></i> Confirmation email sent
              </div>
              <div>
                <a href="<?= url('auth/login') ?>"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-700 to-teal-500 text-white text-sm font-semibold rounded-xl shadow-md shadow-teal-500/25 hover:opacity-90 transition">
                  <i class="fa-solid fa-arrow-right-to-bracket text-[12px]"></i> Go to Sign In
                </a>
              </div>
            </div>

          </form>
        </div>

        <!-- Sticky nav footer -->
        <div class="flex-shrink-0 px-5 md:px-8 py-3 border-t border-slate-100 bg-white flex items-center justify-between" id="navButtons">
          <button type="button" id="btnBack" onclick="prevStep()"
                  class="hidden items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-[13px] font-semibold hover:border-slate-300 hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
          </button>
          <div class="ml-auto flex gap-2.5">
            <button type="button" id="btnNext" onclick="nextStep()"
                    class="flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-teal-700 to-teal-500 text-white text-[13px] font-semibold rounded-xl shadow-md shadow-teal-500/20 hover:opacity-90 transition">
              Continue <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </button>
            <button type="submit" form="regForm" id="btnSubmit"
                    class="hidden items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-teal-700 to-teal-500 text-white text-[13px] font-semibold rounded-xl shadow-md shadow-teal-500/20 hover:opacity-90 transition">
              <i class="fa-solid fa-paper-plane text-[10px]"></i> Submit
            </button>
          </div>
        </div>

      </div><!-- end right panel -->
    </div><!-- end card -->

    <!-- Mobile: sign in link below card -->
    <p class="text-center text-[12px] text-slate-500 mt-4 md:hidden pb-6">
      Already have an account?
      <a href="<?= url('auth/login') ?>" class="text-teal-600 font-semibold hover:underline ml-1">Sign In</a>
      &nbsp;·&nbsp; &copy; <?= date('Y') ?> ClinicEase
    </p>

  </div><!-- end wrapper -->

  <script>
    let currentStep  = 1;
    let selectedRole = '';
    const totalSteps = 4;

    const stepMeta = [
      { label:'Step 1 of 4', title:'Select Your <em class="italic text-teal-600">Role</em>' },
      { label:'Step 2 of 4', title:'Personal <em class="italic text-teal-600">Details</em>' },
      { label:'Step 3 of 4', title:'Account <em class="italic text-teal-600">Credentials</em>' },
      { label:'Step 4 of 4', title:'<em class="italic text-teal-600">Review</em> & Submit' },
    ];

    /* ── Desktop card fixed height ── */
    function applyDesktopHeight() {
      const card = document.querySelector('.md\\:max-w-\\[1020px\\]');
      if (card && window.innerWidth >= 768) {
        card.style.height = 'clamp(520px,80vh,740px)';
      } else if (card) {
        card.style.height = 'auto';
      }
    }
    applyDesktopHeight();
    window.addEventListener('resize', applyDesktopHeight);

    /* ── Role selection ── */
    function selectRole(role, el) {
      selectedRole = role;
      document.getElementById('selectedRole').value = role;
      document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
      el.classList.add('selected');
    }

    function applyRoleFields(role) {
      document.querySelectorAll('.role-fields').forEach(f => f.classList.remove('visible'));
      const el = document.getElementById('fields-' + role);
      if (el) el.classList.add('visible');
    }

    /* ── Validation ── */
    function validateStep(step) {
      if (step === 1) {
        if (!selectedRole) { alert('Please select a role before continuing.'); return false; }
      }
      if (step === 2) {
        for (const id of ['first_name','last_name','dob','gender','phone','address']) {
          const el = document.getElementById(id);
          if (!el || !el.value.trim()) {
            el?.classList.add('!border-red-400'); el?.focus();
            alert('Please fill in all required fields.'); return false;
          }
          el.classList.remove('!border-red-400');
        }
      }
      if (step === 3) {
        const email = document.getElementById('regEmail').value.trim();
        const user  = document.getElementById('regUsername').value.trim();
        const pass  = document.getElementById('regPassword').value;
        const conf  = document.getElementById('regConfirm').value;
        const terms = document.getElementById('agreeTerms').checked;
        if (!email)          { alert('Email address is required.');           document.getElementById('regEmail').focus();    return false; }
        if (!user)           { alert('Username is required.');                document.getElementById('regUsername').focus(); return false; }
        if (pass.length < 8) { alert('Password must be at least 8 chars.');  document.getElementById('regPassword').focus(); return false; }
        if (pass !== conf)   { alert('Passwords do not match.');              document.getElementById('regConfirm').focus(); return false; }
        if (!terms)          { alert('You must agree to the Terms of Service.'); return false; }
      }
      return true;
    }

    /* ── Navigation ── */
    function nextStep() {
      if (!validateStep(currentStep)) return;
      if (currentStep === 1) applyRoleFields(selectedRole);
      if (currentStep < totalSteps + 1) goToStep(currentStep + 1);
      if (currentStep === totalSteps) buildReview();
    }
    function prevStep() { if (currentStep > 1) goToStep(currentStep - 1); }

    function goToStep(n) {
      document.getElementById('step-' + currentStep).classList.remove('active');
      currentStep = n;
      document.getElementById('step-' + currentStep).classList.add('active');
      updateSidebar();
      updateNavButtons();
      updateProgress();
      document.getElementById('stepContentArea').scrollTop = 0;
      // on mobile, scroll the page back to the top of the card
      if (window.innerWidth < 768) window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Sidebar (desktop) + mobile pills ── */
    function updateSidebar() {
      for (let i = 1; i <= totalSteps; i++) {
        const dot  = document.getElementById('sdot-' + i);
        const lbl  = document.getElementById('slbl-' + i);
        const pill = document.getElementById('mpill-' + i);

        if (i < currentStep) {
          if (dot) { dot.style.background='#0d9488'; dot.style.color='#fff'; dot.style.borderColor='#0d9488'; dot.innerHTML='<i class="fa-solid fa-check" style="font-size:8px"></i>'; }
          if (lbl) lbl.style.color='#e2e8f0';
          if (pill){ pill.style.background='#0d9488'; pill.style.color='#fff'; pill.innerHTML='<i class="fa-solid fa-check" style="font-size:8px"></i>'; }
        } else if (i === currentStep) {
          if (dot) { dot.style.background='linear-gradient(135deg,#0f766e,#14b8a6)'; dot.style.color='#fff'; dot.style.borderColor='#0d9488'; dot.innerHTML=i; }
          if (lbl) lbl.style.color='#ffffff';
          if (pill){ pill.style.background='linear-gradient(135deg,#0f766e,#14b8a6)'; pill.style.color='#fff'; pill.innerHTML=i; }
        } else {
          if (dot) { dot.style.background='#ffffff12'; dot.style.color='#475569'; dot.style.borderColor='#ffffff18'; dot.innerHTML=i; }
          if (lbl) lbl.style.color='#475569';
          if (pill){ pill.style.background='#ffffff12'; pill.style.color='#64748b'; pill.innerHTML=i; }
        }
      }
    }

    /* ── Progress ── */
    function updateProgress() {
      const pct = Math.round(((currentStep - 1) / totalSteps) * 100);
      document.getElementById('progressBar').style.width = pct + '%';
      document.getElementById('progressPct').textContent = pct + '%';
      document.getElementById('progressRing').style.background = `conic-gradient(#0d9488 ${pct}%,#e2e8f0 ${pct}%)`;
      if (currentStep <= totalSteps) {
        document.getElementById('stepLabel').textContent = stepMeta[currentStep-1].label;
        document.getElementById('stepTitle').innerHTML   = stepMeta[currentStep-1].title;
      }
    }

    /* ── Nav buttons ── */
    function updateNavButtons() {
      const isLast    = currentStep === totalSteps;
      const isSuccess = currentStep === totalSteps + 1;
      const btnBack   = document.getElementById('btnBack');
      const btnNext   = document.getElementById('btnNext');
      const btnSubmit = document.getElementById('btnSubmit');
      const nav       = document.getElementById('navButtons');

      btnBack.classList.toggle('hidden', currentStep <= 1 || isSuccess);
      btnBack.classList.toggle('flex',   currentStep  > 1 && !isSuccess);
      btnNext.classList.toggle('hidden',   isLast || isSuccess);
      btnNext.classList.toggle('flex',    !isLast && !isSuccess);
      btnSubmit.classList.toggle('hidden', !isLast);
      btnSubmit.classList.toggle('flex',    isLast);
      nav.style.display = isSuccess ? 'none' : 'flex';
    }

    /* ── Review builder ── */
    function buildReview() {
      selectedRole = document.getElementById('selectedRole').value;
      const data  = new FormData(document.getElementById('regForm'));
      const rLbls = { patient:'Patient', doctor:'Doctor', admin:'Administrator', nurse:'Nurse' };
      const pretty = k => k.replace(/_/g,' ').replace(/\b\w/g, c=>c.toUpperCase());

      const sections = [
        { title:'Role',              icon:'fa-user-tag',      fields:['role'] },
        { title:'Basic Information', icon:'fa-user',          fields:['first_name','last_name','dob','gender','phone','nationality','address'] },
        { title:'Account',           icon:'fa-shield-halved', fields:['email','username'] },
      ];
      const roleMap = {
        patient:{ title:'Medical Info',      icon:'fa-heart-pulse',      fields:['blood_type','civil_status','height_cm','weight_kg','allergies','conditions','emergency_name','emergency_relation','emergency_phone','insurance_no','insurance_provider'] },
        doctor: { title:'Professional Info', icon:'fa-stethoscope',      fields:['prc_license','license_expiry','specialization','years_exp','med_school','fellowship','consult_days','consult_hours','affiliation'] },
        admin:  { title:'Admin Info',        icon:'fa-building',         fields:['employee_id','department','job_title','access_level','hire_date','supervisor'] },
        nurse:  { title:'Nursing Info',      icon:'fa-briefcase-medical',fields:['nurse_license','nurse_license_expiry','nurse_exp','shift','ward','nurse_emp_id','nurse_supervisor','nurse_certs'] },
      };
      if (roleMap[selectedRole]) sections.splice(2,0,roleMap[selectedRole]);

      let html = '';
      sections.forEach(sec => {
        let rows = '';
        sec.fields.forEach(f => {
          const v = data.get(f);
          if (v && v.trim()) {
            const display = f==='role' ? (rLbls[v]||v) : v;
            rows += `<div class="flex justify-between py-1.5 border-b border-slate-100 last:border-0">
              <span class="text-[11.5px] text-slate-400 w-1/2">${pretty(f)}</span>
              <span class="text-[11.5px] font-semibold text-slate-700 text-right w-1/2 break-words">${display}</span>
            </div>`;
          }
        });
        if (!rows) return;
        html += `<div class="bg-slate-50 rounded-2xl p-3.5 mb-2.5">
          <div class="flex items-center gap-2 mb-2">
            <i class="fa-solid ${sec.icon} text-teal-500 text-xs"></i>
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">${sec.title}</span>
          </div>${rows}</div>`;
      });
      document.getElementById('reviewContent').innerHTML = html || '<p class="text-slate-400 text-sm">No details to display.</p>';
    }

    /* ── Password strength ── */
    function checkStrength(v) {
      let score = 0;
      if (v.length>=8) score++; if (/[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++; if (/[^A-Za-z0-9]/.test(v)) score++;
      const colors=['#ef4444','#f97316','#eab308','#22c55e'];
      const labels=['Weak','Fair','Good','Strong'];
      for (let i=1;i<=4;i++) {
        const b=document.getElementById('s'+i);
        b.style.width=i<=score?'100%':'0%';
        b.style.background=i<=score?colors[score-1]:'transparent';
      }
      const lbl=document.getElementById('strengthLabel');
      lbl.textContent=score>0?labels[score-1]+' password':'Password strength';
      lbl.style.color=score>0?colors[score-1]:'#94a3b8';
    }

    /* ── Password match ── */
    function checkMatch() {
      const pw=document.getElementById('regPassword').value;
      const c =document.getElementById('regConfirm').value;
      document.getElementById('matchMsg').classList.toggle('hidden', pw===c||!c);
      document.getElementById('matchOk').classList.toggle('hidden',  pw!==c||!c);
    }

    /* ── Password toggle ── */
    function togglePwd(inputId, btnId) {
      const inp=document.getElementById(inputId);
      const icon=document.getElementById(btnId).querySelector('i');
      const hide=inp.type==='password';
      inp.type=hide?'text':'password';
      icon.classList.toggle('fa-eye',      !hide);
      icon.classList.toggle('fa-eye-slash', hide);
    }

    /* ── Init ── */
    updateSidebar(); updateNavButtons(); updateProgress();

    <?php if ($success): ?>
    window.addEventListener('DOMContentLoaded', () => {
      document.getElementById('step-1').classList.remove('active');
      document.getElementById('step-5').classList.add('active');
      document.getElementById('navButtons').style.display='none';
    });
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    window.addEventListener('DOMContentLoaded', () => goToStep(3));
    <?php endif; ?>
  </script>
</body>
</html>