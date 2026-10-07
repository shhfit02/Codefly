document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const email = params.get('email');

  const emailInput = document.getElementById('reset-email');
  const emailText = document.getElementById('reset-email-text');

  if (email && emailInput) {
    emailInput.value = email;
  }

  if (emailText) {
    emailText.textContent = email
      ? `Resetting password for ${email}`
      : "Resetting password";
  }
});
