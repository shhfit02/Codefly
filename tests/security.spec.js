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

test('drone photos are not served to the network', async ({ request }) => {
  // tello_bridge.py saves photos here; only the bridge (this computer) may show them.
  const fs = require('fs');
  const path = require('path');
  const dir = path.join(__dirname, '..', 'photos');
  const file = path.join(dir, 'photo_19990101_000000_000.jpg');
  const madeDir = !fs.existsSync(dir);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(file, 'not really a jpeg');
  try {
    const res = await request.get('photos/photo_19990101_000000_000.jpg', { maxRedirects: 0 });
    expect(res.status()).toBe(404);
  } finally {
    fs.unlinkSync(file);
    if (madeDir) fs.rmdirSync(dir);
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
