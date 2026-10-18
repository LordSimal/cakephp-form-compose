---
title: Create a theme
description: Connect a control() call to your own PHP element and reuse the layout across CakePHP forms.
---

# Create a theme

A theme chooses the PHP element directories and default HTML attributes used by
a form. Use it when your markup should become the shared default across forms.
You only need to provide the elements you want to change; bundled elements
supply the remaining parts.

A **layer** is one directory in the theme's search list. A **variant** is an
optional named presentation inside those directories. If these terms are new,
start with [How rendering works](/rendering).

This example gives email controls an icon beside the input. It changes their
presentation while keeping their bound values, feedback, and submission behavior.

## 1. Select your element directory {#select-theme}

Configure the theme once when loading the helper in `AppView::initialize()`:

```php [src/View/AppView.php]
<?php
declare(strict_types=1);

namespace App\View;

use Cake\View\View;
use FormCompose\Rendering\Theme;

class AppView extends View
{
    public function initialize(): void
    {
        parent::initialize();
        $this->addHelper('Form', [
            'className' => 'FormCompose.Form',
            'theme' => new Theme(layers: ['forms/application']),
        ]);
    }
}
```

Forms inherit that theme without repeating it at each binding:

```php [templates/Users/edit.php]
<?php $form = $this->Form->bind($user); ?>
<?= $form->open() ?>
<?= $form->control('email', ['type' => 'email']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

The `forms/application` prefix maps to your application's
`templates/element/forms/application/` directory. For this email control,
FormCompose looks for `email/control.php`, then a generic `control.php`.

You can also [select a theme for one form](#select-a-theme-for-one-form).

## 2. Write the control element

Create this file in the selected layer:

```php [templates/element/forms/application/email/control.php]
<?php
declare(strict_types=1);

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
without changing the theme. [Use your own templates](/templates)
shows a complete example and how to replace individual parts.
:::

## 3. Add default classes

Put shared class and attribute defaults in the theme. Replace the `Theme` passed
to the helper's `theme` setting with this instance, or pass it as `theme: $theme`
when binding a form:

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

The email-specific input class replaces the generic input class. Per-field
and local attributes then merge on top. Add a class or an integration attribute
for one field like this:

```php
echo $form->control('email', [
    'type' => 'email',
    'attributes' => ['class' => 'wide', 'x-model' => 'email'],
]);
```

Its input receives the classes `email-input wide` and the `x-model` attribute.
The attribute bag merges class tokens and ARIA references; it does not resolve
conflicting CSS utilities.

## 4. Customize another part when needed

Follow the same approach for a label, button, or form structure. For example,
this button element adds decorative markup before its label:

```php [templates/element/forms/application/button/control.php]
<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;
?>
<button<?= (string)$attributes ?>>
    <span aria-hidden="true">→</span>
    <?= Html::text($label) ?>
</button>
```

`$form->submit('Save')` selects this button template through the same theme.
The decorative span is hidden from assistive technology; the button label remains
its accessible name.

::: tip Changing every control
A layer's generic `control.php` applies to all types without a more specific
control template. Preserve fieldset and legend semantics for radios and checkbox
groups when replacing it. A type-specific element is useful for layouts that
only make sense for one control type, such as the email icon above.
:::

## Select a theme for one form

The configured theme also applies to helper `render()`, `postButton()`, and
`postLink()`. Use `theme:` for a per-call override:

```php
use FormCompose\Rendering\Theme;

$form = $this->Form->bind($user, theme: new Theme(['forms/compact']));
// Or use the bundled markup for this binding:
$form = $this->Form->bind($user, theme: 'Base');
```

Omitting `theme:` or passing null inherits the helper setting. An explicit theme
replaces the configured theme, including its layers and attribute defaults.
Compose multiple layers inside one `Theme` when you want them combined.

The default setting is `'Base'`; setting helper `theme` to null also selects the
base theme. `setConfig('theme', $theme)` changes future bindings and action calls.
Existing form sessions retain their selected theme, and other helper instances
keep their own settings. Unsupported strings, arrays, and unrelated objects fail
when the configured theme is selected.

## Share and layer themes

A theme can search more than one element directory. Put application overrides
first, a shared design-system plugin next, and let bundled base elements supply
any remaining parts:

```php
$theme = new Theme(layers: ['forms/application', 'MyDesignSystem.forms']);
```

The renderer searches every candidate in the first layer before moving to the
next. For an email control without a variant, it tries:

```text
forms/application/email/control
forms/application/control
MyDesignSystem.forms/email/control
MyDesignSystem.forms/control
FormCompose.form/base/email/control
FormCompose.form/base/control
```

The first existing file wins. An earlier layer's generic `control.php` therefore
wins over a later layer's type-specific element. Each field part repeats this
search independently.

### Wrap the next layer with `$parent()`

To decorate a shared layout, call `$parent()` from the application element:

```php [templates/element/forms/application/control.php]
<?php
declare(strict_types=1);

/** @var \Closure(): string $parent */
?>
<section class="application-field">
    <?= $parent() ?>
</section>
```

