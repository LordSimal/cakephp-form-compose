---
title: How rendering works
description: Follow a control call into its PHP element and choose where to customize your markup.
---

# How rendering works

A control is a complete field: label, input, help, and errors. Its PHP element
arranges those parts. Each part can also have its own element.

```text
control
├── label     Email address
├── input     <input type="email" …>
├── help      Use your work address.
└── errors    Validation messages, when present
```

## Follow a call into its PHP element

Create the element in the second tab, then use it in your view:

::: code-group

```php [templates/Users/edit.php]
<?php
$form = $this->Form->bind($user);
?>
<?= $form->open() ?>
<?= $form->control('email')
    ->type('email')
    ->help('Use your work address.')
    ->template('forms/email') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

```php [templates/element/forms/email.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= $attributes ?>>
    <?= $field->label() ?>
    <div class="input-shell"><?= $field->input() ?></div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

:::

`template('forms/email')` selects the CakePHP element without its `.php` suffix.
The element receives a bound `$field`: its value and metadata have already been
resolved. Calling its part methods renders them where you place them.

Preserve `$attributes` on the wrapper and render the input through `input()`.
Those supplied attributes and parts keep escaping, field associations, and
submission registration intact.

## Choose how to customize

| Task | Use |
| --- | --- |
| Add input classes or `data-*` attributes | [Field attributes](/field-options#attributes). |
| Arrange one field in a view | [Field parts](/field-parts). |
| Select a PHP element for a field | [An explicit template](/templates). |
| Make markup the shared default | [A theme](/themes). |
| Offer a named alternative, such as compact | [A variant](/variants). |
| Find which element was selected | [Template debugging](/theme-debug). |

## Templates, themes, and variants

A **template** is the PHP element that renders a part. A **theme** chooses the
directories where those elements live and their default attributes. A **variant**
requests a named alternative within the theme. Start with an explicit template;
share it through a theme when you want that layout across forms.

## Field layout and form layout

A field template arranges one control. A [blueprint layout](/blueprints#keep-control-of-the-layout)
arranges a set of fields. A [complete-form layout](/template-reference#complete-form-layouts)
arranges the prepared body, summary, and buttons.

For every part's filename and variables, see the [template reference](/template-reference).
