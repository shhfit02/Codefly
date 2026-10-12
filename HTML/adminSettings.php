<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireAdminPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Admin | Settings</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    /* Same hero gradient as the Admin Center. */
    .settings-hero {
      background: linear-gradient(130deg, #0ea5e9, #6366f1);
      color: #fff;
    }

    .settings-input {
      width: 100%;
      margin-top: 0.5rem;
      border-radius: 0.9rem;
      border: 1px solid #e2e8f0;
      padding: 0.7rem 1rem;
      background: #fff;
      color: #0f172a;
    }

    .settings-input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .setting-toggle .toggle-track {
      width: 48px;
      height: 24px;
      border-radius: 999px;
      background: #cbd5e1;
      display: inline-block;
      position: relative;
      transition: background 0.2s ease;
    }

    .setting-toggle .toggle-thumb {
      position: absolute;
      width: 20px;
      height: 20px;
      left: 2px;
      top: 2px;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 2px 5px rgba(15, 23, 42, 0.25);
      transition: transform 0.2s ease;
    }

    .setting-toggle .toggle-input:focus-visible + .toggle-track {
      outline: 2px solid #6366f1;
      outline-offset: 2px;
    }

    .setting-toggle .toggle-input:checked + .toggle-track {
      background: #6366f1;
    }

    .setting-toggle .toggle-input:checked + .toggle-track .toggle-thumb {
      transform: translateX(24px);
    }
  </style>
</head>
<!-- data-page lets JS/profilePages.js load and save this form (same API as the student Settings page). -->
<body class="bg-gray-100" data-page="account-settings">
  <?php include __DIR__ . '/partials/admin_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 w-full max-w-6xl mx-auto flex flex-col gap-6">

      <div class="settings-hero rounded-3xl shadow-lg p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <p class="text-xs uppercase tracking-[0.3em] text-white/70">Account</p>
          <h1 class="text-3xl font-bold">Settings</h1>
          <p class="text-white/80 mt-1">Your admin login details, password and notifications.</p>
        </div>
        <div class="flex items-center gap-4 bg-white/15 border border-white/25 rounded-2xl px-4 py-3 backdrop-blur min-w-0">
          <img id="accountProfileImage" src="<?= htmlspecialchars($sidebarUser['avatar']) ?>" alt="Your profile photo" class="w-14 h-14 rounded-2xl object-cover border-2 border-white/60 shrink-0">
          <div class="min-w-0">
            <p id="accountProfileName" class="font-semibold truncate"><?= htmlspecialchars($sidebarUser['name']) ?></p>
            <p id="accountProfileHandle" class="text-sm text-white/80 truncate">@<?= htmlspecialchars($sidebarUser['username']) ?></p>
            <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-semibold uppercase tracking-wide bg-white/90 text-indigo-700 rounded-full px-2 py-0.5"><i class='bx bx-shield-quarter'></i>Admin</span>
          </div>
        </div>
      </div>

      <form id="accountSettingsForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start" novalidate>
        <div class="lg:col-span-2 flex flex-col gap-6">
          <div class="rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Login</p>
            <h2 class="text-xl font-semibold text-slate-900 mt-1">Login details</h2>
            <p class="text-sm text-slate-500">Your username or email signs you in.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
              <label class="text-sm font-medium text-slate-600">Username
                <input type="text" name="username" autocomplete="username" class="settings-input" required>
              </label>
              <label class="text-sm font-medium text-slate-600">Email
                <input type="email" name="email" autocomplete="email" class="settings-input" required>
              </label>
            </div>
          </div>

          <div class="rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Security</p>
            <h2 class="text-xl font-semibold text-slate-900 mt-1">Change password</h2>
            <p class="text-sm text-slate-500">Leave blank to keep your current password. Changing it signs out "remember me" on other devices.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">
              <label class="text-sm font-medium text-slate-600">New password
                <input type="password" name="password" autocomplete="new-password" minlength="8" placeholder="At least 8 characters" class="settings-input">
              </label>
              <label class="text-sm font-medium text-slate-600">Confirm new password
                <input type="password" name="confirmPassword" autocomplete="new-password" placeholder="Repeat new password" class="settings-input">
              </label>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-6">
          <div class="rounded-2xl bg-white shadow-lg border border-gray-100 p-6">
            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Notifications</p>
            <div class="flex items-center justify-between gap-4 mt-3">
              <div>
                <p class="font-semibold text-slate-900">In-app notifications</p>
                <p class="text-sm text-slate-500">New student sign-ups, under the bell in the sidebar</p>
              </div>
              <label class="setting-toggle inline-flex items-center cursor-pointer shrink-0">
                <input type="checkbox" id="prefInApp" name="inAppNotifications" class="toggle-input sr-only" aria-label="In-app notifications">
                <span class="toggle-track"><span class="toggle-thumb"></span></span>
              </label>
            </div>
          </div>

          <div class="rounded-2xl bg-white shadow-lg border border-gray-100 p-6 flex flex-col gap-4">
            <label class="text-sm font-medium text-slate-600">Current password
              <input type="password" name="currentPassword" autocomplete="current-password" placeholder="Needed to change email or password" class="settings-input">
            </label>
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold text-white shadow-lg hover:-translate-y-0.5 transition" style="background: linear-gradient(130deg, #0ea5e9, #6366f1);">
              <i class='bx bx-save'></i> Save changes
            </button>
            <p id="accountSettingsStatus" class="text-sm font-semibold text-gray-600 hidden" role="status" aria-live="polite"></p>
          </div>
        </div>
      </form>
    </div>
  </section>

  <script src="../JS/home.js"></script>
  <script src="../JS/profilePages.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer>
</body>
</html>
