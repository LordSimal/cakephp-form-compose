(() => {
  const installed = Symbol.for('FormCompose.actions');
  if (document[installed]) return;
  document[installed] = true;

  document.addEventListener('click', event => {
    const link = event.target instanceof Element
      ? event.target.closest('a[data-form-compose-action]')
      : null;
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    if (link.getAttribute('aria-disabled') === 'true') return;
    const form = document.getElementById(link.dataset.formComposeAction);
    if (form instanceof HTMLFormElement) HTMLFormElement.prototype.requestSubmit.call(form);
  });

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    const message = Element.prototype.getAttribute.call(form, 'data-form-compose-confirm');
    if (message !== null && !window.confirm(message)) event.preventDefault();
  });
})();
