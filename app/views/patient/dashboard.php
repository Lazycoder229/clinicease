<?php
// Session already started in helpers.php
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}
$first_name = htmlspecialchars($_SESSION['first_name'] ?? '');
$last_name  = htmlspecialchars($_SESSION['last_name'] ?? '');
$role       = htmlspecialchars($_SESSION['role'] ?? 'patient');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ClinicEase — Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
  
    .app-wrapper {
      height: 100vh;
      width: 100%;
    }
    .main-content {
      min-width: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    @media (min-width: 1024px) {
      .main-content {
        margin-left: 16rem; /* Match fixed sidebar width on desktop */
      }
    }
  </style>
</head>
<body class="h-full">

<div class="app-wrapper">
  <?php include 'aside.php'; ?>

  <div class="overlay fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" id="overlay" onclick="closeSidebar()"></div>

  <main class="main-content lg:ml-64">

    <?php include 'header.php'; ?>

    <div class="p-6 lg:p-10">
      
      
      
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 lg:gap-8 mb-10 animate-in fade-in slide-in-from-bottom-4 duration-500">

          <div class="bg-white border border-slate-200 rounded-2xl p-6 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg shrink-0" style="background:#ccfbf1;color:#0d9488;">
              <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-800">3</div>
              <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Appointments</div>
              <div class="text-[11px] font-bold mt-2 text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-arrow-up"></i> Next: Tomorrow
              </div>
            </div>
          </div>

          <div class="bg-white border border-slate-200 rounded-2xl p-6 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg shrink-0" style="background:#fef3c7;color:#d97706;">
              <i class="fa-solid fa-prescription-bottle-medical"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-800">5</div>
              <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Prescriptions</div>
              <div class="text-[11px] font-bold mt-2 text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-check"></i> All refilled
              </div>
            </div>
          </div>

          <div class="bg-white border border-slate-200 rounded-2xl p-6 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg shrink-0" style="background:#dbeafe;color:#3b82f6;">
              <i class="fa-solid fa-flask"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-800">2</div>
              <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Lab Results</div>
              <div class="text-[11px] font-bold mt-2 text-red-600 flex items-center gap-1">
                <i class="fa-solid fa-clock"></i> Pending
              </div>
            </div>
          </div>

          <div class="bg-white border border-slate-200 rounded-2xl p-6 flex items-start gap-4 shadow-sm hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg shrink-0" style="background:#f3e8ff;color:#a855f7;">
              <i class="fa-solid fa-message"></i>
            </div>
            <div>
              <div class="text-2xl font-bold text-slate-800">3</div>
              <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unread Mail</div>
              <div class="text-[11px] font-bold mt-2 text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-envelope"></i> New message
              </div>
            </div>
          </div>

        </div>

        <div class="h-8 lg:h-12"></div>

        <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-stretch animate-in fade-in slide-in-from-bottom-6 duration-700">

          <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden h-full">
              <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <i class="fa-solid fa-calendar-day text-teal-600"></i> Upcoming Appointments
                </h3>
                <a href="appointments.php" class="text-xs font-bold text-teal-600 hover:underline">View all</a>
              </div>
              <div class="p-6 divide-y divide-slate-100">
                <div class="py-4 first:pt-0 last:pb-0 flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-stethoscope"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800">General Check-up</p>
                    <p class="text-xs text-slate-500 mt-0.5">Tomorrow · 9:00 AM · Dr. Santos</p>
                  </div>
                  <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-600 uppercase">Confirmed</span>
                </div>
                <div class="py-4 last:pb-0 flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-tooth"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800">Dental Cleaning</p>
                    <p class="text-xs text-slate-500 mt-0.5">Feb 25 · 2:00 PM · Dr. Reyes</p>
                  </div>
                  <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 uppercase">Pending</span>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden h-full">
              <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <i class="fa-solid fa-heart-pulse text-red-500"></i> Health Metrics
                </h3>
                <a href="records.php" class="text-xs font-bold text-teal-600 hover:underline">Details</a>
              </div>
              <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6">
                <div class="flex items-center justify-between group">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-50 text-red-500 flex items-center justify-center shrink-0">
                      <i class="fa-solid fa-heart-pulse text-sm"></i>
                    </div>
                    <div>
                      <p class="text-[11px] font-medium text-slate-500 uppercase tracking-tight">Blood Pressure</p>
                      <p class="text-sm font-bold text-slate-800">120 / 80 <span class="font-normal text-slate-400">mmHg</span></p>
                    </div>
                  </div>
                  <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500" style="width: 60%"></div>
                  </div>
                </div>
                <div class="flex items-center justify-between group">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                      <i class="fa-solid fa-droplet text-sm"></i>
                    </div>
                    <div>
                      <p class="text-[11px] font-medium text-slate-500 uppercase tracking-tight">Blood Sugar</p>
                      <p class="text-sm font-bold text-slate-800">98 <span class="font-normal text-slate-400">mg/dL</span></p>
                    </div>
                  </div>
                  <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-teal-600" style="width: 45%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-1">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm h-full overflow-hidden">
              <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <i class="fa-solid fa-history text-blue-500"></i> Recent Activity
                </h3>
              </div>
              <div class="p-6 space-y-6">
                <?php
                $activities = [
                  ['icon'=>'fa-file-medical', 'color'=>'#0d9488','bg'=>'#ccfbf1', 'text'=>'Lab results uploaded', 'sub'=>'Feb 18 · Blood Panel'],
                  ['icon'=>'fa-prescription', 'color'=>'#d97706','bg'=>'#fef3c7', 'text'=>'Prescription renewed', 'sub'=>'Feb 15 · Dr. Santos'],
                  ['icon'=>'fa-calendar-xmark', 'color'=>'#ef4444','bg'=>'#fee2e2', 'text'=>'Rescheduled', 'sub'=>'Feb 12 · Cardiology'],
                ];
                foreach ($activities as $a):
                ?>
                <div class="flex items-start gap-4">
                  <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:<?= $a['bg'] ?>;color:<?= $a['color'] ?>;">
                    <i class="fa-solid <?= $a['icon'] ?> text-xs"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate"><?= $a['text'] ?></p>
                    <p class="text-[11px] text-slate-500 mt-0.5"><?= $a['sub'] ?></p>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>
</div>

<script>
  function toggleSidebar() {
    // Assuming your sidebar has an ID of 'sidebar' in aside.php
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('-translate-x-full'); // Tailwind standard for sidebars
    overlay.classList.toggle('hidden');
  }
  function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
  }
</script>
</body>
</html>