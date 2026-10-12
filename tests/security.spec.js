// @ts-check
// Guards for the server settings in .htaccess, which are easy to lose when the project
// is copied to another server.
const { test, expect } = require('@playwright/test');

test('session cookie is HttpOnly and SameSite', async ({ request }) => {
  const res = await request.get('PHP/flash_state.php');
  const cookie = res.headers()['set-cookie'] || '';
  expect(cookie).toContain('PHPSESSID=');
  expect(cookie).toMatch(/HttpOnly/i);
  expect(cookie).toMatch(/SameSite=Lax/i);
});

test('scripts and styles are revalidated, so updates reach the browser', async ({ request }) => {
  for (const path of ['JS/workspace.js', 'CSS/home.css']) {
    const res = await request.get(path);
    expect(res.headers()['cache-control'], path).toContain('no-cache');
  }
});

test('private files are not served', async ({ request }) => {
  const blocked = {
    '.git/HEAD': 404,           // repository metadata
    'codefly_database.sql': 403, // database dumps
    'Assets/uploads/': 403,      // folder listings
    'tests/accounts.php': 404,   // CLI-only test tool
  };
  for (const [path, status] of Object.entries(blocked)) {
    const res = await request.get(path, { maxRedirects: 0 });
    expect(res.status(), path).toBe(status);
  }
});
