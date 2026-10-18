---
title: Use your own templates
description: Select a PHP element for one control or part, pass presentation data, and see exactly which markup changes.
---

# Use your own templates

Use `template` when you want a field to render a specific PHP element. It selects
the complete control layout: the wrapper around the label, input, help, and
errors. The field's value and input type still come from normal binding.

For a shared default selected automatically, use a [theme](./themes.md).
For a named alternative inside a theme, use a [variant](./variants.md).

## Select a control element

This example puts an `@` decoration beside an email input. Create the element at
the path shown in the second tab:

::: code-group

```php [templates/Users/edit.php]
<?php $form = $this->Form->bind($user); ?>
<?= $form->open() ?>
<?= $form->control('email', [
    'type' => 'email',
    'label' => 'Work email',
    'help' => 'Use your company address.',
    'template' => 'forms/email',
]) ?>
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
<div<?= (string)$attributes->merge(['class' => 'email-field']) ?>>
    <?= $field->label() ?>
    <div class="input-with-icon">
        <span aria-hidden="true">@</span>
        <?= $field->input() ?>
    </div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

:::

The result is an `email-field` wrapper containing the label, a decorated input
row, help, and any validation errors. The decoration is hidden from assistive
technology. Add styling for these classes in your application's stylesheet.

The element receives a bound `$field`, escaped `$attributes`, custom
`$templateData`, and a `$parent()` callback. You only need the first two here.
The part methods retain the generated input names, IDs, feedback associations,
escaping, and submission registration.

## Replace just one part

Use plural `templates` to map part names to element names:

::: code-group

```php [Inside an open form]
echo $form->control('email', [
    'type' => 'email',
    'help' => 'Use your company address.',
    'templates' => ['help' => 'forms/email-help'],
]);
```

```php [templates/element/forms/email-help.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<p<?= (string)$attributes->merge(['class' => 'email-hint']) ?>>
    <?= $field->state->helpText() ?>
</p>
```

:::

Only the help element changes. Preserving `$attributes` keeps its generated ID
so the input's `aria-describedby` reference still points to the help. `helpText()`
returns escaped text or explicitly trusted markup. With no configured help, the
help part is empty and this element is not rendered.

`template => 'forms/email'` is shorthand for
`templates => ['control' => 'forms/email']`. Combine it with other part overrides
when needed:

```php
echo $form->control('email', [
    'type' => 'email',
    'help' => 'Use your company address.',
    'template' => 'forms/email',
    'templates' => ['help' => 'forms/email-help'],
]);
```

Parts without an explicit selection continue to use the theme. Supplying
different elements for `template` and `templates['control']` throws
`InvalidArgumentException`.

::: tip Match the part you want to change
`control` arranges the complete field. `input` is the input rendering hook;
`native` is the standard input markup it ultimately renders. Hidden fields
bypass the control layout, so customize their input parts instead.
The [template reference](./template-reference.md#field-templates) lists all parts.
:::

## Pass custom presentation data

Use `templateData` for values your element needs. Here the decoration becomes
configurable:

```php [Inside an open form]
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/email',
    'templateData' => ['prefix' => '@'],
]);
```

In the email element, add `use FormCompose\Rendering\Html;` after
`declare(strict_types=1);`, then replace the decorative span with:

```php
<span aria-hidden="true"><?= Html::text($templateData['prefix']) ?></span>
```

Custom data stays in `$templateData`; it does not become HTML attributes or
replace `$field`. The same bag is available to this field's parts. Escape its
values when outputting them.

Form, button, and blueprint data have separate scopes. See
[template data](./template-reference.md#template-data) for replacement rules and
the complete scope table.

## Reuse the layout automatically

An explicit template is selected wherever you pass its element name. To make
this email layout the default for all email fields, move it to
`templates/element/forms/application/email/control.php` and configure
`new Theme(['forms/application'])`. Callers can then use just
`control('email', ['type' => 'email'])`.

Continue with [Create a theme](./themes.md) for that setup, or
[Use variants](./variants.md) to offer several named layouts.
