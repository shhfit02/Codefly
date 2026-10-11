<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Codefly | Account Settings</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    .setting-toggle .toggle-track {
      width: 48px;
      height: 24px;
      border-radius: 999px;
      background: #cbd5f5;
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
      outline: 2px solid #2563eb;
      outline-offset: 2px;
    }

    .setting-toggle .toggle-input:checked + .toggle-track {
      background: #2563eb;
    }

    .setting-toggle .toggle-input:checked + .toggle-track .toggle-thumb {
      transform: translateX(24px);
    }
  </style>
</head>
<body class="bg-gray-100" data-page="account-settings">

  <?php include __DIR__ . '/partials/student_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 flex flex-col lg:flex-row gap-6">

      <aside class="w-full lg:w-72 flex-shrink-0 lg:sticky lg:top-6 self-start">
        <div class="rounded-3xl shadow-lg p-6 flex flex-col gap-6 min-h-[calc(100vh-4rem)] bg-cover bg-center text-white" style="background-image: url('../Assets/settingCoverPic.jpg');">
          <div class="text-center">
            <img id="accountProfileImage" src="../Assets/StudentProfile.png" alt="Profile" class="w-24 h-24 mx-auto rounded-2xl border-4 border-white shadow object-cover">
            <h2 id="accountProfileName" class="text-xl font-semibold mt-3 text-gray-900">MOHAMAD SHAH FITRI</h2>
            <p id="accountProfileHandle" class="text-sm text-gray-500">@shah02</p>
          </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div class="stat-pill">
                <p class="stat-pill__label">Lessons</p>
                <p id="accountMissionsValue" class="stat-pill__value">0</p>
              </div>
              <div class="stat-pill">
                <p class="stat-pill__label">Rank</p>
                <div class="flex justify-center">
                  <img
                    id="accountRankBadge"
                    src="../Assets/RankBronze.png"
                    alt="Rank badge"
                    class="w-12 h-12 object-contain"
                  />
                </div>
                <span id="accountRankValue" class="sr-only">Rookie</span>
              </div>
              <div class="stat-pill">
                <p class="stat-pill__label">XP</p>
                <p id="accountXpValue" class="stat-pill__value">0</p>
              </div>
          </div>
          <div class="space-y-2">
            <button onclick="location.href='../HTML/view-profile.php'" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">View Profile</button>
            <button onclick="location.href='../HTML/edit-profile.php'" class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">Edit Profile</button>
            <button type="button" data-delete-account-trigger class="w-full px-4 py-2 rounded-xl bg-red-50 text-red-600 text-sm font-semibold hover:bg-red-100">Delete Account</button>
          </div>
        </div>
      </aside>

      <div class="flex-1 space-y-6">
        <div class="rounded-3xl bg-white shadow p-6 space-y-6 min-h-[320px] flex-1">
          <div class="flex flex-col gap-1">
            <p class="text-sm uppercase tracking-[0.3em] text-gray-400">Account</p>
            <h1 class="text-2xl font-semibold text-gray-900">Account preferences</h1>
            <p class="text-sm text-gray-500">Manage your login identity and security layers.</p>
          </div>
          <form id="accountSettingsForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="text-sm text-gray-600">Email
                <input type="email" name="email" placeholder="name@email.com" class="mt-2 w-full rounded-2xl border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 px-4 py-3" required>
              </label>
              <label class="text-sm text-gray-600">Username
                <input type="text" name="username" placeholder="@shah02" class="mt-2 w-full rounded-2xl border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 px-4 py-3" required>
              </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="text-sm text-gray-600">New password
                <input type="password" name="password" placeholder="********" class="mt-2 w-full rounded-2xl border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 px-4 py-3">
              </label>
              <label class="text-sm text-gray-600">Confirm password
                <input type="password" name="confirmPassword" placeholder="Repeat password" class="mt-2 w-full rounded-2xl border-gray-200 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 px-4 py-3">
              </label>
            </div>
            <div class="rounded-3xl border border-gray-100 bg-gray-50/80 p-5 space-y-4">
              <div class="flex flex-col gap-1">
                <p class="text-sm uppercase tracking-[0.3em] text-gray-400">Notifications</p>
                <h2 class="text-xl font-semibold text-gray-900">Notification bell</h2>
                <p class="text-sm text-gray-500">Updates appear under the bell in the sidebar.</p>
              </div>
              <div class="space-y-4">
                <div class="flex items-center justify-between gap-4 border border-gray-100 bg-white rounded-2xl px-4 py-3">
                  <div>
                    <p class="font-semibold text-gray-900">In-app notifications</p>
                    <p class="text-sm text-gray-500">New modules, quiz results, rank changes and login streaks</p>
                  </div>
                  <label class="setting-toggle inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" id="prefInApp" name="inAppNotifications" class="toggle-input sr-only" aria-label="In-app notifications">
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                  </label>
                </div>
              </div>
            </div>
            <p id="accountSettingsStatus" class="text-sm font-semibold text-gray-600 hidden"></p>
            <div class="flex flex-wrap gap-4 items-center justify-end">
              <button type="submit" class="px-6 py-3 rounded-full bg-blue-600 text-white font-semibold hover:bg-blue-700">Save changes</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </section>

  <script src="../JS/home.js"></script>
  <script src="../JS/profilePages.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
