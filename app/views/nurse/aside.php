<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="<?= url('public/css/output.css') ?>">
</head>
<body>
    <aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="icon-box">
      <i class="fa-solid fa-heart-pulse" style="color:#fff;font-size:14px;"></i>
    </div>
    <span>ClinicEase</span>
  </div>

  <div class="sidebar-section">Main Menu</div>

  <a href="<?= url('nurse/dashboard') ?>" class="nav-link">
    <i class="fa-solid fa-house-medical"></i> Dashboard
  </a>
  <a href="<?= url('nurse/prescriptions') ?>" class="nav-link">
    <i class="fa-solid fa-prescription-bottle-medical"></i> Prescriptions
    <span class="badge-count">8</span>
  </a>
  <a href="<?= url('nurse/patients') ?>" class="nav-link">
    <i class="fa-solid fa-bed"></i> Patients in Ward
  </a>
  <a href="<?= url('nurse/vitals') ?>" class="nav-link">
    <i class="fa-solid fa-heart"></i> Vital Signs
  </a>
  <a href="<?= url('nurse/reports') ?>" class="nav-link">
    <i class="fa-solid fa-chart-bar"></i> Reports
  </a>

  <div class="sidebar-section">Account</div>

  <!-- <a href="<?= url('nurse/messages') ?>" class="nav-link">
    <i class="fa-solid fa-message"></i> Messages
    <span class="badge-count">5</span>
  </a>
  <a href="<?= url('nurse/profile') ?>" class="nav-link">
    <i class="fa-solid fa-user-circle"></i> My Profile
  </a>
  <a href="<?= url('nurse/settings') ?>" class="nav-link">
    <i class="fa-solid fa-gear"></i> Settings
  </a> -->

  <div class="sidebar-footer">
     <a href="<?= url('/logout') ?>" class="logout-btn">
         <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
   
  </div>

</aside>
</body>
</html>
