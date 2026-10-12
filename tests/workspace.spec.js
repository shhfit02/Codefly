// @ts-check
// Code Block workspace: simulator mode, Esc to stop, saved programs per account, and the
// "not connected" path. No drone or bridge is needed (the bridge is expected to be off).
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const admin = loadAccount('admin');
const student = loadAccount('student');

const num = (n) => ({ shadow: { type: 'math_number', fields: { NUM: n } } });
const move = (dir, n, next) => ({ block: { type: 'tello_move_direction', fields: { DIRECTION: dir }, inputs: { DISTANCE: num(n) }, ...(next ? { next } : {}) } });
// take off, forward 100, turn right 90, forward 100, land
const SQUARE_CORNER = { blocks: { languageVersion: 0, blocks: [{
  type: 'tello_takeoff', x: 40, y: 40,
  next: move('forward', 100, { block: { type: 'tello_turn', fields: { TURN: 'cw' }, inputs: { DEGREES: num(90) },
    next: move('forward', 100, { block: { type: 'tello_land' } }) } }),
}] } };

async function openWorkspace(page) {
  await login(page, admin);
  await page.goto('HTML/workspace.php');
  await page.waitForFunction(() => typeof workspace !== 'undefined' && document.body.dataset.user);
  await page.evaluate((program) => Blockly.serialization.workspaces.load(program, workspace), SQUARE_CORNER);
}

test.describe('workspace', () => {
  test.skip(!admin || !student, `no test accounts (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('runs on the simulator by default, and Esc stops a run', async ({ page }) => {
    await openWorkspace(page);
    await expect(page.locator('body')).toHaveClass(/sim-mode/);
    await expect(page.locator('#sidebar-connection-label')).toHaveText('Simulator');

    const launch = page.locator('#header-flag-btn');
    await launch.click();
    await expect(page.locator('#header-flag-label')).toHaveText('Stop');
    await expect(page.locator('#header-flag-label')).toHaveText('Launch', { timeout: 10000 });
    await expect(page.locator('#droneSimStatus')).toHaveText('Complete');
    await expect(page.locator('#simHeading')).toHaveText('90°');
    await expect(page.locator('#simAltitude')).toHaveText('0 cm');

    await launch.click();
    await page.keyboard.press('Escape');
    await expect(page.locator('#header-flag-label')).toHaveText('Launch');
    await expect(page.locator('#droneSimStatus')).toHaveText('Stopped');
  });

  test('drone mode without a drone explains what to do and offers the simulator', async ({ page }) => {
    await openWorkspace(page);
    await page.locator('.mode-option[data-mode="drone"]').click();
    await expect(page.locator('#sidebar-connection-label')).toHaveText('Not connected');

    // Launch stays clickable (aria-disabled only) so it can explain why nothing flew.
    await page.locator('#header-flag-btn').click({ force: true });
    await expect(page.locator('.swal2-title')).toHaveText('Drone not connected');
    await expect(page.locator('#header-flag-label')).toHaveText('Launch');
    await page.locator('.swal2-deny').click();
    await expect(page.locator('#sidebar-connection-label')).toHaveText('Simulator');
  });

  test('programs are saved per account and can be opened and deleted', async ({ page }) => {
    const name = `zz test ${Date.now()}`;
    await openWorkspace(page);
    await page.locator('.tool-btn[data-action="save"]').click();
    await page.locator('.swal2-input').fill(name);
    await page.locator('.swal2-confirm').click();
    await expect(page.locator('#program-name')).toHaveText(name);

    const list = await page.request.get('PHP/programs.php');
    const saved = (await list.json()).data.find((p) => p.name === name);
    expect(saved).toBeTruthy();

    // Another user cannot open it.
    const other = await page.context().browser().newContext({ baseURL: test.info().project.use.baseURL });
    const otherPage = await other.newPage();
    await login(otherPage, student);
    const peek = await otherPage.request.get(`PHP/programs.php?id=${saved.id}`);
    expect(peek.status()).toBe(404);
    await other.close();

    // Clear the canvas, open the program from My programs, then delete it.
    await page.evaluate(() => workspace.clear());
    await page.locator('.tool-btn[data-action="load"]').click();
    const row = page.locator('.programs-list li', { hasText: name });
    await row.locator('.programs-open').click();
    await expect.poll(() => page.evaluate(() => workspace.getTopBlocks(false).length)).toBe(1);
    await expect(page.locator('#program-name')).toHaveText(name);

    await page.locator('.tool-btn[data-action="load"]').click();
    await page.locator('.programs-list li', { hasText: name }).locator('.programs-delete').click();
    await page.locator('.swal2-confirm').click();
    await expect.poll(async () => (await (await page.request.get('PHP/programs.php')).json()).data.some((p) => p.name === name)).toBe(false);
  });
});
