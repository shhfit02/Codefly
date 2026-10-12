// @ts-check
// Settings page: changing the email or password needs the current password.
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

// The admin test account: the student one is shared with notifications.spec.js, whose mute
// test would race with these saves (the form also saves the notification switch).
const account = loadAccount('admin');
const ORIGINAL_EMAIL = 'zz_test_admin@example.invalid';

async function openSettings(page) {
  // Admins are sent on to their own Settings page (same form and API).
  await page.goto('HTML/account-setting.php');
  await expect(page).toHaveURL(/adminSettings\.php$/);
  await expect(page.locator('#accountSettingsStatus')).toContainText('synced');
}

async function save(page) {
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.locator('#accountSettingsStatus')).not.toContainText('Saving');
}

test.describe('account settings', () => {
  test.describe.configure({ mode: 'serial' });
  test.skip(!account, `no admin test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('email and password changes need the current password', async ({ page }) => {
    await login(page, account);
    await openSettings(page);
    const status = page.locator('#accountSettingsStatus');

    await page.locator('input[name="email"]').fill('zz_test_admin_new@example.invalid');
    await save(page);
    await expect(status).toContainText('current password');

    await page.locator('input[name="currentPassword"]').fill('wrong-password');
    await save(page);
    await expect(status).toContainText('Current password is incorrect');

    await page.locator('input[name="email"]').fill(ORIGINAL_EMAIL);
    await page.locator('input[name="password"]').fill('another-password-123');
    await page.locator('input[name="confirmPassword"]').fill('another-password-123');
    await page.locator('input[name="currentPassword"]').fill('');
    await save(page);
    await expect(status).toContainText('current password');

    // Nothing was saved: the old password still works and the email is unchanged.
    await openSettings(page);
    await expect(page.locator('input[name="email"]')).toHaveValue(ORIGINAL_EMAIL);
  });

  test('the right current password allows an email change', async ({ page }) => {
    await login(page, account);
    await openSettings(page);
    try {
      await page.locator('input[name="email"]').fill('zz_test_admin_new@example.invalid');
      await page.locator('input[name="currentPassword"]').fill(account.password);
      await save(page);
      await expect(page.locator('#accountSettingsStatus')).toContainText('updated');
      await expect(page.locator('input[name="currentPassword"]')).toHaveValue('');
    } finally {
      await openSettings(page);
      await page.locator('input[name="email"]').fill(ORIGINAL_EMAIL);
      await page.locator('input[name="currentPassword"]').fill(account.password);
      await save(page);
      await expect(page.locator('#accountSettingsStatus')).toContainText('updated');
    }
  });
});
