<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
     <header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="icon-btn hamburger" id="hamburger" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="topbar-left">
        <h2>Good morning, <?= $first_name ?> <?= $last_name ?> 👋</h2>
        <p><?= date('l, F j, Y') ?> • Ward: <?= $ward ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="icon-btn" title="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div class="icon-btn" title="Notifications">
        <i class="fa-solid fa-bell"></i>
        <span class="dot"></span>
      </div>
     
    </div>
  </header>
</body>
</html>
