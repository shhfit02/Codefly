// @ts-check
const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.goto('index.html');
});

test('landing page loads with login and register entry points', async ({ page }) => {
  await expect(page).toHaveTitle('CodeFly');
  await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
  await expect(page.getByRole('navigation').getByRole('link', { name: 'Register' })).toBeVisible();
});

test('login button opens the login panel', async ({ page }) => {
  const panel = page.locator('#login-panel');
  await expect(panel).toBeHidden();

  await page.locator('.btnLogin-primary').click();

  await expect(panel).toBeVisible();
  await expect(panel.getByRole('heading', { name: 'Login' })).toBeVisible();
});

test('submitting an empty form shows a warning', async ({ page }) => {
  await page.locator('.btnLogin-primary').click();
  await page.locator('#login-panel').getByRole('button', { name: 'Login' }).click();

  await expect(page.locator('.toast--warning')).toContainText('Please fill in both fields.');
});

test('unknown username shows a warning', async ({ page }) => {
  await page.locator('.btnLogin-primary').click();
  await page.locator('input[name="username"]').fill(`nobody_${Date.now()}`);
  await page.locator('input[name="password"]').fill('wrong-password');
  await page.locator('#login-panel').getByRole('button', { name: 'Login' }).click();

  await expect(page.locator('.toast--warning')).toContainText('No account found');
});

test('register link goes to the registration page', async ({ page }) => {
  await page.getByRole('navigation').getByRole('link', { name: 'Register' }).click();
  await expect(page).toHaveURL(/register-profile\.html$/);
});

// Set CODEFLY_USER and CODEFLY_PASS to a real (non-admin) account to run this one.
test('valid credentials log in to home', async ({ page }) => {
  const user = process.env.CODEFLY_USER;
  const pass = process.env.CODEFLY_PASS;
  test.skip(!user || !pass, 'CODEFLY_USER / CODEFLY_PASS not set');

  await page.locator('.btnLogin-primary').click();
  await page.locator('input[name="username"]').fill(user);
  await page.locator('input[name="password"]').fill(pass);
  await page.locator('#login-panel').getByRole('button', { name: 'Login' }).click();

  await expect(page).toHaveURL(/HTML\/home\.php$/);
});
