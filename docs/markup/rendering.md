---
title: How rendering works
description: Understand field parts, PHP templates, themes, and variants, and choose how to change your form's HTML.
---

# How rendering works

A `control()` call renders a field through PHP elements. You can change one
element, select a named presentation, or share markup across the application.
These are different ways to choose files in the same rendering system.

## Start with the pieces of a field

A **control** is the complete field presentation. Its **input** is the browser
control that accepts a value. The other parts explain and label that input:

```text
control
├── label     “Email address”
├── input     <input type="email" …>
├── help      “Use your work address.”
└── errors    Validation feedback, when present
```

Each part has its own PHP element. A control element arranges the parts by
calling `$field->label()`, `$field->input()`, `$field->help()`, and
`$field->errors()`. Those calls render their own elements.

A **bound field** is the `$field` object: resolved data such as the value, ID,
and errors, plus methods for rendering these parts. Calling `resolve()` on its field builder
produces no HTML and registers no submitted inputs.

## Templates, themes, and variants

| Concept | What it does | Example |
| --- | --- | --- |
| **Template** | A PHP element that outputs one part's markup. | `forms/email.php` arranges an email control. |
| **Theme** | Chooses element directories and default HTML attributes for a form. | `new Theme(['forms/application'])` searches your application elements before the bundled ones. |
| **Layer** | One element directory in a theme's ordered search list. | `forms/application` maps to `templates/element/forms/application/`. |
| **Variant** | Requests a named alternative for the parts being rendered. | `variant('compact')` tries `variants/compact/control.php` for a field's control. |
| **Type** | Determines the control's input and submission behavior; also participates in template lookup. | `type('email')` uses an email input and allows an `email/control.php` layout. |

A theme can contain several variants. A variant is implemented by the files you
add to that theme; the name `compact` has no built-in styling or behavior.

## Follow a call into its PHP element

Here a field explicitly selects a control template. Read the call, then open the
second tab to see the file it renders:

::: code-group

```php [templates/Users/edit.php]
<?php
$form = $this->Form->form($user)
    ->bind();
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
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <div class="input-shell"><?= $field->input() ?></div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

:::

The `template` name is a CakePHP element name without `.php`. This call uses
`templates/element/forms/email.php` for the **control**. The template adds
`input-shell` around the input. Its label, input, help, and errors still use their
own template lookup.

`$attributes` holds the prepared container attributes; casting it to a string
escapes them. The part methods handle their usual escaping and associations.
`input()` also registers the submitted names for form protection after rendering
successfully.

::: details Where does the actual input tag come from?
The bundled `input.php` calls `$field->renderInput($attributes)`. The field type
prepares browser values, choices, and any hidden fallbacks. Standard controls
then render `native.php`, with type-specific files such as `select/native.php`
and `textarea/native.php`.

Change `control.php` to rearrange the field. Change native parts when you need to
change the actual input markup. See [field template contracts](/template-reference#field-templates).
:::

## Choose how to customize

| You want to… | Use | Continue with |
| --- | --- | --- |
| Add classes or integration attributes | `attributes` for the input; `partAttributes` for its surrounding parts | [Field attributes](/field-options#attributes) |
| Arrange one field directly in a view | `field()`, then its part methods | [Compose field parts](/field-parts) |
| Select a particular PHP file for a field or part | `template` or `templates` | [Use your own templates](/templates) |
| Apply shared markup and attribute defaults | A `Theme` | [Create a theme](/themes) |
| Choose a named presentation such as compact | `variant` and matching theme files | [Use variants](/variants) |
| Find out why a file was selected | `themeDebug` | [Debug template selection](/theme-debug) |

For a first customization, start with [one explicit template](/templates).
When that markup should become the default across forms, move it into a
[theme layer](/themes). Add [variants](/variants) when callers need a
choice of presentations within that theme.

## Field layout and form layout

An individual control template arranges one field's parts. A **blueprint layout**
arranges a collection of fields. A **complete-form layout** arranges the rendered
blueprint, error summary, and actions when using `render()`.

| Call | Default element path within a theme layer |
| --- | --- |
| `$form->control('email')` | `control.php` |
| `$form->blueprint('article')` | `blueprint/control.php` |
| `FormBuilder::render()` | `form/layout.php`, between opening and closing markup |
| `$form->open()` | `form/open.php` |
| `$form->submit('Save')` | `button/control.php` |
| `$form->close()` | `form/close.php` |

Types, variants, and explicit overrides can select more specific files. Each
rendered part performs its own lookup.

Blueprints are optional reusable field configuration; their controls use this
same rendering system. See [blueprint layouts](/blueprints#keep-control-of-the-layout)
and [complete-form layouts](/template-reference#complete-form-layouts) when
you need those larger structures. Preserve the supplied method, CSRF, and
protection inputs when replacing form opening or closing elements.
