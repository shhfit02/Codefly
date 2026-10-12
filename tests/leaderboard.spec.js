// @ts-check
// Leaderboards show real data in XP order, and no claims the system does not back up.
const { test, expect } = require('@playwright/test');
const { loadAccount, login, CREDENTIALS_FILE } = require('./helpers/accounts');

const student = loadAccount('student');
const admin = loadAccount('admin');

// Copy that described features the system never had (removed in the leaderboard refresh).
const FALSE_CLAIMS = [/automation tools/i, /refresh(es|ed) (every hour|hourly)/i, /earliest completion/i, /pts\b/];

async function columnNumbers(page, column) {
  const texts = await page.locator(`#leaderboard-body tr td:nth-child(${column})`).allTextContents();
  return texts.map((t) => Number(t.replace(/[^\d]/g, '')));
}

test.describe('leaderboards', () => {
  test.skip(!student || !admin, `no test accounts (run: php tests/accounts.php setup -> ${CREDENTIALS_FILE})`);

  test('admin leaderboard: XP order, real lesson counts, rank counts', async ({ page }) => {
    await login(page, admin);
    await page.goto('HTML/adminLeaderboard.php');
    await expect(page.locator('#leaderboard-body tr').first()).toBeVisible();
    await expect(page.locator('#statActivePilots')).not.toHaveText('--');

    const xp = await columnNumbers(page, 4);
    expect(xp).toEqual([...xp].sort((a, b) => b - a));

    // Lessons used to be hard-coded to 25 for everyone.
    const lessons = await page.locator('#leaderboard-body tr td:nth-child(6)').allTextContents();
    expect(lessons.every((t) => t.trim() === '25 lessons')).toBe(false);

    // The ladder counts add up to the pilot total, and match the chip counts.
    const total = Number(await page.locator('#statActivePilots').textContent());
    const ladderCounts = (await page.locator('#rankLadder [data-rank-count]').allTextContents()).map(Number);
    expect(ladderCounts.reduce((a, b) => a + b, 0)).toBe(total);
    await expect(page.locator('#rankFilters [data-rank="bronze"]')).toHaveText(`Rookie (${ladderCounts[0]})`);

    // A ladder row filters the table, and clicking it again shows everyone.
    const rookie = page.locator('#rankLadder [data-rank="bronze"]');
    await rookie.click();
    await expect(page.locator('#rankFilters [data-rank="bronze"]')).toHaveAttribute('aria-pressed', 'true');
    await rookie.click();
    await expect(page.locator('#rankFilters [data-rank="all"]')).toHaveAttribute('aria-pressed', 'true');

    const body = await page.locator('body').innerText();
    for (const claim of FALSE_CLAIMS) expect(body).not.toMatch(claim);
    await page.locator('#viewRulesBtn').click();
    const rules = await page.locator('#rulesModal').innerText();
    for (const claim of FALSE_CLAIMS) expect(rules).not.toMatch(claim);
  });

  test('student leaderboard: XP order and no false claims', async ({ page }) => {
    await login(page, student);
    await page.goto('HTML/leaderboard.php');
    await expect(page.locator('#leaderboard-body tr').first()).toBeVisible();

    const xp = await columnNumbers(page, 4);
    expect(xp).toEqual([...xp].sort((a, b) => b - a));

    const body = await page.locator('body').innerText();
    for (const claim of FALSE_CLAIMS) expect(body).not.toMatch(claim);
    const rules = await page.locator('#rulesModal').textContent();
    for (const claim of FALSE_CLAIMS) expect(rules).not.toMatch(claim);
  });
});
