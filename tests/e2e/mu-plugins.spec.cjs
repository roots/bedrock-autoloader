const { test, expect } = require('@playwright/test');

test.describe('Bedrock Autoloader', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');
  });

  test('autoloaded mu-plugin appears on must-use plugins page with asterisk', async ({ page }) => {
    await page.goto('/wp/wp-admin/plugins.php?plugin_status=mustuse');

    const pluginRow = page.locator('table.plugins tr', {
      has: page.locator('text=Turn Comments Off *'),
    });

    await expect(pluginRow).toBeVisible();
  });
});
