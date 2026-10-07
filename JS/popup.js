let toastBox = document.getElementById("toastBox");
let successMsg =
  '<ion-icon name="checkmark-circle"></ion-icon>Successfully submitted';
let errorMsg = '<ion-icon name="close-circle"></ion-icon>Something went wrong';
let invalidMsg = '<ion-icon name="alert-circle"></ion-icon>Invalid input';

/**
 * Create a toast with optional type styling, defaults to neutral.
 * @param {string} msg - Markup/text inside the toast.
 * @param {"success"|"error"|"invalid"|"info"} [type]
 */
function showToast(msg, type) {
  let toast = document.createElement("div");
  toast.classList.add("toast");
  toast.innerHTML = msg;

  // Preserve legacy behavior that infers type from canned messages.
  const inferredType =
    type ??
    (msg === errorMsg
      ? "error"
      : msg === invalidMsg
      ? "invalid"
      : msg === successMsg
      ? "success"
      : "info");
  toast.classList.add(inferredType);

  toastBox.appendChild(toast);

  let audio = new Audio("../Assets/Popup.mp3");
  audio.volume = 0.85; // bump volume for clearer feedback
  audio.play();

  setTimeout(() => {
    toast.remove();
  }, 4000);
}
