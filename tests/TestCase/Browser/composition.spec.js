import { test, expect } from '@playwright/test';

test('independently composed feedback remains associated through a protected POST', async ({ page }) => {
  await page.goto('/forms/composition');
  const input = page.getByRole('textbox', { name: 'Title', exact: true });
  await expect(input).toHaveAccessibleDescription('Use a descriptive title Review the title');
  const referencesResolve = await input.evaluate(element =>
    element.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id)));
  expect(referencesResolve).toBe(true);
  await page.getByRole('button', { name: 'Save composition' }).click();
  await expect(page).toHaveURL('/forms/save');
  expect(JSON.parse(await page.locator('body').innerText())).toEqual({ title: 'Example' });
});
