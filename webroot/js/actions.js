/**
 * FormCompose action enhancement (no dependencies).
 *
 * Load once on the host page, including pages whose actions arrive later via AJAX.
 * FormHelper registers a deferred FormCompose.actions asset in Cake's `script`
 * view block by default; render `$this->fetch('script')` in the layout. Set helper
 * option `autoActionScript` to false to load this file yourself or replace it.
 * Delegated listeners handle newly inserted markup without initialization calls.
 * Repeated inclusion installs no additional listeners.
 *
 * Links with data-form-compose-action="form-id" request submission of that form.
 * Forms with data-form-compose-confirm="Message" use window.confirm by default.
 * To supply a custom synchronous or asynchronous dialog, listen on the form or
 * document for the bubbling, cancelable `formcompose:confirm` event:
 *
 * document.addEventListener('formcompose:confirm', event => {
 *   const { message, form, submitter, respondWith } = event.detail;
 *   respondWith(myDialog(message)); // boolean or Promise<boolean>
 * });
 *
 * Call respondWith() synchronously during event dispatch, exactly once. A second
 * or late call throws InvalidStateError. Only true approves; false, rejection,
 * or another resolved value cancels. preventDefault() cancels regardless of the
 * response. Returning a promise from the listener alone does not claim the event.
 * Without a response or cancellation the native confirmation dialog is used.
 *
 * While confirmation is pending, repeated submissions of that form are ignored.
 * Paused/canceled submits do not reach later submit listeners; approval resumes
 * via native requestSubmit(), preserving validation, the original submitter
 * (null for action links), and ordinary submit handlers. Removed forms or removed,
 * reassociated, disabled, or no longer submittable controls are not resumed.
 * Confirmation runs once per attempt.
 * Native buttons need no JavaScript to submit; confirmation and links do.
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
