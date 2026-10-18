---
title: POST buttons and links
description: Render independent protected action forms with native buttons, enhanced links, optional confirmation, and CakePHP view blocks.
---

# POST buttons and links

Use helper shortcuts for actions such as deleting a record from an index table.
Each call creates its own form session, preserving CakePHP routing, CSRF, method
overrides, and form protection without opening or closing another bound form.

```php
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->attributes(['class' => 'button-danger']) ?>
```

This renders a form containing a native submit button. It works without
JavaScript. The default method is POST; use `method` for PUT, PATCH, or DELETE.
Your application owns allowed methods, authorization, and persistence. Restrict
methods in your routes, or use the controller's `allowMethod()` when unrestricted
routes can reach the action. Repeating the same restriction in both places is
optional when all routes to the action already enforce it.

Both shortcuts inherit the helper's configured `theme`. Pass a `Theme` instance
to override it for one call, or `theme: 'Base'` to force the bundled markup.
See [theme configuration](/themes#select-theme).

## Render a link or request confirmation

```php
<?= $this->Form
    ->postLink(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->confirm('Delete this article?') ?>
```

`postLink()` renders an anchor associated with a hidden action form. It requires
JavaScript and never uses the destructive URL as a GET fallback. Use a styled
`postButton()` when the action must work without JavaScript.

Both shortcuts accept `confirm`. By default, confirmation uses the browser's
`window.confirm()` dialog. Applications can [supply a custom dialog](#custom-confirmation-dialogs),
including one that returns a promise.

::: warning Confirmation is not authorization
The confirmation dialog is a browser enhancement. Without JavaScript, a native
`postButton()` still submits without showing confirmation, while a `postLink()`
does not submit. The controller must still authorize the action.
:::

Messages and labels are escaped. For developer-owned label markup, pass
`new Html('<span>Delete</span>')` from `FormCompose\Rendering\Html`.

## Load the enhancement {#load-the-enhancement}

The helper's `autoActionScript` option defaults to true. When the helper is loaded,
it registers the deferred external `FormCompose.actions` asset in CakePHP's
`script` view block, even if the initial page has no post buttons or links.
Include the block in your layout, usually in `<head>`:

```php
<?= $this->fetch('script') ?>
```

Register the helper in `AppView::initialize()` so CakePHP loads it when rendering
the host page, including pages that will receive actions later:

```php
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'autoActionScript' => true,
]);
```

CakePHP's shared HTML helper deduplicates the asset within the view and includes
the request's `cspScriptNonce` when available. There are no generated inline event
handlers. All action calls return markup without script tags; adding more actions
does not add more scripts. The script also guards against duplicate listener
installation if it is included again.

The asset follows CakePHP's plugin asset routing and deployment conventions;
publish the plugin's `webroot/` assets along with your other plugin assets in
production. Your CSP must permit the external script through its source policy
or nonce. No JavaScript framework is required.

### Manual loading or application-owned behavior

Set `autoActionScript` to false when registering the helper to omit automatic
registration. Set this before the helper is initialized; changing it later does
not remove a script already registered in a view block.

```php
// AppView::initialize()
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'autoActionScript' => false,
]);
```

Load the enhancement explicitly in the layout instead:

```php
<?= $this->Html->script('FormCompose.actions', ['defer' => true]) ?>
```

Alternatively, include `webroot/js/actions.js` in your application bundle, or own
the behavior entirely. Disabling automatic loading changes asset registration;
action markup and confirmation metadata are still rendered. Native buttons work
without the asset, while post links and confirmation need an implementation.

### Dynamically inserted actions

Load the script on the host page before users can interact with inserted actions.
Its delegated listeners handle forms, buttons, and links added later through
`innerHTML` or other DOM APIs without an initialization call. Do not depend on a
script tag in a fetched fragment: `innerHTML` does not execute inserted scripts,
and a fragment's view blocks are separate from the host page's layout.

Give each independently rendered fragment's forms explicit, page-unique IDs.
The helper's generated `form-compose-N` counter belongs to one helper instance;
it cannot coordinate IDs across separate HTTP responses:

```php
<?php
use FormCompose\Configuration\FormConfig;

?>
<?= $this->Form
    ->postLink(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->form(
        FormConfig::builder()->id('delete-article-' . $article->id),
    ) ?>
```

If the same record appears more than once, include a unique occurrence identifier
in that form ID too. When using `block`, insert the buffered forms along with
their triggers, outside any existing form.

## Custom confirmation dialogs {#custom-confirmation-dialogs}

The script dispatches a bubbling, cancelable `formcompose:confirm` event on the
form before showing a native dialog. Listen on an individual form or on
`document` for a page-wide integration, including dynamically inserted actions.
This code belongs in your application JavaScript:

```js
document.addEventListener('formcompose:confirm', event => {
  const { message, respondWith } = event.detail;
  respondWith(applicationDialog.confirm(message)); // Promise<boolean>
});
```

`applicationDialog.confirm()` represents your own dialog implementation. It
should display `message` as text and resolve to true for approval or false for
cancellation. The event does not insert the message into HTML.

| Detail | Contract |
| --- | --- |
| `message` | The form's confirmation message as a string. |
| `form` | The `HTMLFormElement` being submitted; also the event target. |
| `submitter` | The original submit button/input, or null for a post link or submission without a button. |
| `respondWith(response)` | Accepts a boolean or `Promise<boolean>`. Call synchronously during dispatch, once per event. |

::: tip Register an asynchronous response immediately
Call `respondWith()` during event dispatch, before awaiting other work. Pass the
promise itself; returning a promise from the listener does not claim the event.
:::

A second call, or a call after dispatch finishes, throws `InvalidStateError`.
Only a result strictly equal to true approves. False, a rejected promise, or a
non-boolean result cancels without a native dialog. If no listener responds,
the script falls back to `window.confirm(message)`.

Calling `event.preventDefault()` cancels the action regardless of any supplied
response. Use it to veto an action without opening a dialog:

```js
document.addEventListener('formcompose:confirm', event => {
  if (applicationIsBusy()) event.preventDefault();
});
```

While a response is pending, repeated submissions of that form are ignored;
other forms remain independent. Paused or canceled submits are stopped before
form-level submit handlers run, so an AJAX handler receives only an approved
submission. Approval calls native `requestSubmit()` with the original submitter,
retaining its name/value and rerunning browser validation without another
confirmation. Your ordinary `submit` handler can still cancel or handle that
approved submission. If the form is removed, or the submitter is removed,
disabled, reassociated, or changed into a non-submit control while waiting, the
action is canceled.

## Pass protected data

```php
<?= $this->Form
    ->postButton(label: 'Archive', url: ['action' => 'archive', $article->id])
    ->data(['version' => $article->version, 'audit' => ['source' => 'index']]) ?>
```

`data` accepts nested arrays with scalar, null, or stringable leaves. Each leaf
renders as a locked hidden input; false and zero are preserved as `"0"`. These
explicit values take precedence over the current request's data. Empty nested
arrays and invalid field paths are rejected. `_method`, `_csrfToken`, and `_Token`
are reserved for the form session.

Locked paths must end in a named field: `records.2.id` works, while a scalar list
such as `ids => [1, 2]` is rejected. CakePHP's form protector normalizes trailing
numeric indexes and cannot lock their individual values. The helper rejects this
shape rather than producing a form that fails protection or unlocking its data.

A named button can use `name` and `value`, but its name must be independent of the
locked data paths and infrastructure fields. Overlapping paths are rejected so
optional button registration cannot unlock the hidden payload.

## Place actions inside another form

Use `block` to put the independent action form outside the surrounding form:

```php
<?php
$form = $this->Form->form($article)->bind();
?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->block('postActions') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>

<?= $this->fetch('postActions') ?>
```

`block('postActions')` selects that CakePHP view block. Any nonempty name
selects another block; null restores inline output. Render that block after the surrounding form
or in the layout outside all forms. The visible button uses the native `form`
attribute to submit its independent form, including when JavaScript is disabled.
A post link uses its associated form ID. Neither submits the surrounding form's
fields or runs its browser validation. Without `block`, output includes the action
form at the call site, so call it outside other forms.

## Options and presentation

`postLink()` returns `ActionBuilder`. `postButton()` returns `PostButtonBuilder`,
which adds native button `name()`, `value()`, and `disabled()` setters.
Optional `config: $actionConfig` accepts an
immutable `ActionConfig` or an inline `ActionConfig::builder()` chain. The helper
captures builders immediately; `config()` is only needed for an explicit snapshot.

| Setter | Behavior |
| --- | --- |
| `method(string)` | POST, PUT, PATCH, or DELETE; overrides the nested form method. |
| `data(array)` | Nested locked hidden payload. |
| `form(FormConfig|FormConfigBuilder)` | Typed configuration of the independent form. Its URL is controlled by the action URL argument. |
| `block(?string)` | Inline for null; a nonempty CakePHP view block name otherwise. |
| `confirm(?string)` | Confirmation text; null or empty string disables it. |
| `attributes(array)` | Presentation attributes on the visible button/link. |
| `name(?string)`, `value(mixed)`, `disabled(bool = true)` | Post buttons only: optional submitted identity and native disabled state. |
| `template()`, `partTemplate()`, `variant()`, `templateData()` | Shared trigger presentation. |

Omitting the URL for `postLink()` uses the current request target. Managed
attributes such as `href`, `formaction`, and `formmethod` cannot redirect the
protected action. Set button name, value, and disabled state with typed setters;
those keys are rejected in `attributes()`. An action configuration containing
button-only state is also rejected by `postLink()`. Supplied labels are escaped unless wrapped in `Html`.

```php
<?= $this->Form
    ->postButton(label: 'Archive', url: ['action' => 'archive', $article->id])
    ->name('intent')
    ->value('archive')
    ->disabled($article->archived) ?>
```

Post buttons reuse `button/control.php` and its `button` attribute defaults. Links
use the `action` scope's `control` part, with `action.control` attribute defaults.
Both reuse the existing `form` and hidden-field templates. The script tag is
registered through CakePHP's HTML helper, independently of action themes and
renderers; use `autoActionScript` to control its loading.

Pass a `Theme` or custom renderer with the helper's named `theme` and `renderer` arguments:

```php
<?= $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id], theme: $theme)
    ->method('delete')
    ->template('forms/delete-button') ?>
```

Custom button elements preserve `$attributes`, including `type` and `form`.
Custom link elements preserve `href` and `data-form-compose-action`. Both receive
`$form`, `$label`, and `$templateData`. Behavior services can be specialized
through `FormHelper::createPostActionRenderer()`, whose protected `trigger()`
method uses the existing renderer and session.
