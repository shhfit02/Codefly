// @ts-check
// The notification bell in the sidebar: badge, panel, mark-as-read, and the
// "In-app notifications" switch in Settings. Notifications are sent to the test
// student with `php tests/accounts.php notify`, so no real user receives anything.
const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const path = require('path');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const PHP = process.env.CODEFLY_PHP || 'C:/xampp/php/php.exe';
const student = loadAccount('student');
const admin = loadAccount('admin');

function notifyStudent(message) {
  execFileSync(PHP, [path.join(__dirname, 'accounts.php'), 'notify', 'student', message]);
}

/** Save the Settings form with the in-app switch on or off. */
async function setInApp(page, enabled) {
  await page.goto('HTML/account-setting.php');
  await expect(page.locator('#accountSettingsStatus')).toContainText('synced');
  await page.locator('#prefInApp').setChecked(enabled, { force: true });
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.locator('#accountSettingsStatus')).toContainText('updated');
}

test.describe('notification bell', () => {
  // One after another: the mute test switches off notifications for the same account.
  test.describe.configure({ mode: 'serial' });
  test.skip(!student || !admin, `no test accounts (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('a student sees, opens and reads a notification on any page', async ({ page }) => {
    const message = `Bell test ${Date.now()}`;
    await login(page, student);
    await setInApp(page, true);
    notifyStudent(message);

    await page.goto('HTML/leaderboard.php');
    const bell = page.locator('.sidebar #notif-btn');
    await expect(page.locator('#notif-badge')).toBeVisible();

    await bell.click();
    const panel = page.locator('#notif-panel');
    await expect(panel).toBeVisible();
    const item = panel.locator('.notif-item', { hasText: message });
    await expect(item).toHaveClass(/notif-item--unread/);
    await expect(item).toHaveAttribute('href', '../HTML/module.php');
    await expect(page.locator('#notif-badge')).toBeHidden();

    // The panel stays on screen next to the sidebar.
    const box = await panel.boundingBox();
    const viewport = page.viewportSize();
    expect(box && viewport && box.x + box.width <= viewport.width && box.y + box.height <= viewport.height).toBe(true);

    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();

    // Read state is saved on the server, not just hidden in this tab.
    await page.reload();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#notif-badge')).toBeHidden();
  });

  test('switching off in-app notifications stops new ones', async ({ page }) => {
    const message = `Muted test ${Date.now()}`;
    await login(page, student);
    await setInApp(page, false);
    try {
      notifyStudent(message);
      await page.goto('HTML/module.php');
      await page.locator('.sidebar #notif-btn').click();
      await expect(page.locator('#notif-panel')).toBeVisible();
      await expect(page.locator('#notif-panel')).not.toContainText(message);
    } finally {
      await setInApp(page, true);
    }
  });

  test('settings only offer switches that work', async ({ page }) => {
    await login(page, student);
    await page.goto('HTML/account-setting.php');
    await expect(page.locator('#prefInApp')).toHaveCount(1);
    for (const name of ['emailAlerts', 'smsAlerts', 'twoFactor']) {
      await expect(page.locator(`[name="${name}"]`)).toHaveCount(0);
    }
  });

  test('admins have the bell too', async ({ page }) => {
    await login(page, admin);
    await page.goto('HTML/manageUser.php');
    await page.locator('.sidebar #notif-btn').click();
    await expect(page.locator('#notif-panel')).toBeVisible();
  });
});
