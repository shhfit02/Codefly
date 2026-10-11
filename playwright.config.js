// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// Tests run against the XAMPP server, so start Apache and MySQL first.
module.exports = defineConfig({
  testDir: './tests',
  reporter: 'list',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080/codefly/',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
