<?php
// Session started in helpers.php
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
                '0%':   { opacity: '0', transform: 'translateY(18px)' },
                '100%': { opacity: '1', transform: 'translateY(0)' },
              }
            },
            animation: {
              'fu1': 'fadeUp .45s ease .05s both',
              'fu2': 'fadeUp .45s ease .12s both',
              'fu3': 'fadeUp .45s ease .20s both',
              'fu4': 'fadeUp .45s ease .28s both',
              'fu5': 'fadeUp .45s ease .36s both',
            }
          }
        }
      }
    </script>
    <title>ClinicEase — Patient Portal</title>
  </head>

  <body class="font-sans min-h-screen flex items-center justify-center p-2 sm:p-8"
        style="background: radial-gradient(ellipse 70% 55% at 5% 0%,#ccfbf155,transparent 55%), radial-gradient(ellipse 55% 45% at 95% 100%,#0d948820,transparent 55%),#f1f5f9;">

    <!-- ═══ Card ═══ -->
    <div class="w-full max-w-[920px] rounded-3xl overflow-hidden shadow-2xl grid grid-cols-1 md:grid-cols-5"
         style="box-shadow:0 0 0 1px #cbd5e133,0 32px 80px -12px #0f1c2e2a,0 8px 24px -4px #0f1c2e14;
                height:auto;
                height:clamp(540px,82vh,760px);">

      <!-- ────────────────────────────
           LEFT PANEL
      ──────────────────────────────── -->
      <div class="md:col-span-2 relative flex flex-col justify-between p-6 sm:p-11 overflow-hidden"
           style="background:#0f1c2e;">

        <!-- Decorative glows -->
        <div class="pointer-events-none absolute -top-20 -right-20 w-80 h-80 rounded-full"
             style="background:radial-gradient(circle,#0d948830 0%,transparent 70%);"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-16 w-52 h-52 rounded-full"
             style="background:radial-gradient(circle,#0d948820 0%,transparent 70%);"></div>

        <div class="relative z-10">

          <!-- Logo -->
          <div class="flex items-center gap-3 mb-7">
            <div class="w-9 h-9 rounded-xl bg-teal-600 flex items-center justify-center shadow-md flex-shrink-0">
              <i class="fa-solid fa-plus text-white text-sm"></i>
            </div>
            <span class="text-white font-bold text-lg tracking-wide">ClinicEase</span>
          </div>

          <!-- HIPAA badge -->
          <div class="inline-flex items-center gap-1.5 border border-white/20 bg-white/10 text-teal-300 text-[10px] font-semibold tracking-widest px-3 py-1.5 rounded-full mb-6">
            <i class="fa-solid fa-circle-check text-[9px]"></i>
            HIPAA COMPLIANT SYSTEM
          </div>

          <!-- Headline -->
          <h1 class="font-serif text-white text-3xl sm:text-[2.05rem] leading-snug mb-3">
            Your Health,<br><em class="italic text-teal-300">Our Priority.</em>
          </h1>
          <p class="text-slate-400 text-[13px] leading-relaxed max-w-[255px] mb-8">
            Access your records, schedule appointments, and connect with your care team — all in one secure place.
          </p>

          <!-- Feature items -->
          <div class="flex flex-col gap-3">

            <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[.06] px-4 py-3.5 transition hover:bg-white/[.10]">
              <div class="w-9 h-9 rounded-xl bg-teal-900/60 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-shield-halved text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-slate-200 text-xs font-semibold leading-tight">Secure &amp; Encrypted</p>
                <p class="text-slate-500 text-[11.5px] mt-0.5">End-to-end encrypted medical data</p>
              </div>
            </div>

            <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[.06] px-4 py-3.5 transition hover:bg-white/[.10]">
              <div class="w-9 h-9 rounded-xl bg-teal-900/60 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-calendar-check text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-slate-200 text-xs font-semibold leading-tight">Easy Scheduling</p>
                <p class="text-slate-500 text-[11.5px] mt-0.5">Book appointments in seconds</p>
              </div>
            </div>

            <div class="flex items-center gap-3.5 rounded-2xl border border-white/10 bg-white/[.06] px-4 py-3.5 transition hover:bg-white/[.10]">
              <div class="w-9 h-9 rounded-xl bg-teal-900/60 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-stethoscope text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-slate-200 text-xs font-semibold leading-tight">24/7 Care Access</p>
                <p class="text-slate-500 text-[11.5px] mt-0.5">Always here for your wellness needs</p>
              </div>
            </div>

          </div>
        </div>

        <!-- Stats -->
        <div class="relative z-10 hidden md:flex items-start gap-7 pt-7 mt-2 border-t border-white/10">
          <div>
            <p class="text-teal-300 font-bold text-lg leading-none">10k+</p>
            <p class="text-slate-500 text-[10.5px] mt-1">Active Patients</p>
          </div>
          <div>
            <p class="text-teal-300 font-bold text-lg leading-none">99.9%</p>
            <p class="text-slate-500 text-[10.5px] mt-1">Uptime</p>
          </div>
          <div>
            <p class="text-teal-300 font-bold text-lg leading-none">256-bit</p>
            <p class="text-slate-500 text-[10.5px] mt-1">Encryption</p>
          </div>
        </div>
      </div>

      <!-- ────────────────────────────
           RIGHT PANEL
      ──────────────────────────────── -->
      <div class="md:col-span-3 flex flex-col justify-center bg-white px-8 py-10 sm:px-12 sm:py-12">

        <!-- Header -->
        <div class="animate-fu1">
          <div class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[10.5px] font-semibold tracking-widest text-emerald-700 mb-4">
            <i class="fa-solid fa-lock text-[9px]"></i>
            SECURE PORTAL
          </div>
          <h2 class="font-serif text-slate-800 text-[1.85rem] leading-tight">
            Welcome <em class="italic text-teal-600">back</em>
          </h2>
          <p class="text-slate-400 text-sm mt-1.5 mb-7">Sign in to access your health dashboard</p>
        </div>

        <!-- ─── Error: single ─── -->
        <?php if (!empty($_SESSION['login_error'])): ?>
          <div class="flex gap-3 items-start rounded-2xl border border-red-200 bg-red-50 p-3.5 mb-5 animate-fu1">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-px flex-shrink-0"></i>
            <p class="text-[12px] text-red-700 leading-relaxed"><?= htmlspecialchars($_SESSION['login_error']) ?></p>
          </div>
          <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <!-- ─── Error: multiple ─── -->
        <?php if (!empty($_SESSION['login_errors'])): ?>
          <div class="flex gap-3 items-start rounded-2xl border border-red-200 bg-red-50 p-3.5 mb-5 animate-fu1">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-px flex-shrink-0"></i>
            <ul class="text-[12px] text-red-700 space-y-1 list-disc list-inside">
              <?php foreach ($_SESSION['login_errors'] as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php unset($_SESSION['login_errors']); ?>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= url('auth/login_process') ?>" method="post" class="space-y-4">

          <!-- Email -->
          <div class="animate-fu2">
            <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Email</label>
            <!-- wrapper: relative + flex so both icons are truly centred on the input height -->
            <div class="relative flex items-center">
              <span class="pointer-events-none absolute inset-y-0 left-0 w-11 flex items-center justify-center text-slate-400 text-[13px]">
                <i class="fa-regular fa-envelope"></i>
              </span>
              <input
                type="email"
                name="email"
                id="email"
                required
                placeholder="Enter your email address"
                class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-[13.5px] text-slate-800 placeholder-slate-300 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20"
              >
            </div>
          </div>

          <!-- Password -->
          <div class="animate-fu3">
            <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Password</label>
            <div class="relative flex items-center">
              <!-- left icon -->
              <span class="pointer-events-none absolute inset-y-0 left-0 w-11 flex items-center justify-center text-slate-400 text-[13px]">
                <i class="fa-solid fa-lock"></i>
              </span>
              <input
                type="password"
                name="password"
                id="password"
                required
                placeholder="Enter your password"
                class="w-full pl-11 pr-11 py-3 rounded-xl border border-slate-200 bg-slate-50 text-[13.5px] text-slate-800 placeholder-slate-300 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-2 focus:ring-teal-500/20"
              >
              <!-- right toggle icon -->
              <button
                type="button"
                id="passwordToggle"
                onclick="togglePasswordVisibility()"
                class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-slate-400 text-[13px] hover:text-teal-600 transition-colors"
              >
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <!-- Remember me / Forgot password -->
          <div class="flex items-center justify-between pt-0.5 animate-fu3">
            <label class="flex items-center gap-2 text-[13px] text-slate-500 cursor-pointer select-none">
              <input
                type="checkbox"
                name="remember_me"
                id="remember_me"
                class="w-4 h-4 rounded border-slate-300 accent-teal-600 cursor-pointer flex-shrink-0"
              >
              Keep me signed in
            </label>
            <a href="<?= url('auth/forgot-password') ?>" class="text-[13px] font-semibold text-teal-700 hover:underline underline-offset-2">
              Forgot password?
            </a>
          </div>

          <!-- Submit -->
          <div class="pt-1 animate-fu4">
            <button
              type="submit"
              class="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-xl bg-gradient-to-r from-teal-700 to-teal-500 text-white text-sm font-semibold tracking-wide shadow-md shadow-teal-500/25 transition-all duration-200 hover:from-teal-600 hover:to-teal-400 hover:shadow-lg hover:shadow-teal-500/30 active:scale-[.99]"
            >
              <i class="fa-solid fa-arrow-right-to-bracket text-[13px] opacity-85"></i>
              Sign In to Portal
            </button>
          </div>

          <!-- Divider -->
          <div class="flex items-center gap-3 animate-fu4">
            <span class="flex-1 h-px bg-slate-200"></span>
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">or</span>
            <span class="flex-1 h-px bg-slate-200"></span>
          </div>

          <!-- Register notice -->
          <div class="flex gap-3 items-start rounded-2xl border border-amber-100 bg-amber-50 p-3.5 animate-fu5">
            <i class="fa-solid fa-circle-info text-amber-400 text-[13px] mt-px flex-shrink-0"></i>
            <p class="text-[12px] text-amber-800 leading-relaxed">
              First time here? Ask your clinic to create an account or&nbsp;
              <a href="<?= url('auth/registration') ?>" class="font-bold text-teal-700 underline underline-offset-2">Register</a>.
            </p>
          </div>

        </form>

        <!-- Footer -->
        <div class="mt-8 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 animate-fu5">
          <p class="text-[11px] text-slate-300">&copy; <?= date('Y') ?> ClinicEase. All rights reserved.</p>
          <div class="flex items-center gap-3 text-[11px] text-slate-400">
            <a href="#" class="hover:text-teal-600 transition-colors">Privacy Policy</a>
            <span class="text-slate-200">|</span>
            <a href="#" class="hover:text-teal-600 transition-colors">Terms of Use</a>
            <span class="text-slate-200">|</span>
            <a href="#" class="hover:text-teal-600 transition-colors">Support</a>
          </div>
        </div>

      </div>
    </div>

    <script>
      function togglePasswordVisibility() {
        const input  = document.getElementById('password');
        const btn    = document.getElementById('passwordToggle');
        const icon   = btn.querySelector('i');
        const isHide = input.type === 'password';
        input.type   = isHide ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !isHide);
        icon.classList.toggle('fa-eye-slash', isHide);
      }
    </script>
  </body>
</html>