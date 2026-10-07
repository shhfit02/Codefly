document.addEventListener('DOMContentLoaded', () => {
  const errorBox = document.getElementById('register-errors');
  const errorList = errorBox?.querySelector('ul');
  const usernameInput = document.getElementById('username');
  const emailInput = document.getElementById('email');
  const agreeCheckbox = document.getElementById('agree');

  const summaryName = document.getElementById('summary-name');
  const summaryAge = document.getElementById('summary-age');
  const summaryGender = document.getElementById('summary-gender');

  fetch('../PHP/register_state.php', { credentials: 'same-origin', cache: 'no-store' })
    .then((res) => res.json())
    .then((data) => {
      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      if (data.profile) {
        if (summaryName) summaryName.textContent = data.profile.fullname || '-';
        if (summaryAge) summaryAge.textContent = data.profile.age || '-';
        if (summaryGender) {
          let formattedGender = '-';
          if (data.profile.gender) {
            formattedGender = data.profile.gender.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
          }
          summaryGender.textContent = formattedGender;
        }
      }

      if (usernameInput && data.values && data.values.username) {
        usernameInput.value = data.values.username;
      }
      if (emailInput && data.values && data.values.email) {
        emailInput.value = data.values.email;
      }
      if (agreeCheckbox) {
        agreeCheckbox.checked = Boolean(data.agreeChecked);
      }

      const errors = data && Array.isArray(data.errors) ? data.errors : [];
      if (errorBox && errorList) {
        if (errors.length > 0) {
          errorList.innerHTML = errors.map((err) => `<li>${err}</li>`).join('');
          errorBox.hidden = false;
        } else {
          errorBox.hidden = true;
          errorList.innerHTML = '';
        }
      }
    })
    .catch((err) => {
      console.error('Failed to load registration state:', err);
      if (errorBox && errorList) {
        errorBox.hidden = true;
        errorList.innerHTML = '';
      }
    });
});
