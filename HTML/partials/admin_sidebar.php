<?php
// Shared admin sidebar. Included by every admin page so the menu stays identical
// everywhere; the active item is highlighted at runtime by JS/home.js.
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
            <span class="profession">Admin Dashboard</span>
          </div>
        </div>
        <i class="bx bx-chevron-right toggle"></i>
      </header>

      <div class="menu-bar">
        <div class="menu">
          <ul class="menu-links">
            <li class="nav-link"><a href="../HTML/adminDashboard.php"><i class='bx bx-home-alt icon'></i><span class="text nav-text">Admin Center</span></a></li>
            <li class="nav-link"><a href="../HTML/AddModule.php"><i class='bx bx-layer icon'></i><span class="text nav-text">Module</span></a></li>
            <li class="nav-link"><a href="../HTML/workspace.php"><i class='bx bx-code-block icon'></i><span class="text nav-text">Code Block</span></a></li>
            <li class="nav-link"><a href="../HTML/manageUser.php"><i class='bx bx-user icon'></i><span class="text nav-text">Manage Users</span></a></li>
            <li class="nav-link"><a href="../HTML/adminLeaderboard.php"><i class='bx bx-trophy icon'></i><span class="text nav-text">Leaderboard</span></a></li>
          </ul>
      </div>
      <div class="bottom-content">
        <li>
          <a href="../PHP/logout.php">
              <i class="bx bx-log-out icon"></i>
              <span class="text nav-text">Logout</span>
            </a>
          </li>
        </div>
      </div>
    </nav>
