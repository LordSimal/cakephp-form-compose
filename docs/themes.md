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
passed through [field attributes](./field-options.md#attributes). For the complete
`Theme`, attribute-bag, and trusted-markup APIs, see the
[template reference](./template-reference.md#theme-api).

## A complete starter theme

For a theme that handles ordinary inputs, selects, textareas, files, a single
checkbox, and grouped choices, start with a generic control element:

```php [templates/element/forms/starter/control.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$tag = $field->state->grouped() ? 'fieldset' : 'div';
?>
<<?= $tag ?><?= (string)$attributes->merge(['class' => 'form-field']) ?>>
    <?php if ($field->state->type === 'checkbox') { ?>
        <div class="checkbox-row">
            <?= $field->input() ?>
            <?= $field->label() ?>
        </div>
    <?php } else { ?>
        <?= $field->label() ?>
        <?= $field->input() ?>
    <?php } ?>
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
.form-field { border: 0; margin: 0 0 1.25rem; padding: 0; }
.field-label { display: block; font-weight: 600; margin-bottom: .375rem; }
.field-input:not([type="checkbox"]):not([type="radio"]) {
  box-sizing: border-box; width: 100%; padding: .625rem;
  border: 1px solid #64748b; border-radius: .375rem;
}
.checkbox-row { display: flex; align-items: baseline; gap: .5rem; }
.choice-input { margin-right: .5rem; }
.field-help { margin-top: .375rem; color: #475569; }
.field-errors { margin-top: .375rem; color: #b91c1c; }
[aria-invalid="true"] { outline: 2px solid #b91c1c; outline-offset: 2px; }
```

The browser regression suite uses this control element and the same defaults
with nested names, validation feedback, disabled controls, choices, and a file
upload. It checks native validity, unique IDs, matching ARIA references, and an
accepted multipart POST through CakePHP protection. The theme requires no
JavaScript or CSS framework.
