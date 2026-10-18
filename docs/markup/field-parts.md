---
title: Compose field parts
description: Resolve immutable field state, configure renderable parts, and register handwritten markup.
---

# Compose field parts

Use `control()` for a complete themed field. Resolve a `field()` builder when the
view arranges label, input, help, and errors independently.

## Arrange a field in the view

```php
<?php
$form = $this->Form->form($article)->bind();
$title = $form->field('title')
    ->help('Displayed above the article.')
    ->resolve();
?>
<?= $form->open() ?>
<section class="article-title">
    <?= $title->label()->attributes(['class' => 'title-label']) ?>
    <div class="title-input-shell">
        <?= $title->input()->attributes(['class' => 'title-input', 'data-controller' => 'title']) ?>
    </div>
    <?= $title->help() ?>
    <?= $title->errors() ?>
</section>
<?= $form->control('body')->type('textarea') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

`field()` returns a mutable `FieldBuilder`; `resolve()` captures a `BoundField`.
Each part method returns a `PartBuilder`, configurable with attributes, template,
variant, and template data. Echo or `render()` emits it. Only rendering/registering
an input changes the submission contract. Whole-field behavior belongs on the
field builder before resolution.

## Inspect state without rendering

```php
$title = $form->field('title')->resolve();
$currentTitle = $title->state->value;
$messages = $title->state->messages;
$attributes = $title->attributes();
```

Inspection renders nothing and reserves no IDs or submitted names. A bound field
is a snapshot; resolve again if source data or configuration changes. See the
[bound-field reference](/field-reference).

## Preserve feedback and accessible names

Render enabled help/errors so generated ARIA references have targets. Configure
`help(false)` or `errors(false)` when deliberately omitting them. `close()` rejects
missing generated feedback targets and leaves the form open for a corrected retry.
Parts may precede or follow their input.

For errors shown only in a summary, keep errors enabled and explicitly remove
input descriptions on the input builder:

```php
echo $field->input()->attributes(['aria-describedby' => null]);
```

Application-owned ARIA references remain your responsibility. If hiding a label,
supply another accessible name such as `attributes(['aria-label' => 'Article title'])`.
Preserve fieldset/legend semantics for radio and multi-checkbox groups.

Handwritten feedback uses the configured identity:

```php
<p<?= (string)$title->attributes('help') ?>><?= $title->state->helpText() ?></p>
<?php $form->registerFeedback($title, 'help');
```

Register after emitting the matching element. Registration reserves its ID and
participates in rollback, without adding submitted fields.

## Write a native input by hand

Prefer `input()` so native formatting, choices, and fallbacks are prepared. For
an ordinary scalar text field:

```php
<?php

use FormCompose\Rendering\Html;

$title = $form->field('title')
    ->type('text')
    ->resolve();
?>
<?= $title->label() ?>
<input type="text"<?= (string)$title->attributes() ?> value="<?= Html::text((string)$title->state->value) ?>">
<?php $title->register(); ?>
<?= $title->help() ?>
<?= $title->errors() ?>
```

`attributes()` excludes the native type/formatted value. If adding local input
attributes, pass the same bag to `attributes('input', $local)` and `register($local)`.
Use either rendering or handwritten registration once per input. File/password,
checkbox, multiple-choice, and composite controls have additional submission
semantics; implement a [custom type](/custom-fields) for a custom protocol.
