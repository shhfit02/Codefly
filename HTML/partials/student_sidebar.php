<?php
// Shared student sidebar. Included by every signed-in student page so the menu stays
// identical everywhere; the active item is highlighted at runtime by JS/home.js.
// Set $showModeToggle = true before including to show the dark-mode switch (profile page).
if (basename($_SERVER["SCRIPT_FILENAME"]) === basename(__FILE__)) { http_response_code(404); exit; }
?>
  <nav class="sidebar close">
      <header>
        <div class="image-text">
          <span class="image">
            <img src="../Assets/logo4.png" alt="logo" />
          </span>
          <div class="text header-text">
            <span class="name">Codefly</span>
            <span class="profession">Dashboard</span>
          </div>
        </div>
        <i class="bx bx-chevron-right toggle"></i>
      </header>

      <div class="menu-bar">
        <div class="menu">
          <ul class="menu-links">
            <li class="nav-link"><a href="../HTML/home.php"><i class='bx bx-home-alt icon'></i><span class="text nav-text">Home</span></a></li>
            <li class="nav-link"><a href="../HTML/module.php"><i class='bx bx-layer icon'></i><span class="text nav-text">Module</span></a></li>
            <li class="nav-link"><a href="../HTML/workspace.php"><i class='bx bx-code-block icon'></i><span class="text nav-text">Code Block</span></a></li>
            <li class="nav-link"><a href="../HTML/leaderboard.php"><i class='bx bx-trophy icon'></i><span class="text nav-text">Leaderboard</span></a></li>
            <li class="nav-link"><a href="../HTML/account-setting.php"><i class='bx bx-cog icon'></i><span class="text nav-text">Settings</span></a></li>
          </ul>
        </div>
        <div class="bottom-content">
          <li>
            <a href="../PHP/logout.php">
              <i class="bx bx-log-out icon"></i>
              <span class="text nav-text">Logout</span>
            </a>
          </li>
        <?php if (!empty($showModeToggle)): ?>
        <li class="mode">
          <div class="moon-sun">
            <i class='bx bx-moon icon moon'></i>
            <i class='bx bx-sun icon sun'></i>
          </div>
          <span class="mode-text text">Dark mode</span>
          <div class="toggle-switch">
            <span class="switch"></span>
          </div>
        </li>
        <?php endif; ?>
        </div>
      </div>
    </nav>
