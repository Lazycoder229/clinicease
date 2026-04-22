<aside
  id="sidebar"
  class="fixed top-0 left-0 h-full w-64 bg-slate-900 text-white flex flex-col
         transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 shadow-xl"
>

  <!-- Logo -->
  <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700">
    <div class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-600">
      <i class="fa-solid fa-heart-pulse text-white text-sm"></i>
    </div>
    <span class="text-lg font-semibold tracking-wide">ClinicEase</span>
  </div>

  <!-- Main -->
  <div class="px-6 mt-6 text-xs uppercase text-slate-400 tracking-wider">
    Main Menu
  </div>

  <nav class="flex-1 px-3 mt-3 space-y-1">

    <a href="<?= url('patient/dashboard') ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <i class="fa-solid fa-house-medical w-5"></i>
      Dashboard
    </a>

    <a href="<?= url('patient/appointments') ?>"
       class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <div class="flex items-center gap-3">
        <i class="fa-solid fa-calendar-check w-5"></i>
        Appointments
      </div>
      <span class="text-xs bg-blue-600 px-2 py-0.5 rounded-full">2</span>
    </a>

    <a href="<?= url('patient/records') ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <i class="fa-solid fa-file-medical w-5"></i>
      Medical Records
    </a>

    <a href="<?= url('patient/prescriptions') ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <i class="fa-solid fa-prescription-bottle-medical w-5"></i>
      Prescriptions
    </a>

    <a href="<?= url('patient/labresult') ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <i class="fa-solid fa-flask w-5"></i>
      Lab Results
    </a>
  </nav>

  <!-- Account -->
  <div class="px-6 mt-6 text-xs uppercase text-slate-400 tracking-wider">
    Account
  </div>

  <div class="px-3 mt-3 space-y-1">

    <a href="<?= url('patient/messages') ?>"
       class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <div class="flex items-center gap-3">
        <i class="fa-solid fa-message w-5"></i>
        Messages
      </div>
      <span class="text-xs bg-green-600 px-2 py-0.5 rounded-full">3</span>
    </a>

    <a href="<?= url('patient/profile') ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
      <i class="fa-solid fa-user-circle w-5"></i>
      My Profile
    </a>

  </div>

  <!-- Footer -->
  <div class="mt-auto p-4 border-t border-slate-700">

    <a href="<?= url('/logout') ?>"
       class="flex items-center justify-center gap-2 w-full py-2 rounded-lg
              bg-red-600 hover:bg-red-700 transition text-white">
      <i class="fa-solid fa-right-from-bracket"></i>
      Sign Out
    </a>

  </div>

</aside>