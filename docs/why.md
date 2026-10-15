---
title: Why FormCompose?
description: FormCompose replaces CakePHP's FormHelper with composable bound fields and PHP element templates.
---

# A replacement for CakePHP's FormHelper

**FormCompose replaces CakePHP's FormHelper as your form-building API.** Its
helper binds form data, opens and closes forms, renders controls, and prepares
CSRF and form-protection fields.

The central change is how you build the markup: **a control renders an ordinary
PHP element with a bound field object**. That element decides where the label,
input, help text, and validation errors appear. You can reuse the layout through
a theme or arrange individual field parts directly in your view.

FormCompose uses CakePHP's contexts, association conventions, validation metadata,
routing, and form protection. Your application keeps its existing validation and
persistence workflow. Adopting FormCompose means porting your form calls to its
own API, described in the comparison below.

## Why rethink form rendering?

A form may start with a label and input. A design system adds an icon, a hint,
feedback in a particular position, and several variants of that structure. The
same layout then needs to work across many forms and field types.

Core FormHelper assembles controls using string templates and widgets. Adapting
that structure to a design system can spread layout decisions across template
configuration, custom widgets, and helper overrides.

FormCompose puts the control layout in a PHP element, with normal conditionals,
element includes, and access to each field part. Tailwind, daisyUI, Bootstrap, or
your own design system can use the markup they require. Changing that layout
preserves the field's data binding and submission behavior.

## String templates and PHP elements

For example, a wrapper in core FormHelper can be configured like this:

```php [Core FormHelper]
$this->Form->setTemplates([
    'inputContainer' => '<div class="field">{{content}}</div>',
]);
```

The placeholder receives the control's assembled content. FormCompose gives the
PHP element access to the individual parts:

```php [FormCompose control.php]
<?php
/** @var \FormCompose\Field\BoundField $field */
?>
<div class="field">
    <?= $field->label() ?>
    <div class="input-shell"><?= $field->input() ?></div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

That element can contain normal PHP logic, include an icon element, or add a
wrapper around just the input. The label, input, help, and errors themselves also
render through replaceable PHP elements.

Core's [FormHelper guide](https://book.cakephp.org/5/en/views/helpers/form.html#customizing-the-templates-formhelper-uses)
documents its string templates and custom widgets. FormCompose makes PHP elements
and independently renderable field parts the foundation of its rendering API.

## What changes in practice?

| Concern | Core FormHelper | FormCompose |
| --- | --- | --- |
| Reusable markup | String templates with placeholders; custom widgets | PHP elements that render field parts |
| A field's data | Accessed through helper methods and its current context | Exposed as a bound field's state |
| A one-off layout | Individual helper calls with a field name | Resolve one field, then call its part methods |
| Shared presentation | Configure templates and widgets | Select application or plugin theme layers |
| Form API | `create()`, field methods, `end()` | `bind()`, then session `open()`, field methods, `close()` |

## What this enables

Use a theme to reuse your application's PHP markup across forms. Compose
individual field parts for custom layouts, or inspect resolved values,
constraints, and validation feedback before rendering.

::: info Experimental API
The replacement API is still evolving. Existing FormHelper calls and custom
widgets need to be ported to FormCompose's sessions, field types, and templates.
:::

Next, [follow a control from data to HTML](./rendering.md), or
[install the plugin](./getting-started.md).
