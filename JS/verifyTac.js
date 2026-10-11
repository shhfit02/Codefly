document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const email = params.get('email') || '';

  const emailInput = document.getElementById('verify-email');
  const instruction = document.getElementById('tac-instruction');
  const resendLink = document.getElementById('resendLink');

  if (emailInput) {
    emailInput.value = email;
  }

  if (instruction && email) {
    instruction.textContent = `Enter the 6-digit code sent to ${email}`;
  }

  if (resendLink && email) {
    resendLink.href = `../HTML/forgotPass.html?email=${encodeURIComponent(email)}`;
  }
});
