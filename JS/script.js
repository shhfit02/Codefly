const Toasts = (() => {
  const containerSelector = ".toast-region";
  const defaultDuration = 4500;

  const resolveAppPath = (relativePath) => {
    const currentPath = window.location.pathname;
    if (currentPath.includes("/HTML/")) {
      return `../${relativePath}`;
    }
    return relativePath;
  };

  const removeToast = (toastEl) => {
    if (!toastEl) return;
    toastEl.style.opacity = "0";
    toastEl.style.transform = "translateY(10px)";
    setTimeout(() => toastEl.remove(), 250);
  };

  const createToastElement = (message, type) => {
    const toast = document.createElement("div");
    toast.className = `toast toast--${type || "info"}`;

    const messageWrapper = document.createElement("div");
    messageWrapper.textContent = message;

    const dismiss = document.createElement("button");
    dismiss.className = "toast-dismiss";
    dismiss.setAttribute("aria-label", "Dismiss notification");
    dismiss.innerHTML = "&times;";

    toast.appendChild(messageWrapper);
    toast.appendChild(dismiss);

    dismiss.addEventListener("click", () => removeToast(toast));

    return toast;
  };

  const showToast = (message, type = "info", duration = defaultDuration) => {
    const container = document.querySelector(containerSelector);
    if (!container || !message) return;

    const toast = createToastElement(message, type);
    container.appendChild(toast);

    const timeout = setTimeout(() => removeToast(toast), duration);
    toast.addEventListener("mouseenter", () => clearTimeout(timeout));
    toast.addEventListener("mouseleave", () => removeToast(toast));
  };

  const hydrateFromServer = () => {
    const endpoint = resolveAppPath("PHP/flash_state.php");
    fetch(endpoint, { credentials: "same-origin", cache: "no-store" })
      .then((res) => res.ok ? res.json() : { toasts: [] })
      .then((data) => {
        if (!data || !Array.isArray(data.toasts)) return;
        data.toasts.forEach((toast) => {
          showToast(toast.message, toast.type);
        });

        const loginPanel = document.getElementById("login-panel");
        if (data.authenticated && loginPanel) {
          window.location.href = "HTML/home.html";
        }
      })
      .catch((err) => console.error("Failed to load toast messages", err));
  };

  return { showToast, hydrateFromServer };
})();

document.addEventListener("DOMContentLoaded", () => {
  const loginToggleBtn = document.querySelector(".btnLogin-popup.btnLogin-primary");
  const loginPanel = document.getElementById("login-panel");

  if (loginToggleBtn && loginPanel) {
    const setVisibility = (isVisible) => {
      loginPanel.classList.toggle("is-hidden", !isVisible);
      loginPanel.dataset.visible = String(isVisible);
      loginPanel.setAttribute("aria-hidden", String(!isVisible));
      loginToggleBtn.setAttribute("aria-expanded", String(isVisible));
      loginToggleBtn.classList.toggle("hidden", isVisible);
    };

    const initialVisible = loginPanel.dataset.visible === "true";
    setVisibility(initialVisible);

    loginToggleBtn.addEventListener("click", () => {
      const currentlyVisible = loginPanel.dataset.visible === "true";
      setVisibility(!currentlyVisible);
    });
  }

  Toasts.hydrateFromServer();
});
