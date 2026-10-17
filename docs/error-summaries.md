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

## Place a summary manually

Render or buffer the controls first so their actual labels, errors, and target
IDs are known. Then output the summary where needed:

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

An early call sees only controls rendered so far. Merely calling `field()` or
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
