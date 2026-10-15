---
title: Template reference
description: Look up field, form, and button elements, their variables, layer precedence, variants, and attribute options.
---

# Template reference

Use this page after [creating a theme](./themes.md). All paths below are relative
to a theme layer, such as `templates/element/forms/application/`.

## Lookup order

For each layer, FormCompose tries a variant, then a type-specific file, then a
generic part. For an email control with `variant => 'compact'`, that means:

```text
variants/compact/control.php
email/control.php
control.php
```

Layers are searched in their supplied order. The bundled `FormCompose.form/base`
layer is last. The first matching element is rendered.

Form and button paths have scope prefixes. A primary submit button tries:

```text
button/variants/primary/control.php
button/submit/control.php
button/control.php
```

Variants, types, and part names use lowercase letters, numbers, underscores, and
hyphens, beginning with a letter.

### Explicit overrides

Use singular `template` to select the complete field control element. It takes
priority over variant and layer lookup:

```php
$form->control('email', [
    'type' => 'email',
    'template' => 'forms/special-email',
]);
```

Here the control uses `templates/element/forms/special-email.php`. Use CakePHP
element names without `.php`, including plugin-qualified names such as
`MyDesignSystem.forms/email`. The [control guide](./usage.md#choose-a-template-for-one-control)
shows the call and a complete element together.

Plural `templates` maps individual parts to elements. The existing
`templates['control']` spelling remains supported:

```php
$form->control('email', [
    'type' => 'email',
    'templates' => [
        'control' => 'forms/special-email',
        'input' => 'forms/email-input',
        'help' => 'forms/email-help',
    ],
]);
```

Each entry names its own element. Parts without an explicit override keep their
normal theme lookup. You can combine `template` with `templates` entries for other
parts. Supplying different values for `template` and `templates['control']` throws
`InvalidArgumentException` during field resolution; identical values are accepted.
The shorthand must be a nonempty string. Conflict checking uses the merged
definition and per-call options.

Both spellings work through `field()` and reusable field definitions too. During
resolution, `template` is normalized to `templates['control']`, so custom renderers
receive the same canonical options.

The singular shorthand applies to field controls. Hidden fields continue to
render their input part directly. Set per-form `templates` using `open`, `close`,
or `hidden` keys in `bind()`; set per-button `templates` using the `control` key in
`submit()`.

### Calling the parent layer

Elements receive a `$parent()` closure that renders the same part in the next
layer. For example, an application can wrap a shared control:

```php
<?php /** @var \Closure(): string $parent */ ?>
<section class="application-field">
    <?= $parent() ?>
</section>
```

In an explicit override, `$parent()` resumes normal theme lookup. Within a layer,
it moves to the next layer rather than trying another file in that same layer.

## Field templates

Field elements receive `$field` (`BoundField`) and `$attributes` (`Attributes`).
The field's immutable `state` exposes its path, name, ID, type, value, messages,
constraints, and options.

| Part | Purpose |
| --- | --- |
| `control.php` | Arrange the complete field. Receives the container attributes. |
| `label.php` | Render a label, or a legend for a grouped control. |
| `input.php` | Render the input hook; normally calls `$field->renderInput($attributes)`. |
| `help.php` | Render enabled help content. |
| `errors.php` | Render enabled validation messages. |

Type-specific overrides work for every part, for example `email/label.php`.
Hidden fields render their input parts directly rather than using the outer
`control.php` layout.

### Standard input markup

The standard field type prepares browser values and choices, then renders:

| Part | Purpose |
| --- | --- |
| `native.php` | The ordinary input tag. |
| `textarea/native.php` | Textarea structure and content. |
| `select/native.php` | Select structure and its options. |
| `radio/native.php`, `multicheckbox/native.php` | Grouped choices. |
| `fallback.php` | An unchecked or empty-selection hidden input. |
| `option.php`, `option-group.php` | Select options and groups. |
| `choice.php`, `choice-group.php` | Radio/checkbox choices and groups. |

Native templates receive additional prepared data as needed, such as `$value`,
`$choices`, and `$renderChoice`. Choice templates receive a `$choice` object with
its label, attributes, children, and presentation attributes.

## Form and button templates

| Element | Purpose |
| --- | --- |
| `form/open.php` | Opening form tag and initial method/CSRF inputs. |
| `form/close.php` | Final protection inputs and closing form tag. |
| `form/hidden.php` | One infrastructure hidden input. |
| `button/control.php` | Submit, reset, or ordinary button structure. |

Forms also support method-specific paths such as `form/patch/open.php`.

Opening and closing templates receive `$form`, prepared `$state`, `$attributes`,
and a `$hidden` list of attribute bags. Render the supplied hidden inputs with
`$form->hiddenFields($hidden, $state->presentation)`. The hidden element receives
an `$attributes` bag for one input.

Retain the supplied infrastructure fields when replacing opening and closing
markup. Token generation belongs to the form lifecycle; the elements output its
prepared fields.

Button templates receive `$form`, `$label`, and `$attributes`. Use
`Html::text($label)` to escape ordinary labels while preserving explicitly trusted
`Html` labels.

## Attributes

Theme defaults use part keys, with optional type prefixes:

| Defaults key | Applies to |
| --- | --- |
| `container`, `input`, `label`, `help`, `errors` | Field parts. |
| `email.input` | Email input defaults replacing generic input defaults. |
| `form.open`, `form.close`, `form.hidden` | Form parts. |
| `button` | Buttons. |
| `option`, `choice`, `choice-label`, `choice-container`, `choice-group` | Choice parts. |

Use a field's `attributes` option for input attributes and `partAttributes` for
other parts:

```php
$form->control('email', [
    'type' => 'email',
    'attributes' => ['class' => 'wide'],
    'partAttributes' => ['label' => ['class' => 'caption']],
]);
```

Attribute bags merge class tokens and `aria-describedby`/`aria-labelledby`
references. Null removes an attribute; `replace()` replaces tokens explicitly.
False omits an attribute and true emits its name alone. Values are escaped when
the bag is serialized.

Configure input identity and behavior, such as `name`, `id`, `type`, `value`, and
`secure`, as field options rather than through input attribute bags.