Here `$parent()` renders the control from the **next layer**, starting with
`MyDesignSystem.forms`. It skips the remaining candidates in the current layer.
Calling it from a variant also moves to the next layer; it does not try the
current layer's type-specific or generic control.

In an [explicit template override](/templates), `$parent()` starts normal
lookup from the first theme layer instead. This lets an exceptional element
wrap the themed presentation.

## Offer a named alternative

Use `variant => 'compact'` when a caller should choose an alternative layout
inside this theme. Create `variants/compact/control.php` in a layer; fields
requesting that variant try it before that layer's type-specific and generic
files. Other fields keep their ordinary layout.

Follow [Use variants](/variants) for a complete file, control call, styling,
and fallback explanation.

## Debug template selection

Enable `themeDebug` to see attempted candidates and the selected element in page
source. Follow [Debug template selection](/theme-debug) for configuration,
an annotated trace, and renderer details.

## Use your CSS framework

Themes belong to your application or a separate plugin. Use the markup and
classes required by Tailwind, daisyUI, Bootstrap, or your own design system.
FormCompose's base theme supplies plain semantic HTML.

For Tailwind, write complete class names and include your theme element files in
source scanning. Integration attributes such as `x-model` and `data-*` can be
passed through [field attributes](/field-options#attributes). For the complete
`Theme`, attribute-bag, and trusted-markup APIs, see the
[template reference](/template-reference#theme-api).

## A complete starter theme

The email example changes only email fields. This optional starter provides a
generic layout and stylesheet for text inputs, selects, textareas, files, a
single checkbox, and grouped choices.

::: details Complete starter: control element, form, and CSS

Start with a generic control element:

```php [templates/element/forms/starter/control.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$tag = $field->state->grouped() ? 'fieldset' : 'div';
?>
<<?= $tag ?><?= (string)$attributes->merge(['class' => 'form-field']) ?>>
    <?php if ($field->state->type === 'checkbox'): ?>
        <div class="checkbox-row">
            <?= $field->input() ?>
            <?= $field->label() ?>
        </div>
    <?php else: ?>
        <?= $field->label() ?>
        <?= $field->input() ?>
    <?php endif; ?>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</<?= $tag ?>>
```

Grouped fields get a fieldset and the base label part supplies a legend. A single
checkbox puts its label after the input. Hidden fields bypass this wrapper.
All other parts fall back to the bundled elements, retaining escaped values,
choice labels, help/error IDs, and hidden fallbacks.

Use the theme in an existing article view:

```php [templates/Articles/edit.php]
<?php
use FormCompose\Rendering\Theme;

$form = $this->Form->bind($article, theme: new Theme(
    layers: ['forms/starter'],
    defaults: [
        'input' => ['class' => 'field-input'],
        'choice' => ['class' => 'choice-input'],
        'label' => ['class' => 'field-label'],
        'help' => ['class' => 'field-help'],
        'errors' => ['class' => 'field-errors'],
    ],
));
?>
<?= $form->open(['type' => 'file']) ?>
<?= $form->control('title', ['help' => 'Shown in the article list.']) ?>
<?= $form->control('body', ['type' => 'textarea']) ?>
<?= $form->control('author_id', ['options' => $authors, 'empty' => 'No author']) ?>
<?= $form->control('published', ['type' => 'checkbox']) ?>
<?= $form->control('visibility', [
    'type' => 'radio',
    'options' => ['public' => 'Public', 'private' => 'Private'],
]) ?>
<?= $form->control('topics', [
    'type' => 'multicheckbox',
    'options' => ['php' => 'PHP', 'cakephp' => 'CakePHP'],
]) ?>
<?= $form->control('attachment', ['type' => 'file']) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

Add these classes to your application's existing stylesheet, adjusting colors
to your design system:

```css
.form-field {
  border: 0;
  margin: 0 0 1.25rem;
  padding: 0;
}

.field-label {
  display: block;
  font-weight: 600;
  margin-bottom: .375rem;
}

.field-input:not([type="checkbox"]):not([type="radio"]) {
  box-sizing: border-box;
  width: 100%;
  padding: .625rem;
  border: 1px solid #64748b;
  border-radius: .375rem;
}

.checkbox-row {
  display: flex;
  align-items: baseline;
  gap: .5rem;
}

.choice-input {
  margin-right: .5rem;
}

.field-help {
  margin-top: .375rem;
  color: #475569;
}

.field-errors {
  margin-top: .375rem;
  color: #b91c1c;
}

[aria-invalid="true"] {
  outline: 2px solid #b91c1c;
  outline-offset: 2px;
}
```

The browser regression suite uses this control element and the same defaults
with nested names, validation feedback, disabled controls, choices, and a file
upload. It checks native validity, unique IDs, matching ARIA references, and an
accepted multipart POST through CakePHP protection. The theme requires no
JavaScript or CSS framework.

:::
