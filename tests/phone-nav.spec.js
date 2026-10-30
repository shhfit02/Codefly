// @ts-check
// On phones the sidebar turns into a bottom bar with a More sheet; larger screens keep the sidebar.
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const student = loadAccount('student');
const admin = loadAccount('admin');

test.describe('phone bottom bar', () => {
  test.use({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });

  test('student: bar at the bottom, More holds Settings and Logout', async ({ page }) => {
    test.skip(!student, `no student test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);
    await login(page, student);
    await page.goto('HTML/home.php');

    const bar = page.locator('.sidebar');
    const box = await bar.boundingBox();
    expect(box && Math.round(box.y + box.height)).toBe(844);
    expect(box && box.width).toBe(390);
    await expect(bar.locator('.menu-links a:visible .nav-text')).toHaveText(['Home', 'Module', 'Leaderboard', 'Notifications', 'More']);

    const sheet = page.locator('#mobile-more');
    await expect(sheet).toBeHidden();
    await page.locator('#mobile-more-btn').click();
    await expect(sheet.getByRole('link', { name: 'Settings' })).toBeVisible();
    await expect(sheet.getByRole('link', { name: 'Logout' })).toBeVisible();

    await sheet.getByRole('link', { name: 'Settings' }).click();
    await expect(page).toHaveURL(/account-setting\.php$/);
    // The current page lives under More, so More is the highlighted item.
    await expect(page.locator('.nav-more-btn')).toHaveClass(/active/);
  });

  test('admin: More holds the signed-in card', async ({ page }) => {
    test.skip(!admin, `no admin test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);
    await login(page, admin);
    await page.goto('HTML/adminDashboard.php');
    await page.locator('#mobile-more-btn').click();
    await expect(page.locator('#mobile-more .sidebar-user')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('#mobile-more')).toBeHidden();
  });
});

test('tablets and up keep the sidebar', async ({ page }) => {
  test.skip(!student, `no student test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);
  await page.setViewportSize({ width: 768, height: 1024 });
  await login(page, student);
  await page.goto('HTML/home.php');
  const box = await page.locator('.sidebar').boundingBox();
  expect(box && box.x).toBe(0);
  expect(box && box.height).toBe(1024);
  await expect(page.locator('#mobile-more-btn')).toBeHidden();
});
