// @ts-check
// Test account credentials for signed-in tests.
// Created by `php tests/accounts.php setup`, which writes them outside the web root to
// ~/.codefly-test-accounts.json. CODEFLY_<ROLE>_USER / CODEFLY_<ROLE>_PASS override them.
const fs = require('fs');
const os = require('os');
const path = require('path');

const CREDENTIALS_FILE = path.join(os.homedir(), '.codefly-test-accounts.json');

function loadAccount(role) {
  const envUser = process.env[`CODEFLY_${role.toUpperCase()}_USER`];
  const envPass = process.env[`CODEFLY_${role.toUpperCase()}_PASS`];
  if (envUser && envPass) return { username: envUser, password: envPass };
  try {
    const all = JSON.parse(fs.readFileSync(CREDENTIALS_FILE, 'utf8'));
    return all[role] || null;
  } catch {
    return null;
  }
}

/** Log in through the real login form and wait until the server redirects away from it. */
async function login(page, account) {
  await page.goto('index.html');
  await page.locator('.btnLogin-primary').click();
  await page.locator('input[name="username"]').fill(account.username);
  await page.locator('input[name="password"]').fill(account.password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith('/index.html')),
    page.locator('#login-panel').getByRole('button', { name: 'Login' }).click(),
  ]);
}

module.exports = { loadAccount, login, CREDENTIALS_FILE };
