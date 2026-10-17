---
title: Error summaries
description: Summarize rendered field and collection errors through a PHP template with links to actual controls.
---

# Error summaries

An error summary gathers CakePHP validation messages for the controls actually
rendered in one form session. It complements inline errors and uses the same
PHP template system. It does not validate data or read errors from unrelated fields.

## Enable the complete-form shortcut

```php
use App\Form\PostForm;

echo $this->Form->render(
    PostForm::class,
    $post,
    options: ['errorSummary' => true],
    submit: 'Save post',
);
```

`render()` buffers the blueprint body and places the summary above it. Summaries
are opt-in and render nothing when there are no eligible errors. Form attributes,
method overrides, CSRF, and tamper-protection fields keep their normal behavior.

Supply an options array to customize the summary:

```php
options: [
    'errorSummary' => [
        'title' => 'Check your post',
        'attributes' => ['class' => 'validation-summary'],
    ],
]
```

The session version supports the same option. A local `errorSummary => false`
overrides a true binding default. This setting is consumed internally and is
never emitted as a form attribute.

## Keep custom layouts and actions without manual buffering

Use complete-form `render()` when the summary belongs above a blueprint's fields.
Put the shared complete-form layout in your configured theme as `form/control.php`.
For example, with `new Theme(layers: ['form/themes/daisy'])`, the element lives at
`templates/element/form/themes/daisy/form/control.php`. [Configure the theme once](./themes.md#select-theme)
when loading the helper; each form then discovers the layout automatically:

```php
<?= $this->Form->render(
    'article',
    $article,
    options: ['errorSummary' => true],
    submit: 'Save article',
) ?>
```

The plugin renders the blueprint into `$body` first, builds `$summary` from the
controls that actually rendered, then invokes the form layout. Your page template
needs no `$opening`, `$body`, or `$summary` variables.

```php [templates/element/form/themes/daisy/form/control.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\FormSession $form
 * @var string $body
 * @var string $summary
 * @var string|\FormCompose\Rendering\Html|false $submit
 * @var array<string, mixed> $submitOptions
 */
?>
<?= $summary ?>
<div class="form-body">
    <?= $body ?>
</div>
<div class="form-actions">
    <?= $form->submit(__('Check server validation'), ['formnovalidate' => true]) ?>
    <?php if ($submit !== false): ?>
        <?= $form->submit($submit, $submitOptions) ?>
    <?php endif; ?>
</div>
```

`$body` and `$summary` are prepared HTML; output them directly, once each. The
summary is an empty string when disabled or when there are no eligible errors.
`$submit` is the requested text/trusted HTML label, a translated default Submit
label, or `false`. `$submitOptions` contains the caller's button options.

The session stays open while the layout renders its action buttons. The plugin
then generates final protection fields and closes the form. Opening and closing
remain the plugin's responsibility. Keep validation-bearing controls in the
blueprint body so the prepared summary describes all of them.

The blueprint's `#[Form(..., template: ...)]` element arranges fields and embeds;
the theme's `form/control.php` arranges the prepared body, summary, and actions.
Within a theme, the root `control.php` wraps one field; the nested
`form/control.php` lays out the complete form.

For an exceptional per-form override, pass
`options: ['template' => 'form/layouts/special-editor', 'errorSummary' => true]`.
`templates['control']` is the equivalent part override. Shared layouts belong in
the theme so ordinary `render()` calls need no template selection. See
[form layout variables](./template-reference.md#complete-form-layouts).

## Place a summary manually

The low-level `open()` / `control()` / `close()` API emits each part immediately.
Its `errorSummary()` can describe only controls rendered so far. For a summary
above handwritten controls, buffer those controls first so their actual labels,
errors, and target IDs are known:

```php
$form = $this->Form->bind($post);
echo $form->open();

$body = $form->controls([
    'title',
    'body' => ['type' => 'textarea'],
]);
echo $form->errorSummary();
echo $body;

echo $form->submit('Save post');
echo $form->close();
```

A summary below the controls can be echoed directly after them, without a body
buffer. For blueprint forms, the complete-form layout above handles this ordering
inside the plugin. Merely calling `field()` or
inspecting attributes does not add feedback. A successfully registered handwritten
input also contributes its errors through the normal `register()` contract.
Handwritten registrations contribute unlinked messages: configured types and
choices do not prove which focusable input IDs the handwritten markup emitted.
Call `errorSummary()` inside the open session.

## Which errors appear?

- Rendered controls contribute messages in rendering order, preserving each
  field's message order. Labels prefix the messages; a suppressed or empty label
  falls back to the humanized field name.
- `errors => false` suppresses both inline messages and summary entries. Fields
  omitted by a blueprint or layout contribute nothing.
- Ordinary controls link to their input IDs. Radio and multi-checkbox groups link
  to their first enabled choice, including choices inside groups.
- Hidden and disabled controls, empty/all-disabled choice groups, and controls
  explicitly opting out of linking appear as plain messages.
- Rendered `EmbedMany` sections contribute their own collection errors, including
  empty collections. These entries have no control link. Row-field errors link to
  the qualified row inputs and are not duplicated as collection errors.

Only successful rendering contributes feedback. A failed control, batch,
blueprint layout, or repeated wrapper restores the earlier feedback snapshot alongside
its ID and submission registrations. Summaries remain isolated between sessions.

## Customize the summary element

Place `error-summary/control.php` in a theme layer, or select one explicitly:

```php
$form->errorSummary([
    'template' => 'forms/error-summary',
    'title' => 'Check these fields',
    'templateData' => ['intro' => 'Your changes have not been saved.'],
]);
```

```php [templates/element/forms/error-summary.php]
<?php
use FormCompose\Rendering\Html;
/**
 * @var list<\FormCompose\Form\FeedbackEntry> $entries
 * @var string|\FormCompose\Rendering\Html $title
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var \FormCompose\Rendering\Attributes $titleAttributes
 * @var array{intro: string} $templateData
 */
?>
<aside<?= (string)$attributes ?>>
    <h2<?= (string)$titleAttributes ?>><?= Html::text($title) ?></h2>
    <p><?= Html::text($templateData['intro']) ?></p>
    <ul>
        <?php foreach ($entries as $entry): ?>
            <?php foreach ($entry->messages as $message): ?>
                <li>
                    <?php if ($entry->target !== null): ?>
                        <a href="<?= Html::text('#' . rawurlencode($entry->target)) ?>"><?= Html::text($entry->label) ?>: <?= Html::text($message) ?></a>
                    <?php else: ?>
                        <?= Html::text($entry->label) ?>: <?= Html::text($message) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
</aside>
```

Preserve the prepared summary and heading attributes so their ARIA association
remains valid. Ordinary titles, labels, and messages are escaped; titles and
labels can use explicit trusted `Html`. Messages always remain escaped.

The default element uses a section, translated heading, and list. Generated
summary and heading IDs participate in duplicate-ID checks. To render a second
summary, provide a different `attributes['id']`; its default heading ID follows
that ID. No JavaScript, autofocus, or focus-management runtime is added. Native
fragment links navigate to the prepared controls.

For composite controls, implement the optional
[error target contract](./extending.md#custom-summary-targets) when the main field
ID is not the appropriate focus target.
