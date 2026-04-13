<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="<?= url('public/css/dashboard.css') ?>">
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

  <a href="<?= url('patient/dashboard') ?>" class="nav-link">
    <i class="fa-solid fa-house-medical"></i> Dashboard
  </a>
  <a href="<?= url('patient/appointments') ?>" class="nav-link">
    <i class="fa-solid fa-calendar-check"></i> Appointments
    <span class="badge-count">2</span>
  </a>
  <a href="<?= url('patient/records') ?>" class="nav-link">
    <i class="fa-solid fa-file-medical"></i> Medical Records
  </a>
  <a href="<?= url('patient/prescriptions') ?>" class="nav-link">
    <i class="fa-solid fa-prescription-bottle-medical"></i> Prescriptions
  </a>
  <a href="<?= url('patient/labresult') ?>" class="nav-link">
    <i class="fa-solid fa-flask"></i> Lab Results
  </a>

  <div class="sidebar-section">Account</div>

  <a href="<?= url('patient/messages') ?>" class="nav-link">
    <i class="fa-solid fa-message"></i> Messages
    <span class="badge-count">3</span>
  </a>
  <a href="<?= url('patient/profile') ?>" class="nav-link">
    <i class="fa-solid fa-user-circle"></i> My Profile
  </a>
  <a href="<?= url('patient/settings') ?>" class="nav-link">
    <i class="fa-solid fa-gear"></i> Settings
  </a>

  <div class="sidebar-footer">
     <a href="<?= url('/logout') ?>" class="logout-btn">
         <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
   
  </div>

</aside>
</body>
</html>