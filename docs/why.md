---
title: Why FormCompose?
description: Understand the problem FormCompose solves and compare its PHP element templates with core FormHelper.
---

# Why FormCompose?

## The problem: markup becomes application code

A form may start with a label and input. A design system adds an icon, a hint,
feedback in a particular position, and several variants of that structure. The
same layout then needs to work across many forms and field types.

Core FormHelper already supports individual input methods, string templates, and
custom widgets. String templates work well for substituting markup around values.
As a layout needs PHP conditionals or other view elements, you may want a PHP
file as the place to compose it.

FormCompose makes that the standard rendering model: **a control renders a PHP
element with a bound field object**. Its label, input, help, and errors can be
arranged inside that file. Changing the layout leaves the field's data binding
and submission behavior in place.

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
documents its string templates and custom widgets. The difference here is the
built-in composition model; both systems have extension points.

## What changes in practice?

| Concern | Core FormHelper | FormCompose |
| --- | --- | --- |
| Reusable markup | String templates with placeholders; custom widgets | PHP elements that render field parts |
| A field's data | Accessed through helper methods and its current context | Exposed as a bound field's state |
| A one-off layout | Individual helper calls with a field name | Resolve one field, then call its part methods |
| Shared presentation | Configure templates and widgets | Select application or plugin theme layers |
| Form API | `create()`, field methods, `end()` | `bind()`, then session `open()`, field methods, `close()` |

FormCompose continues to use CakePHP contexts, schema and validation metadata,
routing, requests, and form protection. Validation rules and persistence stay in
your application's existing layers.

## What this enables

Use a theme to reuse your application's PHP markup across forms. Compose
individual field parts for custom layouts, or inspect resolved values,
constraints, and validation feedback before rendering.

::: info Experimental API
FormCompose is a separate API, with evolving behavior and no full core feature
parity. Existing FormHelper calls and custom widgets need to be ported.
:::

Next, [follow a control from data to HTML](./rendering.md), or
[install the plugin](./getting-started.md).
