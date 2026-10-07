document.addEventListener('DOMContentLoaded', () => {
  const errorBox = document.getElementById('profile-errors');
  const errorList = errorBox?.querySelector('ul');
  const fullnameInput = document.getElementById('fullname');
  const ageInput = document.getElementById('age');
  const genderSelect = document.getElementById('gender');

  fetch('../PHP/profile_state.php', { credentials: 'same-origin', cache: 'no-store' })
    .then((res) => res.json())
    .then((data) => {
      const values = data && data.values ? data.values : {};
      if (fullnameInput && values.fullname) {
        fullnameInput.value = values.fullname;
      }
      if (ageInput && values.age) {
        ageInput.value = values.age;
      }
      if (genderSelect && values.gender) {
        genderSelect.value = values.gender;
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
      console.error('Failed to load profile state:', err);
      if (errorBox && errorList) {
        errorBox.hidden = true;
        errorList.innerHTML = '';
      }
    });
});
