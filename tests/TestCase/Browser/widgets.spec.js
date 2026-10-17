import { test, expect } from '@playwright/test';

test('custom PHP layouts preserve names, feedback targets, and protected browser submission', async ({ page }) => {
  await page.goto('/widgets');
  await expect(page.locator('fieldset > legend')).toHaveText('Period');
  await expect(page.locator('section.inline-range > aside input')).toHaveAttribute('name', 'period[end]');
  const associations = await page.locator('form').evaluate(form => ({
    valid: form.checkValidity(),
    idsUnique: [...document.querySelectorAll('[id]')].length === new Set([...document.querySelectorAll('[id]')].map(el => el.id)).size,
    feedbackExists: [...form.querySelectorAll('[aria-describedby]')].every(el =>
      el.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id))),
  }));
  expect(associations).toEqual({ valid: true, idsUnique: true, feedbackExists: true });
  await page.getByRole('link', { name: 'Period: Check dates' }).click();
  await expect(page.locator('[name="period[start]"]')).toBeFocused();
  await page.locator('[name="period[end]"]').fill('2026-10-20');
  await page.locator('textarea').fill('Edited **without** an editor runtime');
  await page.getByRole('button', { name: 'Save widgets' }).click();
  await expect(page).toHaveURL('/widgets/save');
  expect(JSON.parse(await page.locator('body').innerText())).toEqual({
    period: { end: '2026-10-20', start: '2026-10-01' }, body: 'Edited **without** an editor runtime',
  });
});

test('Cake rejects undeclared inputs in a custom widget payload', async ({ page }) => {
  await page.goto('/widgets');
  const status = await page.locator('form').evaluate(async form => {
    const data = new FormData(form);
    data.set('period[extra]', 'Injected');
    return (await fetch(form.action, { method: 'POST', body: data })).status;
  });
  expect(status).toBe(400);
});
