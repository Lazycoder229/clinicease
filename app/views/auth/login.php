<?php
// Session started in helpers.php
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <title>ClinicEase — Patient Portal</title>
    <link rel="stylesheet" href="<?= url('public/css/styles.css') ?>">
  </head>
  <body class="flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div class="card w-full max-w-5xl rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-5">

      <!-- ─── Left Panel ─── -->
      <div class="left-panel md:col-span-2 p-8 sm:p-10 flex flex-col justify-between relative z-10">
        <div>
          <div class="flex items-center gap-3 mb-8">
            <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center shadow">
              <i class="fa-solid fa-plus text-teal-600 text-sm"></i>
            </div>
            <span class="text-white font-bold text-lg tracking-wide">ClinicEase</span>
          </div>

          <div class="badge">
            <i class="fa-solid fa-circle-check text-teal-300 text-[10px]"></i>
            HIPAA Compliant System
          </div>

          <h1 class="brand text-white text-2xl sm:text-3xl font-bold leading-tight mb-3">
            Your Health,<br>Our Priority
          </h1>
          <p class="text-blue-100 text-sm leading-relaxed mb-8 max-w-xs">
            Access your records, schedule appointments, and connect with your care team — all in one place.
          </p>

          <div class="space-y-3">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fa-solid fa-shield-halved text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-white text-[12px] font-semibold leading-tight">Secure & Encrypted</p>
                <span class="text-blue-200 text-sm">End-to-end encrypted medical data</span>
              </div>
            </div>

            <div class="feature-card">
              <div class="feature-icon">
                <i class="fa-solid fa-calendar-check text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-white text-[12px] font-semibold leading-tight">Easy Scheduling</p>
                <span class="text-blue-200 text-sm">Book appointments in seconds</span>
              </div>
            </div>

            <div class="feature-card">
              <div class="feature-icon">
                <i class="fa-solid fa-stethoscope text-teal-300 text-sm"></i>
              </div>
              <div>
                <p class="text-white text-[12px] font-semibold leading-tight">24/7 Care Access</p>
                <span class="text-blue-200 text-sm">Always here for your wellness needs</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Right Panel ─── -->
      <div class="md:col-span-3 p-8 sm:p-12 flex flex-col justify-center bg-white">

        <!-- Header -->
        <div class="mb-8 fade-up">
          <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-4">
            <i class="fa-solid fa-lock text-[10px]"></i>
            Secure Portal
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 leading-tight">Welcome back</h2>
          <p class="text-gray-500 text-sm mt-1">Sign in to access your health dashboard</p>
        </div>

        <!-- ─── Error Messages ─── -->
        <?php if (!empty($_SESSION['login_error'])): ?>
          <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 flex gap-3 items-start mb-5">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-0.5 flex-shrink-0"></i>
            <p class="text-[12px] text-red-700"><?= htmlspecialchars($_SESSION['login_error']) ?></p>
          </div>
          <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['login_errors'])): ?>
          <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 flex gap-3 items-start mb-5">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-0.5 flex-shrink-0"></i>
            <ul class="text-[12px] text-red-700 space-y-1 list-disc list-inside">
              <?php foreach ($_SESSION['login_errors'] as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php unset($_SESSION['login_errors']); ?>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= url('auth/login_process') ?>" method="post" class="space-y-5">

          <div class="fade-up delay-1">
            <label for="username" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Username
            </label>
            <div class="input-wrap">
              <i class="fa-regular fa-user input-icon"></i>
              <input
                type="text"
                name="username"
                id="username"
                required
                placeholder="Enter your username"
                class="input-field"
              >
            </div>
          </div>

          <div class="fade-up delay-2">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">
              Password
            </label>
            <div class="input-wrap">
              <i class="fa-solid fa-lock input-icon"></i>
              <input
                type="password"
                name="password"
                id="password"
                required
                placeholder="Enter your password"
                class="input-field"
              >
            </div>
          </div>

          <div class="flex items-center justify-between text-[14px] fade-up delay-2">
            <label class="flex items-center gap-2.5 text-gray-600 cursor-pointer select-none">
              <input
                type="checkbox"
                name="remember_me"
                id="remember_me"
                class="w-4 h-4 rounded border-gray-300 accent-blue-700 cursor-pointer"
              >
              <span>Keep me signed in</span>
            </label>
            <a href="<?= url('auth/forgot-password') ?>" class="text-blue-700 font-medium hover:underline underline-offset-2">
              Forgot password?
            </a>
          </div>

          <div class="fade-up delay-3">
            <button type="submit" class="btn-signin">
              <i class="fa-solid fa-arrow-right-to-bracket mr-2 opacity-80"></i>
              Sign In to Portal
            </button>
          </div>

          <div class="divider fade-up delay-3 text-gray-800 text-sm font-medium uppercase tracking-widest">or</div>

          <div class="bg-amber-50 border border-amber-100 rounded-xl p-3.5 flex gap-3 items-start fade-up delay-4">
            <i class="fa-solid fa-circle-info text-amber-500 text-sm mt-0.5 flex-shrink-0"></i>
            <p class="text-[12px] text-amber-700 leading-relaxed">
              First time here? Ask your clinic to create an account or&nbsp;
              <a href="<?= url('auth/registration') ?>" class="font-semibold underline underline-offset-2">Register</a>.
            </p>
          </div>

        </form>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 fade-up delay-4">
          <p class="text-xs text-gray-400">
            &copy; <?= date('Y') ?> ClinicEase. All rights reserved.
          </p>
          <div class="flex items-center gap-4 text-xs text-gray-400">
            <a href="#" class="hover:text-gray-600 transition-colors">Privacy Policy</a>
            <span class="text-gray-200">|</span>
            <a href="#" class="hover:text-gray-600 transition-colors">Terms of Use</a>
            <span class="text-gray-200">|</span>
            <a href="#" class="hover:text-gray-600 transition-colors">Support</a>
          </div>
        </div>
      </div>

    </div>
  </body>
</html>