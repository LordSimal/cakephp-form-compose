---
title: How rendering works
description: Follow control() from a bound field to a CakePHP element, and learn how field parts and templates fit together.
---

# How a control becomes HTML

FormCompose separates two jobs: resolving a field from your data and rendering
its markup. A **bound field** connects those jobs. It holds the resolved state
and exposes methods that render the field's parts.

## Three terms to know

| Term | Meaning |
| --- | --- |
| **Control** | The complete field presentation: label, input, help, and errors. |
| **Input** | The actual browser control, such as a text input, select, or checkbox. |
| **Bound field** | Resolved field data and methods for rendering its parts. |

## From a method call to a file

Forms work with the bundled templates immediately:

```php
$form = $this->Form->bind($user);
echo $form->open();
echo $form->control('email', ['type' => 'email']);
echo $form->submit('Save');
echo $form->close();
```

To use your own markup for that control, select a PHP element directly. Inside
the open form, replace the `control()` call with:

```php
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/email',
]);
```

This selects `templates/element/forms/email.php`. The call performs these steps:

1. **Resolve the field.** Combine field options, request values, and the CakePHP
   context into a state containing the value, name, ID, constraints, and errors.
2. **Find the control element.** The `template` option names the application
   element to render. Without an override, use the bundled or selected theme's
   control element.
3. **Render the element.** Supply a `$field` object, prepared `$attributes`, and a
   `$parent()` callback. The element decides how to arrange its field parts.
4. **Render the parts it calls.** `$field->label()`, `$field->input()`,
   `$field->help()`, and `$field->errors()` each render their own element.

## What a control element receives

A control element is an ordinary CakePHP PHP element:

```php [templates/element/forms/email.php]
<?php
/**
 * @var \FormCompose\Field\BoundField $field
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

`$field` carries both the resolved state and the part methods. For example,
`$field->state->value` is the bound value and `$field->state->messages` contains
validation feedback. `$attributes` contains the theme and field attributes for
the control's container.

Your template places the parts. Each part handles its normal associations and
escaping. `input()` also registers the submitted names for form protection after
its template renders successfully.

::: details Where does the actual input tag come from?
By default, `input.php` calls `$field->renderInput($attributes)`. The selected field
type prepares browser values, choices, and any hidden fallback inputs. Standard
controls then render `native.php`, with type-specific versions such as
`textarea/native.php` and `select/native.php`.

Customize `control.php` to arrange the complete field, `input.php` to customize its
input rendering hook, or the native parts when changing standard input markup.
The [template reference](./template-reference.md#field-templates) lists these parts.
:::

## Render a complete control or compose its parts

These are alternative ways to present the same field inside an open form:

::: code-group

```php [Render a complete control]
echo $form->control('email', ['type' => 'email']);
```

```php [Arrange parts in the view]
$email = $form->field('email', ['type' => 'email']);
echo $email->label();
echo $email->input(['class' => 'contact-input']);
echo $email->help();
echo $email->errors();
```

:::

`field()` resolves an object without emitting HTML or registering an input.
Calling its part methods renders those parts. Use one presentation per input ID;
rendering both alternatives would duplicate the input.

## Blueprints follow the same rendering flow

A [blueprint](./blueprints.md) supplies reusable field configuration. Inside an
open form, render one with:

```php
use App\Form\ProfileForm;

echo $form->definition(ProfileForm::class);
```

The bundled definition layout renders its fields in order through the same
`control()` pipeline. A [custom definition layout](./composition.md#select-a-layout)
can arrange those fields in wrappers or grids and position embedded definitions.
Bound values, validation feedback, and submission bookkeeping use the same form
session in either case.

## Forms and buttons use PHP elements too

| Call | Element in a theme layer |
| --- | --- |
| `$form->open()` | `form/open.php` |
| `$form->submit('Save')` | `button/control.php` |
| `$form->close()` | `form/close.php` |

Form opening and closing elements receive prepared method, CSRF, and protection
fields. Retain those fields when customizing the form structure. The form session
coordinates submission state; elements render the prepared markup.

## Share presentation across forms

Once a control element works, a [theme](./themes.md) can apply it to matching
fields across forms. Themes provide shared element directories and default
attributes, with bundled elements as the fallback. The
[lookup reference](./template-reference.md#lookup-order) explains layers, types,
and variants.

[Install and build a form](./getting-started.md), or
[reuse field configuration with blueprints](./blueprints.md).
