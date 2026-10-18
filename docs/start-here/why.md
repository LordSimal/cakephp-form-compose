---
title: Why FormCompose?
description: Compare FormCompose with CakePHP's FormHelper and see how bound fields, PHP elements, and submission protection fit together.
---

# Why FormCompose?

**Write your form markup in PHP elements. Reuse it across your application.**
FormCompose is for forms that need their own icons, wrappers, hints, and error
placement, while using CakePHP's field values and validation feedback.

## What does it solve?

A custom email control might need an icon beside the input, a hint underneath,
and errors outside the input wrapper. Repeating that structure in every form
makes changes tedious. FormCompose lets you put it in one PHP element, then
render it with a single `control()` call wherever you need it.

| You want to… | Core FormHelper | FormCompose |
| --- | --- | --- |
| Reuse a custom control layout | Configure string templates with placeholders; use widgets for custom controls. | Write a PHP element that places the label, input, help, and errors. |
| Add an icon, wrapper, or conditional markup | Arrange template fragments and pass template variables. | Use HTML, PHP conditionals, and element includes in the control element. |
| Arrange one field differently | Call individual helper methods with the field name and options. | Resolve one bound field, then place its parts in your view. |
| Reuse a set of fields | Organize helper calls in application code or elements. | Use optional PHP blueprints, with embedded blueprints and application-owned layouts. |

Core's [template and widget APIs](https://book.cakephp.org/6.x/views/helpers/form.html#customizing-the-templates-formhelper-uses)
already support custom markup. FormCompose makes PHP elements and field objects
the starting point for that work.

## One call, your markup

The call selects an ordinary CakePHP element. That element controls the layout:

::: code-group

```php [Inside an open form]
echo $form->control('email')
    ->type('email')
    ->template('forms/email')
    ->help('Use your work address.');
```

```php [templates/element/forms/email.php]
<?php

/**
 * @var \FormCompose\Form\Field\BoundField $field
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

:::

Move the hint, add another wrapper, or include an icon element here. Every call
using this element gets the change. A [theme](/themes) can apply it across
forms without repeating the `template` option.

[Build your first form](/getting-started) ·
[Follow a control from data to HTML](/rendering) ·
[Look up field configuration](/field-options)
