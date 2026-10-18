---
title: Use variants
description: Build a compact field variant from its PHP file to the control call, and understand fallback, scope, and layer precedence.
---

# Use variants

A **variant** is a name that asks the theme for alternative templates. For
example, `variant('compact')` asks for files under `variants/compact/`.
You create those files and their styling. Setting the option alone does not
make a field smaller or add a CSS class.

Use variants when the same theme needs several presentations, such as ordinary
fields and compact fields. Use an [explicit template](/templates) when you
want to select a particular element by its full name.

## 1. Create the variant element

Assume your theme layer is `forms/application`. Add a control element at:

```text
templates/element/forms/application/
├── control.php                   Optional default layout
└── variants/
    └── compact/
        └── control.php           Layout requested by variant('compact')
```

This variant places the label and input side by side. It uses a fieldset for
grouped choices, letting the label part render a legend:

```php [templates/element/forms/application/variants/compact/control.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
$tag = $field->state->grouped() ? 'fieldset' : 'div';
?>
<<?= $tag ?><?= (string)$attributes->merge(['class' => 'compact-field']) ?>>
    <?= $field->label() ?>
    <div class="compact-field-body">
        <?= $field->input() ?>
        <?= $field->help() ?>
        <?= $field->errors() ?>
    </div>
</<?= $tag ?>>
```

Add these rules to your application's stylesheet:

```css
.compact-field {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  gap: .75rem;
  border: 0;
  margin: 0 0 .75rem;
  padding: 0;
}

.compact-field-body {
  min-width: 0;
}
```

## 2. Request it on a field

Select the layer and pass the variant on the field that needs it:

```php [templates/Users/edit.php]
<?php

use FormComposeTheme;
use Rendering\Theme;

$form = $this->Form
    ->form($user)
    ->theme(new Theme(['forms/application']))
    ->bind();
?>
<?= $form->open() ?>
<?= $form->control('email')->type('email')->variant('compact') ?>
<?= $form->control('name') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

The email field uses `variants/compact/control.php`, adding the `compact-field`
wrapper and side-by-side layout. The name field uses the ordinary control layout.
Both keep their normal values, validation feedback, and submission behavior.

If you already [configured this theme on the helper](/themes#select-theme),
you can omit the `theme()` call.

## 3. Understand what gets selected

For the email control above, each layer tries these files in order:

```text
variants/compact/control.php     Named variant
email/control.php                Type-specific layout
control.php                      Generic layout
```

The first existing file wins. If no compact control file exists, lookup continues
to the type-specific and generic files. A valid variant name with no matching
files therefore falls back to the ordinary presentation.

The variant is considered separately for **every part of that field**. For the
label it tries `variants/compact/label.php`, then `email/label.php`, then
`label.php`. You can add only `control.php` and let all its parts fall back, or
also provide `label.php`, `help.php`, or other parts in the variant directory.

::: info A field variant applies across field types
`variants/compact/control.php` can be selected for email, text, select, or any
other field type requesting `compact`. There is no
`variants/compact/email/control.php` lookup. Use `email/control.php` for an
automatic email-specific layout, or an explicit `template` for a particular file.
:::

Variant names begin with a lowercase letter and contain only lowercase letters,
digits, underscores, and hyphens. For example, `compact` and `inline_help` are
valid. An invalid name throws `InvalidArgumentException` during theme lookup.

## Layers take priority before specificity

With `new Theme(['forms/application', 'MyDesignSystem.forms'])`, the renderer
searches all candidates in the application layer before moving to the shared
plugin layer:

```text
1. forms/application/variants/compact/control
2. forms/application/email/control
3. forms/application/control
4. MyDesignSystem.forms/variants/compact/control
5. MyDesignSystem.forms/email/control
6. MyDesignSystem.forms/control
7. Bundled base candidates
```

An application `control.php` wins over a shared plugin's compact variant. If you
want the shared variant to be reachable, avoid a generic control override in the
earlier layer, or delegate with [`$parent()`](/themes#share-and-layer-themes).
Explicit `template` or `templates` selections take priority over this entire
search for the selected part.

## Buttons and form layouts have their own variants

The option applies to the object receiving it. A field variant does not change
the submit button, and a form variant is not inherited by its fields or buttons.

For a primary submit button, pass the variant on `submit()` and create its element
under the `button` scope:

::: code-group

```php [Inside an open form]
echo $form->submit('Save')->variant('primary');
```

```php [templates/element/forms/application/button/variants/primary/control.php]
<?php
declare(strict_types=1);

use FormComposeHtml;
use Rendering\Html;

?>
<button<?= (string)$attributes->merge(['class' => 'button-primary']) ?>>
    <?= Html::text($label) ?>
</button>
```

:::

This adds your `button-primary` class. Style it in the application stylesheet.

| Variant supplied to | Example candidate in a theme layer |
| --- | --- |
| `control()` or `field()` | `variants/compact/control.php` |
| `submit()` | `button/variants/primary/control.php` |
| Form builder `variant()` | `form/variants/compact/open.php` and `form/variants/compact/close.php` |
| Form builder `variant()` | `form/variants/compact/layout.php` |
| Blueprint occurrence `variant()` | `blueprint/variants/compact/control.php` |

## When a variant seems to do nothing

Check that the form uses the theme containing your file, that the file path
matches the requested scope and part, and that no explicit override or earlier
layer wins. A variant needs its own markup or classes to change the presentation.

Enable [template debugging](/theme-debug) to see every attempted candidate and
the element that matched. See the [lookup reference](/template-reference#lookup-order)
for the complete rules.
