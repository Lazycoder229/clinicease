<header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">

  <div class="flex items-center justify-between px-4 md:px-6 py-3">

    <!-- Left -->
    <div class="flex items-center gap-3">

      <!-- Hamburger -->
      <button
        class="lg:hidden w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 transition"
        onclick="toggleSidebar()"
      >
        <i class="fa-solid fa-bars text-lg text-gray-700"></i>
      </button>

      <!-- Greeting -->
      <div>
        <h2 class="text-base md:text-lg font-semibold text-gray-800">
          Good morning,
          <?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?>
          <?= htmlspecialchars($_SESSION['last_name'] ?? '') ?> 👋
        </h2>

        <p class="text-xs md:text-sm text-gray-500">
          <?= date('l, F j, Y') ?>
        </p>
      </div>

    </div>

    <!-- Right -->
    <div class="flex items-center gap-2 md:gap-3">

      <!-- Search -->
      <button
        class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 transition"
        title="Search"
      >
        <i class="fa-solid fa-magnifying-glass text-gray-600"></i>
      </button>

      <!-- Notifications -->
      <button
        class="relative w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 transition"
        title="Notifications"
      >
        <i class="fa-solid fa-bell text-gray-600"></i>

        <!-- red dot -->
        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
      </button>

    </div>

  </div>

</header>