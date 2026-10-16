---
title: POST buttons and links
description: Render independent protected action forms with native buttons, enhanced links, optional confirmation, and CakePHP view blocks.
---

# POST buttons and links

Use helper shortcuts for actions such as deleting a record from an index table.
Each call creates its own form session, preserving CakePHP routing, CSRF, method
overrides, and form protection without opening or closing another bound form.

```php
<?= $this->Form->postButton(
    'Delete',
    ['action' => 'delete', $article->id],
    ['method' => 'delete', 'class' => 'button-danger'],
) ?>
```

This renders a form containing a native submit button. It works without
JavaScript. The default method is POST; use `method` for PUT, PATCH, or DELETE.
Controllers still enforce allowed methods, authorization, and persistence.

Both shortcuts inherit the helper's configured `theme`. Pass a `Theme` instance
to override it for one call, or `theme: 'Base'` to force the bundled markup.
See [theme configuration](./themes.md#select-theme).

## Render a link or request confirmation

```php
<?= $this->Form->postLink(
    'Delete',
    ['action' => 'delete', $article->id],
    ['method' => 'delete', 'confirm' => 'Delete this article?'],
) ?>
```

`postLink()` renders an anchor associated with a hidden action form. It requires
JavaScript and never uses the destructive URL as a GET fallback. Use a styled
`postButton()` when the action must work without JavaScript.

Both shortcuts accept `confirm`. The helper automatically emits one deferred
external `FormCompose.actions` script per helper instance when a link or a
confirmation first needs it. There are no generated inline event handlers, and
the script receives the request's `cspScriptNonce` when available. Multiple script
inclusions share one set of event listeners. Plain buttons without confirmation
emit no script.

The asset follows CakePHP's plugin asset routing and deployment conventions;
publish the plugin's `webroot/` assets along with your other plugin assets in
production. Your CSP must permit the external script through its source policy
or nonce. No JavaScript framework is required.

Confirmation is a browser enhancement, not an authorization check. Without
JavaScript, native buttons still submit without showing a confirmation; links do
not submit. Messages and labels are escaped. For developer-owned label markup,
pass `new Html('<span>Delete</span>')` from `FormCompose\Rendering\Html`.

## Pass protected data

```php
<?= $this->Form->postButton('Archive', ['action' => 'archive', $article->id], [
    'data' => ['version' => $article->version, 'audit' => ['source' => 'index']],
]) ?>
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
<?php $form = $this->Form->bind($article); ?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $this->Form->postButton('Delete', ['action' => 'delete', $article->id], [
    'method' => 'delete',
    'block' => 'postActions',
]) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>

<?= $this->fetch('postActions') ?>
```

Both methods accept `block`: true selects `postActions`, and a nonempty string
selects a named CakePHP view block. Render that block after the surrounding form
or in the layout outside all forms. The visible button uses the native `form`
attribute to submit its independent form, including when JavaScript is disabled.
A post link uses its associated form ID. Neither submits the surrounding form's
fields or runs its browser validation. Without `block`, output includes the action
form at the call site, so call it outside other forms.

## Options and presentation

```php
public function postButton(
    string|Html $label,
    array|string $url,
    array $options = [],
    Theme|string|null $theme = null,
    ?RendererInterface $renderer = null,
): string;

public function postLink(
    string|Html $label,
    array|string|null $url = null,
    array $options = [],
    Theme|string|null $theme = null,
    ?RendererInterface $renderer = null,
): string;
```

| Option | Behavior |
| --- | --- |
| `method` | POST by default. Accepts POST, PUT, PATCH, or DELETE, case-insensitively. Overrides `form.method`. |
| `data` | Explicit locked hidden data; defaults to an empty array. |
| `form` | Opening options and HTML attributes for the independent form, including an explicit unique `id` or form `templates`. The URL argument determines its target; form `type` cannot change its method. |
| `block` | Inline by default. True selects `postActions`; a string selects another view block. |
| `confirm` | Confirmation message. Null, false, or an empty string disables confirmation. |
| `template`, `templates`, `variant`, `templateData` | Customize the trigger through the normal PHP template pipeline. `templates['script']` selects the enhancement script element. |
| Other options | HTML attributes on the visible button or link. A link's `target` applies to its associated form. |

Omitting the URL for `postLink()` uses the current request target. Managed
attributes such as `href`, `formaction`, and `formmethod` cannot redirect the
protected action. Supplied labels are escaped unless wrapped in `Html`.

Post buttons reuse `button/control.php` and its `button` attribute defaults. Links
use the `action` scope's `control` part, with `action.control` attribute defaults.
Both reuse the existing `form` and hidden-field templates. `action/script.php`
receives the prepared `$attributes` for the external script, including its asset
URL, defer flag, and optional nonce.

Pass a `Theme` or custom renderer using the same named arguments as `bind()`:

```php
<?= $this->Form->postButton('Delete', ['action' => 'delete', $article->id], [
    'method' => 'delete',
    'template' => 'forms/delete-button',
], theme: $theme) ?>
```

Custom button elements preserve `$attributes`, including `type` and `form`.
Custom link elements preserve `href` and `data-form-compose-action`. Both receive
`$form`, `$label`, and `$templateData`. For applications that bundle the enhancement
already, replace the script element with an empty PHP element. Behavior services
can be specialized through `FormHelper::createPostActionRenderer()`, whose
protected `trigger()` and `script()` methods use the existing renderer and session.
