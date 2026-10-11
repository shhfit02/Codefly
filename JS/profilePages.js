(() => {
  const ENDPOINTS = {
    view: "../PHP/viewProfile.php",
    viewPublic: "../PHP/viewPublicProfile.php",
    edit: "../PHP/editProfile.php",
    account: "../PHP/accountSettings.php",
    media: "../PHP/uploadProfileMedia.php",
  };

  const DEFAULTS = {
    avatar: "../Assets/StudentProfile.png",
    cover: "../Assets/background-Profile.jpg",
    rankBadge: "../Assets/RankBronze.png",
    lessons: 25,
  };

  document.addEventListener("DOMContentLoaded", () => {
    const page = document.body?.dataset?.page;
    if (!page) {
      return;
    }

    switch (page) {
      case "view-profile":
        setupCoverUpload();
        initViewProfile();
        break;
      case "edit-profile":
        setupAvatarUpload();
        initEditProfile();
        break;
      case "account-settings":
        initAccountSettings();
        break;
    }

    initDeleteAccountFlow();
  });

  async function apiRequest(url, options = {}) {
    const fetchOptions = {
      credentials: "include",
      ...options,
    };

    const response = await fetch(url, fetchOptions);
    const contentType = response.headers.get("content-type") || "";
    let payload;

    if (contentType.includes("application/json")) {
      payload = await response.json();
    } else {
      const text = await response.text();
      payload = { message: text };
    }

    if (!response.ok || payload?.status === "error") {
      const message = payload?.message || "Unexpected error occurred.";
      const error = new Error(message);
      if (payload?.errors) {
        error.details = payload.errors;
      }
      throw error;
    }

    return payload;
  }

  function setStatus(element, message, variant = "info") {
    if (!element) {
      return;
    }

    const palette = ["text-gray-600", "text-emerald-600", "text-red-600", "text-blue-600"];
    element.classList.remove("hidden", ...palette);

    if (!message) {
      element.textContent = "";
      element.classList.add("hidden");
      return;
    }

    const variants = {
      info: "text-blue-600",
      success: "text-emerald-600",
      error: "text-red-600",
      muted: "text-gray-600",
    };

    const nextClass = variants[variant] || variants.muted;
    palette.concat(["hidden"]).forEach((cls) => element.classList.remove(cls));
    element.classList.add(nextClass);
    element.textContent = message;
  }

  function setTextContent(id, value) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = value ?? "";
    }
  }

  function setImageSource(id, value) {
    const el = document.getElementById(id);
    if (el && value) {
      el.src = value;
    }
  }

  function displayRankTitle(rawTitle) {
    const normalized = (rawTitle || "").toLowerCase();
    const map = {
      bronze: "Rookie",
      silver: "Operator",
      gold: "Specialist",
      ace: "Elite Pilot",
      captain: "Commander",
      commander: "Grand Commander",
    };
    return map[normalized] || rawTitle || "Rookie";
  }

  function cacheBust(url) {
    if (!url) {
      return url;
    }
    const token = `ts=${Date.now()}`;
    return url.includes("?") ? `${url}&${token}` : `${url}?${token}`;
  }

  function getRequestedIdentity() {
    const params = new URLSearchParams(window.location.search);
    const user = (params.get("user") || "").trim();
    const id = (params.get("id") || "").trim();
    return { user, id };
  }

  function readLeaderboardSnapshot(requestedUser) {
    if (!requestedUser) {
      return null;
    }
    try {
      const raw = sessionStorage.getItem("selectedPilot");
      if (!raw) {
        return null;
      }
      const parsed = JSON.parse(raw);
      if (!parsed?.username) {
        return null;
      }
      if (parsed.username.toLowerCase() !== requestedUser.toLowerCase()) {
        return null;
      }
      return parsed;
    } catch (error) {
      console.warn("Unable to read pilot snapshot", error);
      return null;
    }
  }

  function snapshotToProfileData(pilot = {}) {
    const xp = Number(pilot.xp ?? pilot.score ?? 0) || 0;
    const lessonCount = Number(pilot.lessons);
    const lessons = Number.isFinite(lessonCount) ? lessonCount : DEFAULTS.lessons;
    const rankTitle = displayRankTitle(pilot.rank || "Bronze");
    const rankStatus = pilot.status || "Not Eligible";
    return {
      profile: {
        fullName: pilot.username || "Pilot",
        avatar: pilot.avatar || DEFAULTS.avatar,
        bio: [rankTitle, rankStatus].filter(Boolean).join(" \u00b7 "),
      },
      account: { username: pilot.username || "" },
      stats: { xp, missionsCompleted: lessons },
      rank: {
        title: rankTitle,
        badge: pilot.badge || DEFAULTS.rankBadge,
        status: rankStatus,
      },
    };
  }

  function toggleOwnerControls(isOwner) {
    const editBtn = document.querySelector("button[onclick*='edit-profile.php']");
    if (editBtn) {
      editBtn.classList.toggle("hidden", !isOwner);
    }
    const coverBtn = document.getElementById("uploadCoverBtn");
    if (coverBtn) {
      coverBtn.classList.toggle("hidden", !isOwner);
    }
    const coverInput = document.getElementById("coverInput");
    if (coverInput) {
      coverInput.disabled = !isOwner;
    }
  }

  async function initViewProfile() {
    const statusEl = document.getElementById("viewProfileStatus");

    const { user: requestedUser, id: requestedId } = getRequestedIdentity();

    if (requestedUser || requestedId) {
      const snapshot = requestedUser ? readLeaderboardSnapshot(requestedUser) : null;
      if (snapshot) {
        populateViewProfile(snapshotToProfileData(snapshot), { isOwner: false });
      }
      setStatus(statusEl, snapshot ? "" : "Loading profile...", "info");
      try {
        const queryParam = requestedId
          ? `id=${encodeURIComponent(requestedId)}`
          : `user=${encodeURIComponent(requestedUser)}`;
        const payload = await apiRequest(`${ENDPOINTS.viewPublic}?${queryParam}`);
        const data = payload.data || payload;
        populateViewProfile(data, { isOwner: Boolean(data.isOwner) });
        setStatus(statusEl, "");
      } catch (error) {
        if (!snapshot) {
          setStatus(statusEl, buildErrorMessage(error), "error");
        }
      }
      return;
    }

    try {
      const payload = await apiRequest(ENDPOINTS.view);
      populateViewProfile(payload.data || payload, { isOwner: true });
      setStatus(statusEl, "");
    } catch (error) {
      setStatus(statusEl, buildErrorMessage(error), "error");
    }
  }

  function populateViewProfile(data, options = {}) {
    const isOwner = options.isOwner !== false;
    const profile = data.profile || {};
    const account = data.account || {};
    const stats = data.stats || {};
    const rank = data.rank || {};

    const fullName = profile.fullName || account.username || "Pilot";
    const username = account.username ? `@${account.username}` : "@pilot";
    const location = profile.location ? ` \u00b7 ${profile.location}` : "";

    setTextContent("viewProfileFullName", fullName);
    setTextContent("viewProfileIdentity", `${username}${location}`);

    const taglineParts = [];
    if (profile.school) {
      taglineParts.push(profile.school);
    }
    if (profile.bio) {
      taglineParts.push(profile.bio);
    }
    const tagline = taglineParts.join(" \u00b7 ") || "Share your journey from the Edit Profile page.";
    setTextContent("viewProfileTagline", tagline);

    setImageSource("viewProfileAvatar", profile.avatar || DEFAULTS.avatar);
    setImageSource("coverImage", profile.cover || DEFAULTS.cover);

    const displayRank = displayRankTitle(rank.title);
    setImageSource("viewRankBadge", rank.badge || DEFAULTS.rankBadge);
    const badgeEl = document.getElementById("viewRankBadge");
    if (badgeEl) {
      badgeEl.alt = `${displayRank || "Rookie"} badge`;
    }

    setTextContent("viewDetailFullName", fullName);
    setTextContent("viewDetailAge", profile.age ? `${profile.age}` : "--");
    setTextContent("viewDetailGender", formatGender(profile.gender));
    setTextContent("viewDetailSchool", profile.school || "--");
    setTextContent("viewDetailPhone", profile.phone || "--");
    setTextContent("viewDetailBio", profile.bio || "Add a short bio to introduce yourself.");

    const missionsCompleted =
      typeof rank.lessons === "number"
        ? rank.lessons
        : typeof stats.missionsCompleted === "number"
        ? stats.missionsCompleted
        : stats.flightHours ?? 0;
    setTextContent("viewFlightHours", missionsCompleted);
    const xpValue =
      typeof rank.score === "number"
        ? rank.score
        : typeof stats.xp === "number"
        ? stats.xp
        : typeof stats.lessonScore === "number"
        ? stats.lessonScore
        : 0;
    setTextContent("viewMissionScore", xpValue);
    setTextContent("viewCertification", stats.certification || "Cadet Pilot");

    toggleOwnerControls(isOwner);
  }

  function formatGender(value) {
    switch (value) {
      case "male":
        return "Male";
      case "female":
        return "Female";
      case "prefer-not":
        return "Prefer not to say";
      case "other":
        return "Other";
      default:
        return "--";
    }
  }

  async function initEditProfile() {
    const form = document.getElementById("editProfileForm");
    const statusEl = document.getElementById("editProfileStatus");

    if (!form) {
      return;
    }

    setStatus(statusEl, "Loading profile...", "info");

    try {
      const payload = await apiRequest(ENDPOINTS.edit);
      fillProfileForm(form, payload.data || payload);
      setStatus(statusEl, "Profile loaded. Make your updates.", "muted");
    } catch (error) {
      setStatus(statusEl, buildErrorMessage(error), "error");
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      setStatus(statusEl, "Saving changes...", "info");

      const formData = new FormData(form);

      try {
        const payload = await apiRequest(ENDPOINTS.edit, {
          method: "POST",
          body: formData,
        });
        setStatus(statusEl, payload.message || "Profile updated successfully.", "success");
        if (typeof showToast === "function") {
          showToast("Profile updated successfully.", "success");
        }
      } catch (error) {
        setStatus(statusEl, buildErrorMessage(error), "error");
        if (typeof showToast === "function") {
          showToast(buildErrorMessage(error), "error");
        }
      }
    });

  }

  function fillProfileForm(form, data) {
    const profileData = data.profile || data;

    const fields = {
      fullName: profileData.fullName || "",
      age: profileData.age || "",
      gender: profileData.gender || "",
      school: profileData.school || "",
      phone: profileData.phone || "",
      location: profileData.location || "",
      bio: profileData.bio || "",
    };

    Object.entries(fields).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);
      if (!field) {
        return;
      }

      if (field instanceof HTMLSelectElement) {
        field.value = value || "";
      } else if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        field.value = value ?? "";
      }
    });

    const avatarPreview = document.getElementById("profileImage");
    if (avatarPreview) {
      avatarPreview.src = profileData.avatar || DEFAULTS.avatar;
    }

    const accountRankEl = document.getElementById("accountRankValue");
    if (accountRankEl) {
      accountRankEl.textContent = displayRankTitle(profileData.rank?.title) || "Rookie";
    }
    const accountRankBadgeEl = document.getElementById("accountRankBadge");
    if (accountRankBadgeEl) {
      accountRankBadgeEl.src = profileData.rank?.badge || DEFAULTS.rankBadge;
      accountRankBadgeEl.alt = `${displayRankTitle(profileData.rank?.title) || "Rookie"} badge`;
    }

    const displayNameEl = document.getElementById("editProfileDisplayName");
    if (displayNameEl) {
      displayNameEl.textContent =
        profileData.fullName || data.username || "Pilot";
    }

    const handleEl = document.getElementById("editProfileHandle");
    if (handleEl) {
      const handleSource = data.username || "";
      handleEl.textContent = handleSource ? `@${handleSource}` : "@pilot";
    }
  }

  async function initAccountSettings() {
    const form = document.getElementById("accountSettingsForm");
    const statusEl = document.getElementById("accountSettingsStatus");

    if (!form) {
      return;
    }

    setStatus(statusEl, "Loading account settings...", "info");

    try {
      const payload = await apiRequest(ENDPOINTS.account);
      fillAccountForm(form, payload.data || payload);
      setStatus(statusEl, "Account details synced.", "muted");
    } catch (error) {
      setStatus(statusEl, buildErrorMessage(error), "error");
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      setStatus(statusEl, "Saving account changes...", "info");

      const formData = new FormData(form);

      try {
        const payload = await apiRequest(ENDPOINTS.account, {
          method: "POST",
          body: formData,
        });
        if (form.elements.password) {
          form.elements.password.value = "";
        }
        if (form.elements.confirmPassword) {
          form.elements.confirmPassword.value = "";
        }
        setStatus(statusEl, payload.message || "Account updated successfully.", "success");
      } catch (error) {
        setStatus(statusEl, buildErrorMessage(error), "error");
      }
    });
  }

  function fillAccountForm(form, data) {
    if (form.elements.email) {
      form.elements.email.value = data.email || "";
    }
    if (form.elements.username) {
      form.elements.username.value = data.username || "";
    }

    ["twoFactor", "emailAlerts", "inAppNotifications", "smsAlerts"].forEach((name) => {
      const input = form.elements.namedItem(name);
      if (input instanceof HTMLInputElement && input.type === "checkbox") {
        input.checked = Boolean(data[name]);
      }
    });

    const nameEl = document.getElementById("accountProfileName");
    if (nameEl) {
      nameEl.textContent = data.fullName || data.username || "Pilot";
    }

    const handleEl = document.getElementById("accountProfileHandle");
    if (handleEl && data.username) {
      handleEl.textContent = `@${data.username}`;
    }

    const avatarEl = document.getElementById("accountProfileImage");
    if (avatarEl) {
      avatarEl.src = data.avatar || DEFAULTS.avatar;
    }

    const rankEl = document.getElementById("accountRankValue");
    if (rankEl) {
      rankEl.textContent = displayRankTitle(data.rank?.title) || "Rookie";
    }
    const rankBadgeEl = document.getElementById("accountRankBadge");
    if (rankBadgeEl) {
      rankBadgeEl.src = data.rank?.badge || DEFAULTS.rankBadge;
      rankBadgeEl.alt = `${displayRankTitle(data.rank?.title) || "Rookie"} badge`;
    }

    const lessonsEl = document.getElementById("accountMissionsValue");
    if (lessonsEl) {
      const lessons = typeof data.lessons === "number" ? data.lessons : DEFAULTS.lessons;
      lessonsEl.textContent = lessons.toLocaleString();
    }

    const xpEl = document.getElementById("accountXpValue");
    if (xpEl && typeof data.xp === "number") {
      xpEl.textContent = data.xp.toLocaleString();
    }
  }

  function initDeleteAccountFlow() {
    const triggers = document.querySelectorAll("[data-delete-account-trigger]");
    if (!triggers.length) {
      return;
    }

    const modal = ensureDeleteAccountModal();
    const input = modal.querySelector("[data-delete-input]");
    const errorEl = modal.querySelector("[data-delete-error]");
    const confirmBtn = modal.querySelector("[data-delete-confirm]");
    const cancelButtons = modal.querySelectorAll("[data-delete-close]");

    const closeModal = () => {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      input.value = "";
      if (errorEl) {
        errorEl.textContent = "";
        errorEl.classList.add("hidden");
        errorEl.classList.remove("text-emerald-600");
        errorEl.classList.add("text-red-600");
      }
      confirmBtn.disabled = false;
      confirmBtn.textContent = "Delete permanently";
    };

    cancelButtons.forEach((btn) =>
      btn.addEventListener("click", closeModal)
    );

    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        input.value = "";
        if (errorEl) {
          errorEl.textContent = "";
          errorEl.classList.add("hidden");
          errorEl.classList.remove("text-emerald-600");
          errorEl.classList.add("text-red-600");
        }
        confirmBtn.disabled = false;
        confirmBtn.textContent = "Delete permanently";
        setTimeout(() => input.focus(), 50);
      });
    });

    confirmBtn.addEventListener("click", () => {
      const value = input.value.trim().toUpperCase();
      if (value !== "DELETE") {
        errorEl.textContent = 'Type "DELETE" to confirm.';
        errorEl.classList.remove("hidden");
        errorEl.classList.remove("text-emerald-600");
        errorEl.classList.add("text-red-600");
        return;
      }

      confirmBtn.disabled = true;
      confirmBtn.textContent = "Deleting...";
      errorEl.classList.add("hidden");

      fetch("../PHP/deleteAccount.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ confirm: "DELETE" }),
        credentials: "include",
      })
        .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
          if (!ok || data?.status !== "success") {
            throw new Error(data?.message || "Unable to delete account.");
          }

          if (errorEl) {
            errorEl.textContent = data.message || "Account deleted. Redirecting...";
            errorEl.classList.remove("hidden", "text-red-600");
            errorEl.classList.add("text-emerald-600");
          }

          setTimeout(() => {
            window.location.href = "../index.html";
          }, 1500);
        })
        .catch((error) => {
          errorEl.textContent = error?.message || "Failed to delete account.";
          errorEl.classList.remove("hidden");
          errorEl.classList.remove("text-emerald-600");
          errorEl.classList.add("text-red-600");
          confirmBtn.disabled = false;
          confirmBtn.textContent = "Delete permanently";
        });
    });
  }

  function ensureDeleteAccountModal() {
    let modal = document.getElementById("deleteAccountModal");
    if (modal) {
      return modal;
    }

    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
      <div id="deleteAccountModal" class="fixed inset-0 z-[140] hidden items-center justify-center bg-slate-900/70 px-6">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-5 text-center">
          <button type="button" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600" data-delete-close aria-label="Close delete account modal">
            <i class='bx bx-x text-2xl'></i>
          </button>
          <div class="w-16 h-16 mx-auto rounded-full bg-red-100 text-red-500 flex items-center justify-center text-3xl">
            <i class='bx bx-error'></i>
          </div>
          <div>
            <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Danger zone</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-1">Delete your account?</h3>
            <p class="text-sm text-slate-600 mt-2">This action removes all of your Codefly data permanently. Type <strong>DELETE</strong> in the field below to confirm.</p>
          </div>
          <input type="text" data-delete-input placeholder='Type "DELETE" to confirm' class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-center uppercase tracking-widest focus:ring-2 focus:ring-red-200 focus:border-red-400" autocomplete="off">
          <p data-delete-error class="text-sm text-red-600 hidden"></p>
          <div class="flex flex-col gap-3">
            <button type="button" data-delete-confirm class="px-5 py-3 rounded-full bg-red-600 text-white font-semibold hover:bg-red-700 transition">Delete permanently</button>
            <button type="button" data-delete-close class="px-5 py-3 rounded-full border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition">Cancel</button>
          </div>
        </div>
      </div>
    `;

    modal = wrapper.firstElementChild;
    document.body.appendChild(modal);
    return modal;
  }

  function buildErrorMessage(error) {
    const base = error?.message || "Something went wrong.";
    if (error?.details?.length) {
      return `${base} (${error.details.join("; ")})`;
    }
    return base;
  }

  function setupCoverUpload() {
    setupUploadHandler({
      inputId: "coverInput",
      triggerId: "uploadCoverBtn",
      type: "cover",
      statusElement: document.getElementById("viewProfileStatus"),
      previewElement: document.getElementById("coverImage"),
    });
  }

  function setupAvatarUpload() {
    setupUploadHandler({
      inputId: "profilePicInput",
      type: "avatar",
      statusElement: document.getElementById("editProfileStatus"),
      previewElement: document.getElementById("profileImage"),
    });
  }

  function setupUploadHandler({ inputId, triggerId, type, statusElement, previewElement }) {
    const input = typeof inputId === "string" ? document.getElementById(inputId) : inputId;
    if (!input) {
      return;
    }

    if (triggerId) {
      const trigger = document.getElementById(triggerId);
      if (trigger) {
        trigger.addEventListener("click", () => input.click());
      }
    }

    input.addEventListener("change", async () => {
      if (!input.files || input.files.length === 0) {
        return;
      }

      const file = input.files[0];
      const formData = new FormData();
      formData.append("type", type);
      formData.append("file", file);

      setStatus(statusElement, `Uploading ${type}...`, "info");

      try {
        const payload = await apiRequest(ENDPOINTS.media, {
          method: "POST",
          body: formData,
        });
        const newPath = payload.data?.path || payload.path;
        if (previewElement && newPath) {
          previewElement.src = cacheBust(newPath);
        }
        setStatus(statusElement, payload.message || "Upload complete.", "success");
      } catch (error) {
        setStatus(statusElement, buildErrorMessage(error), "error");
      } finally {
        input.value = "";
      }
    });
  }
})();
