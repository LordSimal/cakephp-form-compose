import { test, expect } from '@playwright/test';

test('schema-discovered enums submit native values through Cake protection', async ({ page }) => {
  await page.goto('/forms/enums');
  const status = page.getByRole('combobox', { name: 'Status', exact: true });
  await expect(status).toHaveValue('published');
  await expect(page.getByRole('radio', { name: 'Standard <priority>', exact: true })).toBeChecked();
  await status.selectOption('in-review');
  await page.getByRole('button', { name: 'Save enums' }).click();
  await expect(page).toHaveURL('/forms/save');
  expect(JSON.parse(await page.locator('body').innerText())).toEqual({ status: 'in-review', priority: '0' });
});
