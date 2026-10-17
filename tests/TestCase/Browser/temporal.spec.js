import { test, expect } from '@playwright/test';

test('Cake temporal values survive browser normalization and a protected submission', async ({ page }) => {
  await page.goto('/forms/temporal');
  const state = await page.locator('form').evaluate(form => ({
    valid: form.checkValidity(),
    values: Object.fromEntries([...new FormData(form)].filter(([name]) => !name.startsWith('_'))),
  }));
  expect(state).toEqual({
    valid: true,
    values: { day: '2026-10-15', clock: '14:23:45', instant: '2026-10-15T14:23:45.123', week: '2026-W53' },
  });
  await page.getByRole('button', { name: 'Save temporal' }).click();
  await expect(page).toHaveURL('/forms/save');
  expect(JSON.parse(await page.locator('body').innerText())).toEqual(state.values);
});
