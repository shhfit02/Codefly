// @ts-check
// Signed-in smoke tests: log in as a real student and a real admin, open every page,
// and fail on uncaught JS errors, server errors from the PHP endpoints, or being
// bounced back to the login page. Needs the test accounts: php tests/accounts.php setup
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const student = loadAccount('student');
const admin = loadAccount('admin');

/** First module that has lessons, so lesson/quiz pages get real ids. */
async function findModule(page) {
  const res = await page.request.get('PHP/listModules.php');
  const payload = await res.json();
  const modules = Object.values(payload.data || {});
  const mod = modules.find((m) => !m.locked && (m.lessons || []).length) || modules.find((m) => (m.lessons || []).length);
  return { moduleId: mod.id, lessonId: mod.lessons[0].id };
}

/** Open a page and collect problems instead of stopping at the first one. */
async function visit(page, url) {
  const problems = [];
  const onPageError = (e) => problems.push(`JS error: ${e.message}`);
  const onResponse = (res) => {
    const u = res.url();
    if (u.includes('/PHP/') && (res.status() >= 500 || res.status() === 401 || res.status() === 403)) {
      problems.push(`${res.status()} from ${u.split('/codefly/')[1]}`);
    }
  };
  page.on('pageerror', onPageError);
  page.on('response', onResponse);
  const response = await page.goto(url);
  await page.waitForLoadState('networkidle');
  page.off('pageerror', onPageError);
  page.off('response', onResponse);
  expect(response?.status(), `${url} status`).toBeLessThan(400);
  expect(page.url(), `${url} should not bounce to login`).not.toContain('/index.html');
  expect(problems, `${url} problems`).toEqual([]);
}

test.describe('student pages', () => {
  test.skip(!student, `no student test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('every student page loads cleanly', async ({ page }) => {
    test.setTimeout(90000); // 11 pages, while other test files run in parallel
    await login(page, student);
    await expect(page).toHaveURL(/HTML\/home\.php$/);
    const { moduleId, lessonId } = await findModule(page);
    const pages = [
      'HTML/home.php',
      'HTML/module.php',
      `HTML/Student-ViewLesson.php?id=${moduleId}`,
      `HTML/Student-ViewLesson-Detail.php?id=${moduleId}&lesson_id=${lessonId}`,
      `HTML/Student-Quiz.php?module_id=${moduleId}&lesson_id=${lessonId}`,
      'HTML/leaderboard.php',
      `HTML/view-profile.php?user=${student.username}`,
      'HTML/account-setting.php',
      'HTML/profile.php',
      'HTML/edit-profile.php',
      'HTML/workspace.php',
    ];
    for (const url of pages) {
      await test.step(url, () => visit(page, url));
    }
  });

  test('a student cannot open admin pages', async ({ page }) => {
    await login(page, student);
    await page.goto('HTML/manageUser.php');
    await expect(page).toHaveURL(/HTML\/home\.php$/);
    const users = await page.request.get('PHP/listUsers.php');
    expect(users.status()).toBe(403);
  });
});

test.describe('admin pages', () => {
  test.skip(!admin, `no admin test account (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('every admin page loads cleanly', async ({ page }) => {
    test.setTimeout(90000); // 14 pages, while other test files run in parallel
    await login(page, admin);
    await expect(page).toHaveURL(/HTML\/adminDashboard\.php$/);
    const { moduleId, lessonId } = await findModule(page);
    const pages = [
      'HTML/adminDashboard.php',
      'HTML/manageUser.php',
      'HTML/addUser.php',
      `HTML/editUser.php?id=${encodeURIComponent((await page.request.get('PHP/listUsers.php').then((r) => r.json())).data.users[0].id)}`,
      `HTML/adminViewProfile.php?user=${student ? student.username : 'zz_test_student'}`,
      'HTML/AddModule.php',
      'HTML/NewModule.php',
      `HTML/editModule.php?id=${moduleId}`,
      `HTML/ViewLesson.php?id=${moduleId}`,
      `HTML/ViewLesson-Detail.php?id=${moduleId}&lesson_id=${lessonId}`,
      `HTML/quiz.php?module_id=${moduleId}&lesson_id=${lessonId}`,
      'HTML/adminLeaderboard.php',
      'HTML/adminSettings.php',
      'HTML/workspace.php',
    ];
    for (const url of pages) {
      await test.step(url, () => visit(page, url));
    }
  });
});

test('signed-out visitors are sent to login', async ({ page }) => {
  for (const url of ['HTML/home.php', 'HTML/adminDashboard.php']) {
    await page.goto(url);
    await expect(page).toHaveURL(/index\.html$/);
  }
  await expect(page.locator('.toast--warning')).toContainText('Please log in');
});
