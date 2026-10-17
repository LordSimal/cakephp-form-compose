import { test, expect } from '@playwright/test';

for (const manual of [false, true]) {
  for (const role of ['link', 'button']) {
    test(`dynamic ${role}s work with ${manual ? 'manual' : 'automatic'} page loading`, async ({ page }) => {
      await page.goto(`/actions/dynamic${manual ? '?manual=1' : ''}`);
      await expect(page.locator('form')).toHaveCount(0);
      await expect(page.locator('script[src]')).toHaveCount(1);
      await page.evaluate(async () => {
        for (const id of [1, 2]) {
          const response = await fetch(`/actions/fragment?id=${id}`);
          document.querySelector('#fragments').insertAdjacentHTML('beforeend', await response.text());
        }
      });
      await expect(page.locator('#fragments script')).toHaveCount(0);
      await expect(page.locator('form')).toHaveCount(4);
      page.once('dialog', dialog => dialog.accept());
      await page.getByRole(role, { name: `Dynamic ${role} 2`, exact: true }).click();
      await expect(page).toHaveURL('/actions/save');
      expect(JSON.parse(await page.locator('body').innerText())).toMatchObject({
        method: role === 'link' ? 'DELETE' : 'POST', data: { version: '2' },
      });
    });
  }
}

for (const role of ['button', 'link']) {
  test(`asynchronous ${role} confirmation cancels, ignores duplicate attempts, then resumes once`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/actions');
    await page.evaluate(() => {
      window.confirm = () => { throw new Error('Native dialog must not run.'); };
      window.confirmations = [];
      window.submissions = 0;
      document.addEventListener('formcompose:confirm', event => {
        const { message, form, submitter, respondWith } = event.detail;
        window.confirmations.push({ message, id: form.id, name: submitter?.name ?? null, target: event.target === form });
        respondWith(new Promise(resolve => { window.resolveConfirmation = resolve; }));
      });
      for (const form of document.forms) {
        form.addEventListener('submit', () => { window.submissions++; });
      }
    });
    const trigger = page.getByRole(role, { name: role === 'button' ? 'Confirm button' : 'Confirm link', exact: true });
    await trigger.click();
    await trigger.click();
    expect(await page.evaluate(() => window.confirmations)).toEqual([{
      message: role === 'button' ? 'Proceed with button?' : 'Proceed with <record>?',
      id: role === 'button' ? 'confirm-action' : 'link-action',
      name: role === 'button' ? 'intent' : null,
      target: true,
    }]);
    expect(await page.evaluate(() => window.submissions)).toBe(0);
    await page.evaluate(async () => {
      window.resolveConfirmation(false);
      await new Promise(resolve => setTimeout(resolve, 0));
    });
    await expect(page).toHaveURL('/actions');
    await trigger.click();
    expect(await page.evaluate(() => window.confirmations.length)).toBe(2);
    expect(await page.evaluate(() => window.submissions)).toBe(0);
    await page.evaluate(() => window.resolveConfirmation(true));
    await expect(page).toHaveURL('/actions/save');
    const result = JSON.parse(await page.locator('body').innerText());
    expect(result).toMatchObject(role === 'button'
      ? { method: 'POST', data: { intent: 'delete' } }
      : { method: 'PATCH', data: { version: '7', requestSubmit: 'locked' } });
    expect(errors).toEqual([]);
  });
}

for (const outcome of ['canceled', 'canceled without response', 'canceled approved response', 'false', 'rejected', 'non-boolean']) {
  test(`${outcome} confirmation cancels without reaching submit handlers and allows retry`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/actions');
    await page.evaluate(outcome => {
      window.confirmations = 0;
      window.submissions = 0;
      window.confirm = () => { throw new Error('Native dialog must not run.'); };
      document.querySelector('#confirm-action').addEventListener('submit', event => {
        window.submissions++;
        event.preventDefault();
      });
      document.addEventListener('formcompose:confirm', event => {
        window.confirmations++;
        if (outcome === 'canceled') {
          event.detail.respondWith(Promise.reject(new Error('Canceled response')));
          event.preventDefault();
        } else if (outcome === 'canceled approved response') {
          event.detail.respondWith(true);
          event.preventDefault();
        } else if (outcome === 'canceled without response') event.preventDefault();
        else if (outcome === 'false') event.detail.respondWith(false);
        else if (outcome === 'rejected') event.detail.respondWith(Promise.reject(new Error('Dialog failed')));
        else event.detail.respondWith('yes');
      });
    }, outcome);
    const trigger = page.getByRole('button', { name: 'Confirm button', exact: true });
    await trigger.click();
    await trigger.click();
    expect(await page.evaluate(() => ({ confirmations: window.confirmations, submissions: window.submissions })))
      .toEqual({ confirmations: 2, submissions: 0 });
    await expect(page).toHaveURL('/actions');
    expect(errors).toEqual([]);
  });
}

test('approval reaches ordinary form submit handlers exactly once and preserves the submitter', async ({ page }) => {
  await page.goto('/actions');
  const result = await page.evaluate(async () => {
    let confirmations = 0;
    const submissions = [];
    const form = document.querySelector('#confirm-action');
    form.addEventListener('formcompose:confirm', event => {
      confirmations++;
      event.detail.respondWith(true);
    });
    form.addEventListener('submit', event => {
      event.preventDefault();
      submissions.push({ name: event.submitter.name, value: event.submitter.value });
    });
    form.querySelector('button').click();
    await new Promise(resolve => setTimeout(resolve, 0));
    return { confirmations, submissions };
  });
  expect(result).toEqual({ confirmations: 1, submissions: [{ name: 'intent', value: 'delete' }] });
  await expect(page).toHaveURL('/actions');
});

