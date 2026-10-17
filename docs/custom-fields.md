---
title: Custom field types
description: Build reusable field widgets with AsFieldType, ordinary child fields, and application-owned PHP elements.
---

# Custom field types

A custom type gives a reusable widget a name and input semantics. Its PHP element
owns the markup. For example, a booking date range can render two ordinary date
fields in one control:

::: code-group

```php [templates/Bookings/edit.php]
<?php $form = $this->Form->bind($booking); ?>
<?= $form->open() ?>
<?= $form->control('period', [
    'type' => 'date-range',
    'label' => 'Your stay',
    'help' => 'Choose arrival and departure dates.',
    'templates' => ['composite' => 'forms/fields/date-range'],
]) ?>
<?= $form->submit('Book') ?>
<?= $form->close() ?>
```

```php [templates/element/forms/fields/date-range.php]
<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 */
?>
<div<?= (string)$attributes->replace([
    'name' => null, 'required' => null, 'disabled' => null, 'readonly' => null,
])->merge(['class' => 'date-range']) ?>>
    <div class="date-range-start">
        <?= $inputs['start']->label() ?>
        <?= $inputs['start']->input() ?>
        <?= $inputs['start']->errors() ?>
    </div>
    <div class="date-range-end">
        <?= $inputs['end']->label() ?>
        <?= $inputs['end']->input() ?>
        <?= $inputs['end']->errors() ?>
    </div>
</div>
```

:::

The outer control renders a fieldset, legend, group help, and group errors. The
`composite` element arranges its inputs. Classes here are application styling
hooks; FormCompose requires no CSS framework or JavaScript.

## Register a type with AsFieldType

Put application types in `src/Form/Field/`, using the `App\Form\Field` namespace.
`#[AsFieldType('date-range')]` registers the alias selected by the `type` option:

```php [src/Form/Field/DateRangeType.php]
<?php
declare(strict_types=1);

namespace App\Form\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\BoundField;
use FormCompose\Field\CompositeType;
use function Cake\I18n\__;

#[AsFieldType('date-range')]
class DateRangeType extends CompositeType
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function inputs(BoundField $field): array
    {
        return [
            'start' => ['type' => 'date', 'label' => __('Arrival')],
            'end' => ['type' => 'date', 'label' => __('Departure')],
        ];
    }
}
```

The plugin discovers direct and nested PHP files under `src/Form/` in the
application and loaded plugins. Classes must be PSR-4 autoloadable. Aliases start
with a lowercase letter and contain lowercase letters, digits, underscores, or
hyphens. Duplicate aliases fail. Qualify plugin aliases, for example
`#[AsFieldType('travel-date-range')]`.

Discovery reads metadata without constructing types. A discovered type is
constructed when its rendering, registration, or composite children are first
requested, and reused within that binding. Inspecting the root field's state or
attributes does not construct it. Constructors can receive services through the
[configured BlueprintFactory and application container](./blueprint-configuration.md).
Keep per-field state on the bound fields; a type instance can serve several fields.

Explicit instances override discovered aliases:

```php
use App\Form\Field\DateRangeType;

$form = $this->Form->bind($booking, [
    'types' => ['date-range' => new DateRangeType()],
]);
```

