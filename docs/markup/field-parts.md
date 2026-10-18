---
title: Compose field parts
description: Arrange bound field parts in your own markup and register handwritten native inputs safely.
---

# Compose field parts

Use `control()` for a complete themed field. Use `field()` when a view needs to
place its label, input, help, and errors separately. Both resolve the same data
and use the same PHP template system.

## Arrange a field in the view

This is a complete form whose title field has a one-off layout:

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
$title = $form->field('title', [
    'help' => 'Displayed above the article.',
]);
?>
<?= $form->open() ?>
<section class="article-title">
    <?= $title->label(['class' => 'title-label']) ?>
    <div class="title-input-shell">
        <?= $title->input(['class' => 'title-input', 'data-controller' => 'title']) ?>
    </div>
    <?= $title->help() ?>
    <?= $title->errors() ?>
</section>
<?= $form->control('body', ['type' => 'textarea']) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

`field()` returns a `BoundField` without rendering or registering an input.
`input()` renders and then registers the control. Each part method accepts HTML
attributes for that part; field behavior such as `type` or `value` belongs in the
options passed to `field()`.

Use an [explicit control template](/templates) to select this structure on
individual controls, or a [theme element](/themes) to make it a shared default.
Use a [blueprint layout](/blueprints#keep-control-of-the-layout) when arranging a reusable collection
of fields rather than the internal structure of one field.

## Inspect state without rendering

```php
$title = $form->field('title');
$currentTitle = $title->state->value;
$messages = $title->state->messages;
$attributes = $title->attributes();
```

State and attribute inspection have no rendering or security-registration side
effects. The [bound-field reference](/field-reference) lists all state properties
and part methods. A bound field is a snapshot; resolve it again if its inputs change.

## Preserve feedback and accessible names

::: warning Keep generated ARIA references valid
Render enabled help and error parts so generated `aria-describedby` references
have matching elements. When deliberately omitting them, configure `help => false`
or `errors => false` during field resolution. `close()` rejects generated feedback
references whose targets have not been rendered or registered, and leaves the
form open so missing parts can be rendered before retrying. Parts may appear
before or after their input. Reading attributes alone registers nothing.
:::

To show errors only in a summary, keep error presentation enabled and omit the
input's descriptions explicitly with `input(['aria-describedby' => null])`.
Application-owned `aria-describedby` references to other elements remain your
responsibility. When suppressing a label, provide
an accessible name, such as `attributes => ['aria-label' => 'Article title']`.

Radio and multi-checkbox controls use a fieldset and legend around their inputs.
Preserve those group semantics when writing a custom control element. Render
an input only once per ID; do not call both `control()` and `input()` for the
same bound field.

Handwritten feedback uses the same configured identities:

```php
<p<?= (string)$title->attributes('help') ?>><?= $title->state->helpText() ?></p>
<?php $form->registerFeedback($title, 'help'); ?>
```

Call `$form->registerFeedback($title, 'help')` or use `'errors'` after emitting the matching
element instead of calling that part's rendering method. Registration reserves
the part ID, participates in render rollback, and adds no submitted fields.

## Write a native input by hand

Prefer `input()` so the field type prepares values, choices, and fallbacks. For
a handwritten scalar text input, emit its prepared attributes and escaped value,
then register that input once. This replaces the earlier title presentation
inside an open form:

```php
<?php
use FormCompose\Rendering\Html;

$title = $form->field('title', ['type' => 'text']);
?>
<?= $title->label() ?>
<input type="text"<?= (string)$title->attributes() ?> value="<?= Html::text((string)$title->state->value) ?>">
<?php $title->register(); ?>
<?= $title->help() ?>
<?= $title->errors() ?>
```

`attributes()` does not contain the native `type` or formatted `value`; those are
supplied explicitly here. Registration uses the field type's submission contract.
If adding local attributes such as `disabled`, pass the same array to both
`attributes('input', $local)` and `register($local)`.

This example is for a scalar text value. Other controls have additional native
semantics: file/password values are never prefilled, choices need selected state,
and checkboxes/multiple selections may need fallbacks. For a composite input or a
non-native value representation, implement a [custom field type](/extending#custom-field-types)
that describes its actual submitted names.
