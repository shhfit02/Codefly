// @ts-check
// Admin view of a student's profile: real module progress and admin-only actions.
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const student = loadAccount('student');
const admin = loadAccount('admin');

test.describe('admin view profile', () => {
  test.skip(!student || !admin, `no test accounts (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('shows module progress and an edit link for the student', async ({ page }) => {
    await login(page, admin);
    await page.goto(`HTML/adminViewProfile.php?user=${student.username}`);
    await expect(page.locator('#viewProfileIdentity')).toHaveText(`@${student.username}`);

    // One progress bar per module, and the summary counts modules (it used to reuse the lesson count).
    const bars = page.locator('#moduleProgress [role="progressbar"]');
    await expect(bars.first()).toBeVisible();
    const moduleCount = await bars.count();
    await expect(page.locator('#viewModulesDone')).toHaveText(new RegExp(`of ${moduleCount} modules finished`));

    await expect(page.locator('#editUserLink')).toBeVisible();
    await expect(page.locator('#editUserLink')).toHaveAttribute('href', /editUser\.php\?id=\d+$/);
    await expect(page.locator('#viewStreak')).toHaveText(/^\d+$/);
  });

  test('student profile shows real progress, and edit controls only on their own', async ({ page }) => {
    await login(page, student);
    await page.goto('HTML/view-profile.php');
    await expect(page.locator('#viewProfileIdentity')).toContainText(`@${student.username}`);
    await expect(page.locator('#moduleProgress [role="progressbar"]').first()).toBeVisible();
    await expect(page.locator('#viewXpNext')).toHaveText(/XP to |Top rank reached/);
    const body = await page.locator('body').innerText();
    expect(body).not.toMatch(/this week|Top \d+% of pilots/); // placeholder numbers that used to show
    await expect(page.locator("button[onclick*='edit-profile.php']")).toBeVisible();

    await page.goto(`HTML/view-profile.php?user=${admin.username}`);
    await expect(page.locator('#viewProfileIdentity')).toContainText(`@${admin.username}`);
    await expect(page.locator("button[onclick*='edit-profile.php']")).toBeHidden();
    await expect(page.locator('#uploadCoverBtn')).toBeHidden();
  });

  test('students do not get other users\' ids or emails from the profile API', async ({ page }) => {
    await login(page, student);
    const res = await page.request.get(`PHP/viewPublicProfile.php?user=${admin.username}`);
    const data = (await res.json()).data;
    expect(data.account.id).toBeNull();
    expect(data.account.email).toBe('');
  });
});