Clear or warm discovery metadata during deployment as described in
[discovery and cache configuration](./blueprint-configuration.md#discovery-and-cache-configuration).

## Choose an authoring contract

| Contract | Use it for | What you declare |
| --- | --- | --- |
| `CompositeType` | Date ranges, amount/currency pairs, address widgets | Relative child paths and ordinary field options; PHP arranges their parts. |
| `SingleInputType` | A Markdown editor or another editable scalar widget | A PHP element that emits one input with the supplied name and ID. |
| `FieldTypeInterface` | A control with its own submission protocol | Rendering, exact successful submissions, and optional identity/focus metadata. |

Use [themes](./themes.md) for markup or JavaScript enhancements to an existing
native input. Native controls already handle passwords, files, checkbox fallbacks,
and choices. A new alias is useful when a widget has reusable semantics beyond
those presentation changes.

## Composite binding and layout

For `period`, the date-range declaration resolves to:

| Child | Context path | Submitted name | Default ID for form `booking` |
| --- | --- | --- | --- |
| `start` | `period.start` | `period[start]` | `booking-period.start` |
| `end` | `period.end` | `period[end]` | `booking-period.end` |

Nested mounts work the same way: `bookings.0.period` produces
`bookings[0][period][start]`. Configuring the parent's `name` or `id` changes the
children's derived identities. Explicit child `name`, `id`, and `value` options win.

Children use normal request/context resolution, schema inference, validation
metadata, and native value formatting. An array-valued parent supplies matching
child values, preserving null, false, zero, and empty strings. With an explicit
parent `value`, missing children receive null. Without that override, missing
children use their normal context/default lookup. Adapt application value objects
in `inputs($field)` by supplying explicit child values; the plugin does not parse
objects or introduce a persistence layer.

The `inputs()` declaration is resolved once per bound field. Keep it free of
rendering and registration. Distinct composite types can nest; a type that recurs
in its own ancestor chain is rejected.

### Keep layout in PHP

The bundled composite element calls each child's `control()`. Replace it to
reorder children, add arbitrary wrappers, or render `label()`, `input()`, `help()`,
and `errors()` independently, as in the date-range element above. Each child also
supports its own variants, templates, attributes, and `templateData`.

`templates['composite']` selects the composite body; `template` or
`templates['control']` selects the entire outer control. Outer elements can use
`$field->inputs` to access children. Keep the parent legend and feedback appropriate
to your HTML structure. Preserve the supplied wrapper ID when rendering the body.
The prepared input bag includes a name and constraints for child propagation;
remove native-only attributes from a non-input wrapper, as shown above.

Without an explicit element, the normal theme lookup applies to `composite`, for
example `date-range/composite.php` or `variants/compact/composite.php`. A type may
set `protected ?string $template = 'forms/fields/date-range';` to provide its own
default element. A local `templates['composite']` override wins. `$parent()` resumes
normal theme lookup. Presentation data remains available as `$templateData`.

### Registration and accessibility

Only child `input()` or explicit `register()` calls register submissions. Declaring
or inspecting `$field->inputs` registers nothing. A layout may omit a child; the
omitted name is not added to form protection. Child hidden values, checkbox
fallbacks, multiple selections, file/multipart checks, and duplicate IDs retain
the normal native contracts. A failed enclosing render rolls back registrations,
feedback, focus targets, and reserved IDs together.

The parent uses a legend. Child labels target their actual inputs. Parent help
and errors are included in child ARIA descriptions, alongside each child's own
feedback. Group messages exclude nested child error trees, so summaries do not
duplicate those errors. The group summary targets the first declared child that
actually emitted an eligible control; omitted, hidden, disabled, and empty choice
controls do not become focus targets.

Parent disabled and readonly state applies to every child, including local
`$field->input(['disabled' => true])` overrides. A child cannot re-enable a disabled
parent. Required status comes from each child's metadata or options: a required
parent does not make every child required. `secure`, `errors`, and `templateData`
provide inherited defaults that children can override. Child ARIA attributes can
explicitly remove inherited references with null. Render the referenced parent
feedback even when rendering its children independently.

## Example: amount and currency

An invoice amount is two submitted values, with application validation deciding
which currencies and amounts are allowed:

```php [src/Form/Field/MoneyType.php]
<?php
declare(strict_types=1);

namespace App\Form\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\BoundField;
use FormCompose\Field\CompositeType;
use function Cake\I18n\__;

#[AsFieldType('money')]
class MoneyType extends CompositeType
{
    protected ?string $template = 'forms/fields/money';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function inputs(BoundField $field): array
    {
        return [
            'amount' => [
                'type' => 'number', 'label' => __('Amount'),
                'attributes' => ['step' => '0.01', 'min' => '0'],
            ],
            'currency' => [
                'type' => 'select', 'label' => __('Currency'),
                'options' => ['EUR' => __('Euro'), 'USD' => __('US dollar')],
            ],
        ];
    }
}
```

```php [templates/element/forms/fields/money.php]
<?php
declare(strict_types=1);
/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Field\BoundField> $inputs
 */
?>
<div<?= (string)$attributes->replace([
    'name' => null, 'required' => null, 'disabled' => null, 'readonly' => null,
])->merge(['class' => 'money-fields']) ?>>
    <div class="money-amount"><?= $inputs['amount']->control() ?></div>
    <div class="money-currency"><?= $inputs['currency']->control() ?></div>
</div>
```

```php
// Inside an open form; zero is a real value.
echo $form->control('price', [
    'type' => 'money',
    'value' => ['amount' => 0, 'currency' => 'EUR'],
]);
// Submits price[amount] and price[currency].
```

The `step` is an application policy for this example. Currency precision, decimal
conversion, validation, and persistence belong in the application. A service
injected into the type can provide the allowed currencies when that list is not
fixed.

## Example: a progressively enhanced Markdown editor

A text editor submits one editable scalar value. `SingleInputType` handles that
submission contract while the element emits an ordinary textarea:

::: code-group

```php [src/Form/Field/MarkdownType.php]
<?php
declare(strict_types=1);

namespace App\Form\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\SingleInputType;

#[AsFieldType('markdown')]
class MarkdownType extends SingleInputType
{
    protected ?string $template = 'forms/fields/markdown';
}
```

```php [templates/element/forms/fields/markdown.php]
<?php
declare(strict_types=1);

use FormCompose\Rendering\Html;

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string $value
 */
?>
<textarea<?= (string)$attributes->merge([
    'data-editor' => 'markdown', 'rows' => 12,
]) ?>><?= Html::text($value) ?></textarea>
```

:::

```php
echo $form->control('body', ['type' => 'markdown', 'label' => 'Article body']);
```

Your optional editor integration can find `data-editor="markdown"` and enhance
the textarea. It must keep that textarea's submitted value synchronized and retain
its identity and feedback associations. The form works before JavaScript loads.
The textarea value is escaped even when the source contains HTML or `</textarea>`.
Sanitize any rendered Markdown preview separately in the application.

`SingleInputType` supplies `$field`, `$attributes`, and the normalized scalar
`$value`. It registers the supplied name as editable, excludes disabled controls,
and respects explicit `secure: false`. Its default body part is `widget`; local
`templates['widget']`, theme layers, variants, `$parent()`, and `templateData` work
like other parts. The bundled fallback emits a text input.

The element must emit exactly one scalar input with the prepared name and ID.
For files, locked hidden values, checkbox fallbacks, or several submitted names,
use native composite children or implement the lower-level contract.

## Custom submission protocols

Implement `FormCompose\Field\FieldTypeInterface` when the base classes do not
represent your protocol:

- `render(BoundField $field, Attributes $attributes): string` returns escaped markup.
- `submissions(BoundField $field, Attributes $attributes): array` describes exactly
  the successful inputs as `Submission` objects, respecting disabled state.
- Use `new Submission($name, value: $value)` for a locked hidden value, or
  `new Submission($name, locked: false)` for an explicitly unlocked name.

Keep rendering free of direct security mutation. FormCompose registers the
reported submissions after successful rendering; it does not infer them from HTML.
Implement `InputIdentityProviderInterface::inputIds()` to report additional emitted
IDs, excluding the root field ID. Implement
`ErrorTargetProviderInterface::errorTarget()` to report the actual emitted focus
ID, or null for an unlinked summary entry. Neither method should render or register
anything. Hidden and disabled roots remain unlinked.

Use `$field->renderer->render($part, $node)` with a `RenderNode` when preparing
custom parts. Preserve its `templateData` and supplied identities. The
[template reference](./template-reference.md) describes lookup and prepared locals;
[service extensions](./extending.md) covers replacing rendering or binding services.
