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
| Reuse a set of fields | Organize helper calls in application code or elements. | Use optional PHP definitions or attributed blueprints, with nested definitions and layouts. |
| Read values and validation feedback | CakePHP contexts and validation metadata. | The same CakePHP contexts and metadata, exposed through bound fields. |

Core's [template and widget APIs](https://book.cakephp.org/6.x/views/helpers/form.html#customizing-the-templates-formhelper-uses)
already support custom markup. FormCompose makes PHP elements and field objects
the starting point for that work.

## How the pieces fit together

<figure class="formcompose-architecture">
  <div class="formcompose-architecture-scroll" tabindex="0" role="region" aria-label="FormCompose architecture diagram; scroll horizontally on small screens">
    <img src="/diagrams/formcompose-architecture.svg" width="960" height="610" alt="CakePHP data and request feed a form session, which resolves bound fields. Optional definitions and blueprints supply field configuration. Bound fields render through PHP elements to HTML. The session owns submission tracking; successful input rendering registers submitted fields, and closing adds protection fields to the HTML." />
  </div>
  <figcaption>Data becomes field state, then markup. Submission tracking shares the same form session.</figcaption>
</figure>

`field()` resolves values, names, IDs, constraints, and errors without rendering
or registering inputs. PHP elements render the parts you call; successful input
rendering registers submitted fields. Form opening and closing prepare method,
CSRF, and form-protection fields when needed.

[Blueprints](./blueprints.md) supply optional configuration, and
[blueprint layouts](./blueprints.md#keep-control-of-the-layout) arrange fields;
[nested definitions](./composition.md) compose related sections. They
use this same rendering flow. Validation and persistence stay in your existing
CakePHP application layers.

## One call, your markup

The call selects an ordinary CakePHP element. That element controls the layout:

::: code-group

```php [Inside an open form]
echo $form->control('email', [
    'type' => 'email',
    'template' => 'forms/email',
    'help' => 'Use your work address.',
]);
```

```php [templates/element/forms/email.php]
<?php
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

:::

Move the hint, add another wrapper, or include an icon element here. Every call
using this element gets the change. A [theme](./themes.md) can apply it across
forms without repeating the `template` option.

::: info Adopting FormCompose
This is a separate form-building API for CakePHP 6. Existing FormHelper calls and
custom widgets need to be ported. The API is still evolving.
:::

[Build your first form](./getting-started.md) ·
[Follow a control from data to HTML](./rendering.md) ·
[Look up control options](./field-options.md)

<style>
.formcompose-architecture {
  margin: 24px 0;
}

.formcompose-architecture-scroll {
  overflow-x: auto;
  border-radius: 16px;
}

.formcompose-architecture-scroll:focus-visible {
  outline: 2px solid var(--vp-c-brand-1);
  outline-offset: 4px;
}

.formcompose-architecture img {
  width: 100%;
  min-width: 640px;
  max-width: none;
}

.formcompose-architecture figcaption {
  margin-top: 12px;
  color: var(--vp-c-text-2);
  font-size: 14px;
  line-height: 1.6;
}
</style>
