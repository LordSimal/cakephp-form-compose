---
title: Create a theme
description: Connect a control() call to your own PHP element and reuse the layout across CakePHP forms.
---

# Create a theme

A theme tells FormCompose where to find your PHP elements and which default
attributes to use. Start with one control layout; the bundled theme supplies any
parts you do not replace.

This example gives email controls an icon beside the input. It changes their
presentation while keeping their bound values, feedback, and submission behavior.

## 1. Select your element directory

Pass an element prefix as a theme layer when binding the form:

```php [templates/Users/edit.php]
<?php
use FormCompose\Rendering\Theme;

$theme = new Theme(layers: ['forms/application']);
$form = $this->Form->bind($user, theme: $theme);
?>
<?= $form->open() ?>
<?= $form->control('email', ['type' => 'email']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

The `forms/application` prefix maps to your application's
`templates/element/forms/application/` directory. For this email control,
FormCompose looks for `email/control.php`, then a generic `control.php`.

## 2. Write the control element

Create this file in the selected layer:

```php [templates/element/forms/application/email/control.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= (string)$attributes->merge(['class' => 'contact-field']) ?>>
    <?= $field->label() ?>
    <div class="input-with-icon">
        <span aria-hidden="true">@</span>
        <?= $field->input() ?>
    </div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

The outer container preserves the supplied attributes and adds your class.
`$field->input()` renders the input part at the position you choose. The other
calls render their corresponding parts, returning empty content when disabled or
not applicable.

Every email control in a form using this theme now gets this layout. Other types
continue to use the base control element, and all parts without overrides still
use their base elements.

::: tip Change just one control
For an individual field, pass `['template' => 'forms/special-email']` to
`control()`. This directly selects `templates/element/forms/special-email.php`
without changing the theme. The [control guide](./usage.md#choose-a-template-for-one-control)
shows a complete example; plural `templates` selects overrides for individual parts.
:::

## 3. Add default classes

Put shared class and attribute defaults in the theme:

```php
$theme = new Theme(
    layers: ['forms/application'],
    defaults: [
        'input' => ['class' => 'field-input'],
        'email.input' => ['class' => 'email-input'],
        'label' => ['class' => 'field-label'],
    ],
);
```

The email-specific input default replaces the generic input default. Per-field
and local attributes then merge on top. Add a class or an integration attribute
for one field like this:

```php
$form->control('email', [
    'type' => 'email',
    'attributes' => ['class' => 'wide', 'x-model' => 'email'],
]);
```

Its input receives the classes `email-input wide` and the `x-model` attribute.
The attribute bag merges class tokens and ARIA references; it does not resolve
conflicting CSS utilities.

## 4. Customize another part when needed

Follow the same approach for a label, button, or form structure. For example,
this button element adds an application icon element:

```php [templates/element/forms/application/button/control.php]
<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
?>
<button<?= (string)$attributes ?>>
    <?= $this->element('icons/save') ?>
    <?= Html::text($label) ?>
</button>
```

Create the application's `icons/save` element before using this example.
`$form->submit('Save')` selects this button template through the same theme.

::: tip Changing every control
A layer's generic `control.php` applies to all types without a more specific
control template. Preserve fieldset and legend semantics for radios and checkbox
groups when replacing it. A type-specific element is useful for layouts that
only make sense for one control type, such as the email icon above.
:::

## Share a theme or add a variant

A layer can also point to another plugin's elements, for example
`MyDesignSystem.forms`. Order layers from the most specific application overrides
to the shared defaults. Bundled base elements are searched last.

For a named layout, create `variants/compact/control.php` in the layer and use
`['variant' => 'compact']` on a field. The variant is tried before type-specific
and generic files.

See the [template reference](./template-reference.md) for lookup order, `$parent()`,
all part names, and the variables supplied to each element.

## Use your CSS framework

Themes belong to your application or a separate plugin. Use the markup and
classes required by Tailwind, daisyUI, Bootstrap, or your own design system.
FormCompose's base theme supplies plain semantic HTML.

For Tailwind, write complete class names and include your theme element files in
source scanning. Integration attributes such as `x-model` and `data-*` can be
passed through field attributes.
