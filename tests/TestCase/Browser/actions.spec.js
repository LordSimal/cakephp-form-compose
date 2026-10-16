import { test, expect } from '@playwright/test';

for (const [label, version] of [['Native delete', '0'], ['External delete', '5']]) {
  test(`${label} submits with JavaScript disabled`, async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8765/actions');
    await page.getByRole('button', { name: label, exact: true }).click();
    const result = JSON.parse(await page.locator('body').innerText());
    expect(result).toMatchObject({ method: 'DELETE', data: { version } });
    expect(result.data).not.toHaveProperty('title');
    if (label === 'Native delete') expect(result.data.record).toEqual({ id: '42' });
    await context.close();
  });
}

test('post links confirm, cancel, and submit under strict CSP', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/actions');
  await expect(page.locator('script[src]')).toHaveCount(1);
  await expect(page.locator('[onclick], [onsubmit]')).toHaveCount(0);
  page.once('dialog', dialog => dialog.dismiss());
  await page.getByRole('link', { name: 'Confirm link', exact: true }).click();
  await expect(page).toHaveURL('/actions');
  page.once('dialog', async dialog => {
    expect(dialog.message()).toBe('Proceed with <record>?');
    await dialog.accept();
  });
  await page.getByRole('link', { name: 'Confirm link', exact: true }).click();
  await expect(page).toHaveURL('/actions/save');
  const result = JSON.parse(await page.locator('body').innerText());
  expect(result).toMatchObject({ method: 'PATCH', data: { version: '7', requestSubmit: 'locked' } });
  expect(errors).toEqual([]);
});

test('post buttons use the same cancellable confirmation', async ({ page }) => {
  await page.goto('/actions');
  page.once('dialog', dialog => dialog.dismiss());
  await page.getByRole('button', { name: 'Confirm button', exact: true }).click();
  await expect(page).toHaveURL('/actions');
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Confirm button', exact: true }).click();
  const result = JSON.parse(await page.locator('body').innerText());
  expect(result.method).toBe('POST');
});

for (const [role, label, method, version] of [
  ['button', 'External delete', 'DELETE', '5'],
  ['link', 'External link', 'PUT', '6'],
]) {
  test(`${label} submits only its external form`, async ({ page }) => {
    await page.goto('/actions');
    await expect(page.locator('form form')).toHaveCount(0);
    await page.getByRole(role, { name: label, exact: true }).click();
    const result = JSON.parse(await page.locator('body').innerText());
    expect(result).toMatchObject({ method, data: { version } });
    expect(result.data).not.toHaveProperty('title');
  });
}

for (const attack of ['locked value', 'extra field', 'missing CSRF']) {
  test(`action forms reject ${attack}`, async ({ page }) => {
    await page.goto('/actions');
    const status = await page.locator('#native-action').evaluate(async (form, attack) => {
      const body = new FormData(form);
      if (attack === 'locked value') body.set('version', '99');
      if (attack === 'extra field') body.set('extra', '1');
      if (attack === 'missing CSRF') body.delete('_csrfToken');
      return (await fetch(form.action, { method: 'POST', body })).status;
    }, attack);
    expect(status).toBe(attack === 'missing CSRF' ? 403 : 400);
  });
}

test('disabled and missing action targets do not submit or navigate', async ({ page }) => {
  await page.goto('/actions');
  const link = page.getByRole('link', { name: 'Confirm link', exact: true });
  await link.evaluate(element => element.setAttribute('aria-disabled', 'true'));
  await link.click({ force: true });
  await expect(page).toHaveURL('/actions');
  await link.evaluate(element => {
    element.removeAttribute('aria-disabled');
    element.setAttribute('data-form-compose-action', 'missing-form');
  });
  await link.click();
  await expect(page).toHaveURL('/actions');
  const ignored = await link.evaluate(element => {
    const event = new MouseEvent('click', { bubbles: true, cancelable: true, ctrlKey: true });
    element.dispatchEvent(event);
    return !event.defaultPrevented;
  });
  expect(ignored).toBe(true);
});

test('loading the enhancement twice still confirms only once', async ({ page }) => {
  await page.goto('/actions');
  const count = await page.evaluate(async () => {
    await new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = document.querySelector('script[src]').src;
      script.nonce = 'fixture-actions';
      script.onload = resolve;
      script.onerror = reject;
      document.head.append(script);
    });
    let confirmations = 0;
    window.confirm = () => { confirmations++; return true; };
    document.addEventListener('submit', event => event.preventDefault());
    document.querySelector('#confirm-action button').click();
    return confirmations;
  });
  expect(count).toBe(1);
  await expect(page).toHaveURL('/actions');
});
