/**
 * Delegated POST links and confirmation, including dynamically inserted actions.
 *
 * FormHelper registers this script in Cake's `script` view block; render
 * `$this->fetch('script')` in the layout. Set `autoActionScript` to false for
 * manual loading or replacement behavior. Repeated inclusion is safe.
 *
 * Replace window.confirm with a custom dialog:
 *
 * document.addEventListener('formcompose:confirm', event => {
 *   const { message, respondWith } = event.detail;
 *   respondWith(myDialog(message)); // boolean or Promise<boolean>
 * });
 *
 * Call respondWith() once during dispatch: only true approves; rejection cancels.
 * preventDefault() cancels outright. Unclaimed events use window.confirm.
 * See docs/forms/post-actions.md for the full loading and event contracts.
 */
(() => {
  const installed = Symbol.for('FormCompose.actions');
  if (document[installed]) return;
  document[installed] = true;

  const pending = new WeakSet();
  const approved = new WeakSet();
  const pause = event => {
    event.preventDefault();
    event.stopImmediatePropagation();
  };

  document.addEventListener('click', event => {
    const link = event.target instanceof Element
      ? event.target.closest('a[data-form-compose-action]')
      : null;
    if (!link || event.defaultPrevented || event.button !== 0
      || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    if (link.getAttribute('aria-disabled') === 'true') return;
    const form = document.getElementById(link.dataset.formComposeAction);
    if (form instanceof HTMLFormElement) HTMLFormElement.prototype.requestSubmit.call(form);
  });

  // Capture before form-level AJAX handlers so they receive only approved submits.
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || approved.has(form)) return;
    if (pending.has(form)) {
      pause(event);
      return;
    }
    const message = Element.prototype.getAttribute.call(form, 'data-form-compose-confirm');
    if (message === null) return;

    pending.add(form);
    const submitter = event.submitter ?? null;
    let accepting = true;
    let response = null;
    const confirmation = new CustomEvent('formcompose:confirm', {
      bubbles: true,
      cancelable: true,
      detail: {
        message,
        form,
        submitter,
        respondWith(value) {
          if (!accepting || response !== null) {
            throw new DOMException('respondWith() must be called once during confirmation dispatch.', 'InvalidStateError');
          }
          // Attach rejection handling immediately, including when the event is canceled.
          response = Promise.resolve(value).then(result => result === true, () => false);
        },
      },
    });
    EventTarget.prototype.dispatchEvent.call(form, confirmation);
    accepting = false;

    if (confirmation.defaultPrevented) {
      pending.delete(form);
      pause(event);
    } else if (response === null) {
      pending.delete(form);
      if (!window.confirm(message)) pause(event);
    } else {
      pause(event);
      response.then(allow => {
        pending.delete(form);
        if (!allow || !Node.prototype.contains.call(document, form)) return;
        if (submitter && (!submitter.isConnected || submitter.form !== form
          || submitter.matches(':disabled') || !['submit', 'image'].includes(submitter.type))) return;
        approved.add(form);
        try {
          if (submitter) HTMLFormElement.prototype.requestSubmit.call(form, submitter);
          else HTMLFormElement.prototype.requestSubmit.call(form);
        } finally {
          // Validation can prevent the replay from emitting a submit event.
          approved.delete(form);
        }
      });
    }
  }, true);
})();
