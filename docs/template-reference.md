---
title: Template reference
description: Look up field, form, and button elements, their variables, layer precedence, variants, and attribute options.
---

# Template reference

Use this page to look up element contracts after [creating a theme](./themes.md).
For control configuration, see [Field options](./field-options.md). All paths below are relative
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

Form, button, and blueprint paths have scope prefixes. A primary submit button tries:

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

Both spellings work through `field()` and blueprint field declarations too. During
resolution, `template` is normalized to `templates['control']`, so custom renderers
receive the same canonical options.

The singular shorthand applies to field controls and blueprint layouts. Hidden fields continue to
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

## Template data

Pass custom presentation data through the `templateData` option. Each element
receives it as a `$templateData` array alongside its prepared variables:

```php
echo $form->control('email', [
    'template' => 'forms/special-email',
    'templateData' => ['prefix' => '@', 'account' => $account],
]);
```

The [control guide](./usage.md#choose-a-template-for-one-control) shows the call and
its PHP element together. No custom keys are extracted into local variables:
`$templateData['field']` is ordinary custom data and cannot replace `$field`.
The same applies to `$attributes`, `$parent`, and other prepared variables.

### Availability and scope

| Supplied through | Available to |
| --- | --- |
| `control()` or `field()` options | That field's control, label, input, help, errors, native markup, choices, and fallback elements. |
| `bind()` options or `open()` options | That form's opening, closing, and infrastructure hidden-input elements. |
| `submit()` options | That button's element. |
| `Form` attribute, builder `presentation()`, or `blueprint()` options | That blueprint's layout, independently of child field or embedded layout data. |

Theme layers, explicit overrides, and `$parent()` calls receive the same bag for
their field, form, or button. Form data does not become a default for its fields
or buttons; supply their own data explicitly. Metadata is removed from form and
button HTML attributes before rendering.

For example, form and button elements can use separate configuration:

```php
$form = $this->Form->bind($article, [
    'templateData' => ['layout' => 'stacked'],
]);

echo $form->open();
echo $form->control('title');
echo $form->submit('Save', ['templateData' => ['icon' => 'save']]);
echo $form->close();
```

Here form elements receive `['layout' => 'stacked']`, the title field receives
`[]`, and the button receives `['icon' => 'save']`.

### Values, replacement, and escaping

When omitted, the bag is `[]`. Supplying a non-array container, including explicit
null, throws `InvalidArgumentException` before the affected template renders or
its submission is registered. Values *inside* the array may include objects,
nested arrays, false, zero, null, and explicitly trusted `Html` instances. Objects
keep their identity; metadata is not serialized, stringified, or pre-escaped.

Existing option precedence applies: per-call field metadata replaces the entire
definition bag, and `open()` metadata replaces the entire binding bag. Pass `[]`
to clear earlier metadata. To keep existing keys, combine the arrays explicitly
before supplying the new bag, for example with `array_replace()`.

::: tip Escape at the point of output
Templates receive original PHP values. Use `Html::text()` for text; it escapes
ordinary strings and preserves explicitly trusted `Html`. Use an `Attributes` bag
when intentionally putting a value into an HTML attribute.
:::

Custom renderers receive the validated bag through
`$node->variables['templateData']`, including `[]` when none was supplied.

## Field templates

Field elements receive `$field` (`BoundField`), `$attributes` (`Attributes`), and
the `$templateData` array.
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
an `$attributes` bag for one input. All three form elements also receive the
form's `$templateData` bag.

Retain the supplied infrastructure fields when replacing opening and closing
markup. Token generation belongs to the form lifecycle; the elements output its
prepared fields.

Button templates receive `$form`, `$label`, `$attributes`, and `$templateData`. Use
`Html::text($label)` to escape ordinary labels while preserving explicitly trusted
`Html` labels.

## Blueprint layouts

Blueprint nodes use scope `blueprint`, type `default`, and part `control`.
Each theme layer tries `blueprint/variants/{variant}/control.php` when selected,
then `blueprint/default/control.php`, then `blueprint/control.php`.
The bundled default renders ordered fields and embeds without a wrapper.

| Variable | Meaning |
| --- | --- |
| `$form` | A scoped `BlueprintView` with `has()`, `field()`, `control()`, and `embedded()`. |
| `$definition` | Internal compiled layout metadata, relative to this mount; use `$form` for field access. |
| `$attributes` | Escaped attribute bag from theme defaults and local definition options. |
| `$templateData` | This blueprint layout's application-owned presentation data. |
| `$parent` | The standard callback to resume theme lookup. |

Use theme defaults keyed by `blueprint.control` for layout attributes. The default
layout adds no element, so a custom wrapper must emit `$attributes` when needed.
Selecting a layout does not forward its data to individual controls or children.
See [blueprint layouts](./blueprints.md#keep-control-of-the-layout) for a complete
class/element example, and [nested blueprints](./composition.md) for child layouts.

## Repeated-embed layouts

The `embed-many` scope renders `control` and `errors` through the standard theme
lookup. The control receives `$embed` (`RepeatedView`), `$rows` (original keys
mapped to scoped `BlueprintView` instances), `$attributes`, and `$templateData`.
The error element receives `$embed`, `$messages`, `$attributes`, and `$templateData`.

The base control renders rows and direct collection errors without a wrapper.
Emitted wrapper attributes require a custom control element; render `$attributes`
on its wrapper to preserve configured attributes and duplicate-ID checks.

Use a normal `foreach` for row wrappers and `$row->render()` for each child's own
layout. There is no `row` template part. See the
[complete repeated layout example](./repeated-embeds.md#arrange-rows-with-a-php-element).

## Error-summary elements

The `error-summary` scope renders `control`. Its element receives `$form`,
`$entries` (`list<FeedbackEntry>`), `$title`, `$attributes`, `$titleAttributes`,
and `$templateData`. An entry exposes `label` (text or trusted `Html`), `messages`
(escaped strings), and `target` (an emitted DOM ID or null for an unlinked message).

The default section and heading IDs are associated with `aria-labelledby` and
checked for collisions. Preserve those supplied attributes in custom markup.
See the [complete summary element](./error-summaries.md#customize-the-summary-element).

## Attributes

Standalone action buttons use `button/control.php`; action links use
`action/control.php`. Both receive `$form`, `$label`, `$attributes`, and
`$templateData`. Preserve the supplied trigger associations in custom markup.
The external action script is registered through CakePHP's HTML helper and the
`script` view block; control loading with the `autoActionScript` helper option. See [POST action templates](./post-actions.md#options-and-presentation).

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


## Theme API

`FormCompose\Rendering\Theme` is immutable. Pass it through `bind(..., theme: $theme)`:

```php
use FormCompose\Rendering\Theme;

$theme = new Theme(
    layers: ['forms/application'],
    defaults: [
        'input' => ['class' => 'field-input'],
        'email.input' => ['class' => 'email-input'],
        'blueprint.control' => ['class' => 'form-section'],
    ],
);
$form = $this->Form->bind($article, theme: $theme);
```

`new Theme(array $layers = [], array $defaults = [])` stores readonly `layers`
and `defaults`. Layers are element prefixes in priority order. Empty prefixes or
prefixes containing `..` throw `InvalidArgumentException`. `Theme::base(): self`
returns a theme with no custom layers or defaults; bundled elements remain available.

`attributes(string $part, string $type): Attributes` starts with generic defaults,
then replaces matching keys with type-specific defaults. An email class default
replaces the generic input class; subsequent field/local attributes merge on top.
This distinction matters when using CSS utility classes.

## Attribute bag API

`FormCompose\Rendering\Attributes` stores readonly `values` and escapes them when
cast to a string. It includes a leading space when attributes are present, so
`<div<?= (string)$attributes ?>>` is valid.

| Signature | Behavior |
| --- | --- |
| `new Attributes(array $values = [])` | Validates attribute names and normalizes class tokens. |
| `merge(array $values): self` | New bag; combines class and ARIA ID-reference tokens, replaces other keys. |
| `replace(array $values): self` | New bag; replaces matching keys without token merging. |
| `has(string $name): bool` | Whether the attribute would be emitted; null/false count as absent. |
| `__toString(): string` | Escaped attributes; false/null omitted, true emitted as a bare name. |

```php
use FormCompose\Rendering\Attributes;

$attributes = new Attributes(['class' => 'field', 'required' => true]);
$wide = $attributes->merge(['class' => ['wide' => true, 'hidden' => false]]);
// (string)$wide: ' class="field wide" required'
$replacement = $wide->replace(['class' => 'compact', 'required' => null]);
// (string)$replacement: ' class="compact"'
```

Arbitrary integration attributes such as `data-*`, `x-model`, `@click`, and
`:class` retain their names. Class values support strings, arrays of tokens, and
conditional class maps. Invalid attribute names throw `InvalidArgumentException`.
The bag does not resolve conflicting CSS utilities.

## Trusted markup

`FormCompose\Rendering\Html` explicitly marks developer-controlled markup for
labels, help, or button content. It does not sanitize HTML.

```php
use FormCompose\Rendering\Html;

// Inside an open form.
echo $form->submit(new Html('<strong>Save article</strong>'));
```

`new Html(string $value)` exposes readonly `value`.
`Html::text(string|Html $text): string` escapes ordinary strings and preserves an
explicit `Html` value. Custom templates should use this method when outputting
text. Attribute values always go through the attribute bag's escaping.

## Custom widget bodies

[Custom field types](./custom-fields.md) use two additional field body parts:

| Part | Prepared locals | Contract |
| --- | --- | --- |
| `widget` | `$field`, `$attributes`, scalar `$value` | `SingleInputType`: emit one editable input with the supplied name and ID. |
| `composite` | `$field`, `$attributes`, `$inputs` keyed by relative path | `CompositeType`: arrange child controls or their independently renderable parts. |

Both receive `$templateData` and `$parent()`, and support normal layers, variants,
and explicit `templates` overrides. Outer control elements can access children
through `$field->inputs`. Composite bodies receive wrapper-ready attributes with their supplied ID and
feedback references. `CompositeType` filters input-only attributes before rendering
the body, while child inputs retain their effective constraints.
The type declaration supplies semantics, and the PHP element owns layout.