test('approval revalidates the form and clears its bypass when validation fails', async ({ page }) => {
  await page.goto('/actions');
  await page.evaluate(() => {
    const form = document.querySelector('#confirm-action');
    const input = document.createElement('input');
    input.required = true;
    input.value = 'valid';
    form.append(input);
    window.confirmations = 0;
    window.submissions = 0;
    form.addEventListener('submit', event => { window.submissions++; event.preventDefault(); });
    form.addEventListener('formcompose:confirm', event => {
      window.confirmations++;
      event.detail.respondWith(new Promise(resolve => { window.resolveConfirmation = resolve; }));
    });
  });
  const trigger = page.getByRole('button', { name: 'Confirm button', exact: true });
  await trigger.click();
  await page.locator('#confirm-action input[required]').fill('');
  await page.evaluate(() => window.resolveConfirmation(true));
  expect(await page.evaluate(() => window.submissions)).toBe(0);
  await page.locator('#confirm-action input[required]').fill('valid again');
  await trigger.click();
  expect(await page.evaluate(() => window.confirmations)).toBe(2);
  await page.evaluate(() => window.resolveConfirmation(true));
  await expect.poll(() => page.evaluate(() => window.submissions)).toBe(1);
});

for (const change of ['form removed', 'submitter removed', 'submitter disabled', 'submitter reassociated', 'submitter type changed']) {
  test(`approval does not resume after ${change}`, async ({ page }) => {
    await page.goto('/actions');
    const result = await page.evaluate(async change => {
      const form = document.querySelector('#confirm-action');
      const button = form.querySelector('button');
      let submissions = 0;
      let resolveConfirmation;
      form.addEventListener('submit', event => { submissions++; event.preventDefault(); });
      form.addEventListener('formcompose:confirm', event => {
        event.detail.respondWith(new Promise(resolve => { resolveConfirmation = resolve; }));
      });
      button.click();
      if (change === 'form removed') form.remove();
      if (change === 'submitter removed') button.remove();
      if (change === 'submitter disabled') button.disabled = true;
      if (change === 'submitter reassociated') button.setAttribute('form', 'native-action');
      if (change === 'submitter type changed') button.type = 'button';
      resolveConfirmation(true);
      await new Promise(resolve => setTimeout(resolve, 0));
      return submissions;
    }, change);
    expect(result).toBe(0);
    await expect(page).toHaveURL('/actions');
  });
}

test('only one synchronous respondWith call can own a confirmation', async ({ page }) => {
  await page.goto('/actions');
  const errors = await page.evaluate(() => {
    const errors = [];
    let respond;
    document.addEventListener('formcompose:confirm', event => {
      respond = event.detail.respondWith;
      respond(false);
      try { respond(true); } catch (error) { errors.push(error.name); }
    });
    document.querySelector('#confirm-action button').click();
    try { respond(true); } catch (error) { errors.push(error.name); }
    return errors;
  });
  expect(errors).toEqual(['InvalidStateError', 'InvalidStateError']);
});

test('an unclaimed event falls back to native confirmation, ignoring a listener return value', async ({ page }) => {
  await page.goto('/actions');
  const result = await page.evaluate(() => {
    let confirmations = 0;
    let submissions = 0;
    window.confirm = () => { confirmations++; return true; };
    document.addEventListener('formcompose:confirm', () => Promise.resolve(false));
    document.querySelector('#confirm-action').addEventListener('submit', event => {
      submissions++;
      event.preventDefault();
    });
    document.querySelector('#confirm-action button').click();
    return { confirmations, submissions };
  });
  expect(result).toEqual({ confirmations: 1, submissions: 1 });
});


test('pending confirmations are isolated per form, including an externally associated button', async ({ page }) => {
  await page.goto('/actions');
  const result = await page.evaluate(async () => {
    const confirmations = [];
    const submissions = [];
    const responses = {};
    const external = document.querySelector('#external-action');
    external.setAttribute('data-form-compose-confirm', 'External?');
    document.addEventListener('formcompose:confirm', event => {
      const { form, submitter, respondWith } = event.detail;
      confirmations.push({ id: form.id, external: !form.contains(submitter) });
      respondWith(new Promise(resolve => { responses[form.id] = resolve; }));
    });
    document.addEventListener('submit', event => {
      submissions.push({ id: event.target.id, associated: event.submitter.form === event.target });
      event.preventDefault();
    });
    document.querySelector('#confirm-action button').click();
    document.querySelector('button[form="external-action"]').click();
    responses['external-action'](true);
    await new Promise(resolve => setTimeout(resolve, 0));
    responses['confirm-action'](false);
    await new Promise(resolve => setTimeout(resolve, 0));
    return { confirmations, submissions };
  });
  expect(result).toEqual({
    confirmations: [{ id: 'confirm-action', external: false }, { id: 'external-action', external: true }],
    submissions: [{ id: 'external-action', associated: true }],
  });
});
