<?php
// Doctor sidebar - included in other pages
// Set active link based on current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="icon-box">
      <i class="fa-solid fa-heart-pulse" style="color:#fff;font-size:14px;"></i>
    </div>
    <span>ClinicEase</span>
  </div>

  <div class="sidebar-section">Main Menu</div>

  <a href="<?= url('doctor/dashboard') ?>" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-house-medical"></i> Dashboard
  </a>
  <a href="<?= url('doctor/prescriptions') ?>" class="nav-link <?= $currentPage === 'prescriptions.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-prescription-bottle-medical"></i> Prescriptions
    <?php if (isset($counts['pending_refills']) && $counts['pending_refills'] > 0): ?>
    <span class="badge-count"><?= $counts['pending_refills'] ?></span>
    <?php endif; ?>
  </a>
  <a href="<?= url('doctor/patients') ?>" class="nav-link <?= $currentPage === 'patients.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-users"></i> My Patients
  </a>
  <a href="<?= url('doctor/appointments') ?>" class="nav-link <?= $currentPage === 'appointments.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-calendar-check"></i> Appointments
  </a>
  <a href="<?= url('doctor/records') ?>" class="nav-link <?= $currentPage === 'records.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-file-medical"></i> Medical Records
  </a>

  <div class="sidebar-section">Account</div>

  <a href="<?= url('doctor/messages') ?>" class="nav-link <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-message"></i> Messages
    <span class="badge-count">3</span>
  </a>
  <a href="<?= url('doctor/profile') ?>" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-user-circle"></i> My Profile
  </a>
  <a href="<?= url('doctor/settings') ?>" class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-gear"></i> Settings
  </a>

  <div class="sidebar-footer">
    <?php if (isset($full_name)): ?>
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(substr($full_name, 0, 1)) ?></div>
      <div><div class="user-name">Dr. <?= htmlspecialchars($full_name) ?></div><div class="user-role">Doctor</div></div>
    </div>
    <?php endif; ?>
    <a href="<?= url('/logout') ?>" class="logout-btn">
      <i class="fa-solid fa-right-from-bracket"></i> Sign Out
    </a>
  </div>

</aside>
