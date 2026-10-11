<?php require_once __DIR__ . '/../PHP/page_auth.php'; requireLoginPage(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../Assets/logo4.png">
  <title>Codefly | Edit Profile</title>
  <link rel="stylesheet" href="../CSS/tailwind.build.css">
  <link rel="stylesheet" href="../CSS/home.css">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <style>
    #toastBox {
      position: fixed;
      bottom: 30px;
      right: 30px;
      display: flex;
      align-items: flex-end;
      flex-direction: column;
      overflow: hidden;
      padding: 20px;
      z-index: 1200;
      pointer-events: none;
    }
    .toast {
      width: 320px;
      min-height: 60px;
      background: rgba(34, 197, 94, 0.15);
      color: #14532d;
      font-weight: 600;
      margin: 12px 0;
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.25);
      border: 1px solid rgba(34, 197, 94, 0.35);
      backdrop-filter: blur(12px);
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 12px;
      max-width: 90vw;
      transform: translateX(100%);
      opacity: 0;
      animation: slideIn 0.25s ease forwards;
      position: relative;
    }
    .toast.error {
      background: rgba(239, 68, 68, 0.18);
      color: #7f1d1d;
      border-color: rgba(239, 68, 68, 0.35);
    }
    .toast.info {
      background: rgba(59, 130, 246, 0.15);
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, 0.3);
    }
    .toast.success::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 100%;
      height: 4px;
      background: #16a34a;
      border-bottom-left-radius: 12px;
      border-bottom-right-radius: 12px;
      animation: fill 3.5s linear forwards;
    }
    @keyframes slideIn { to { transform: translateX(0); opacity: 1; } }
    @keyframes fill { from { width: 100%; } to { width: 0; } }
  </style>
</head>
<body class="bg-gray-100" data-page="edit-profile">
  <div id="toastBox"></div>

  <?php include __DIR__ . '/partials/student_sidebar.php'; ?>

  <section class="home min-h-screen">
    <div class="p-6 flex flex-col lg:flex-row gap-6">

      <aside class="w-full lg:w-72 flex-shrink-0 lg:sticky lg:top-6 self-start">
        <div class="rounded-3xl shadow-lg p-6 flex flex-col gap-6 min-h-[calc(100vh-4rem)] bg-cover bg-center text-white" style="background-image: url('../Assets/settingCoverPic.jpg');">
          <div class="text-center">
            <div class="relative w-28 h-28 mx-auto">
              <img id="profileImage" src="../Assets/StudentProfile.png" alt="Profile" class="w-full h-full rounded-3xl border-4 border-white object-cover shadow">
              <label for="profilePicInput" class="absolute -bottom-2 -right-2 bg-blue-600 text-white p-2 rounded-full cursor-pointer hover:bg-blue-700">
                <i class='bx bx-camera'></i>
              </label>
              <input type="file" id="profilePicInput" accept="image/*" class="hidden">
            </div>
            <h2 id="editProfileDisplayName" class="text-xl font-semibold mt-3 text-gray-900">Pilot Name</h2>
            <p id="editProfileHandle" class="text-sm text-gray-500">@pilot</p>
          </div>
          <div class="space-y-3">
            <button onclick="location.href='../HTML/view-profile.php'" class="w-full px-4 py-2 rounded-xl border border-white/50 text-sm font-semibold text-white/90 hover:bg-white/10">View Profile</button>
            <button onclick="location.href='../HTML/account-setting.php'" class="w-full px-4 py-2 rounded-xl border border-white/50 text-sm font-semibold text-white/90 hover:bg-white/10">Account Settings</button>
            <button type="button" data-delete-account-trigger class="w-full px-4 py-2 rounded-xl bg-red-500/80 text-white text-sm font-semibold hover:bg-red-500/90">Delete Account</button>
          </div>
        </div>
      </aside>

      <div class="flex-1 space-y-6">
        <div class="rounded-3xl bg-white shadow p-6 space-y-6">
          <div class="flex flex-col gap-1">
            <p class="text-sm uppercase tracking-[0.3em] text-gray-400">Profile</p>
            <h1 class="text-2xl font-semibold text-gray-900">Edit profile</h1>
            <p class="text-sm text-gray-500">Update your public-facing information so the squad knows who is flying.</p>
          </div>
          <form id="editProfileForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <label class="text-sm text-gray-600">Full name
                <input type="text" name="fullName" placeholder="Enter full name" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500" required>
              </label>
              <label class="text-sm text-gray-600">Age
                <input type="number" name="age" placeholder="Age" min="13" max="100" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
              </label>
              <label class="text-sm text-gray-600">Gender
                <select name="gender" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                  <option value="">Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="prefer-not">Prefer not to say</option>
                  <option value="other">Other</option>
                </select>
              </label>
              <label class="text-sm text-gray-600">School
                <input type="text" name="school" placeholder="School name" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
              </label>
              <label class="text-sm text-gray-600">Phone
                <input type="tel" name="phone" placeholder="Phone number" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
              </label>
              <label class="text-sm text-gray-600">Location
                <input type="text" name="location" placeholder="City, Country" class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
              </label>
            </div>
            <label class="text-sm text-gray-600 block">Bio
              <textarea name="bio" rows="4" placeholder="Write a short bio..." class="mt-2 w-full rounded-2xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-blue-200 focus:border-blue-500"></textarea>
            </label>
            <p id="editProfileStatus" class="text-sm font-semibold text-gray-600 hidden"></p>
            <div class="flex flex-wrap gap-3">
              <button type="reset" class="px-5 py-3 rounded-full border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</button>
              <button type="submit" class="px-6 py-3 rounded-full bg-blue-600 text-white font-semibold hover:bg-blue-700">Save changes</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </section>

  <script src="../JS/home.js"></script>
  <script src="../JS/popup.js"></script>
  <script src="../JS/profilePages.js"></script>
  <footer class="app-footer" style="width:100%; text-align:center; padding:16px 12px; font-size:12px; color:#64748b;">
    COPYRIGHT &copy; 2022 Universiti Pendidikan Sultan Idris
  </footer></body>
</html>
