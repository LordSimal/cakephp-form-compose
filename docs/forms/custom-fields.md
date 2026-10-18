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
<?php $form = $this->Form->form($booking)->bind(); ?>
<?= $form->open() ?>
<?= $form->control('period')
    ->type('date-range')
    ->label('Your stay')
    ->help('Choose arrival and departure dates.')
    ->partTemplate('composite', 'forms/fields/date-range') ?>
<?= $form->submit('Book') ?>
<?= $form->close() ?>
```

```php [templates/element/forms/fields/date-range.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Form\Field\BoundField> $inputs
 */
?>
<div<?= $attributes->merge(['class' => 'date-range']) ?>>
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

A **composite field** is a reusable group of bound child inputs. The default
`control` template renders an actual HTML `<fieldset>` with a `<legend>`, group
help, and group errors. Your `composite` element supplies the inner layout:

```html
<fieldset>
    <legend>Your stay</legend>
    <div class="date-range">
        <!-- Arrival and departure fields -->
    </div>
    <!-- Group help and errors -->
</fieldset>
```

Change `composite` to arrange the children; change `control` to replace the outer
structure. With another outer structure, provide suitable accessible group naming
and customize the `label` part if a `<legend>` no longer fits. Classes here are
application styling hooks; FormCompose requires no CSS framework or JavaScript.

## Register a type with AsFieldType

Put application types in `src/Form/Field/`, using the `App\Form\Field` namespace.
`#[AsFieldType('date-range')]` registers the alias selected by the `type` option:

```php [src/Form/Field/DateRangeType.php]
<?php
declare(strict_types=1);

namespace AppField;

use Attribute\AsFieldType;
use Configuration\FieldConfig;
use Form\Field;
use Form\Field\BoundField;
use Form\Field\CompositeType;
use Form\Field\FieldSpec;
use FormComposeAsFieldType;
use FormComposeField\BoundField;
use FormComposeField\CompositeType;
use FormComposeField\FieldSpec;
use FormComposeFieldConfig;
use I18n\__;
use function Cake__;

#[AsFieldType('date-range')]
class DateRangeType extends CompositeType
{
    /**
     * @return list<FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        return [
            new FieldSpec('start', FieldConfig::builder()->type('date')->label(__('Arrival'))),
            new FieldSpec('end', FieldConfig::builder()->type('date')->label(__('Departure'))),
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
[configured BlueprintFactory and application container](/blueprint-configuration).
Keep per-field state on the bound fields; a type instance can serve several fields.

Explicit instances override discovered aliases:

```php
use AppDateRangeType;
use Form\Field\DateRangeType;

$form = $this->Form->form($booking)->types(['date-range' => new DateRangeType()])->bind();
```

Clear or warm discovery metadata during deployment as described in
[discovery and cache configuration](/blueprint-configuration#discovery-and-cache-configuration).

## Choose an authoring contract

| Contract | Use it for | What you declare |
| --- | --- | --- |
| `CompositeType` | Date ranges, amount/currency pairs, address widgets | Relative `FieldSpec` children and typed configs; PHP arranges their parts. |
| `SingleInputType` | A Markdown editor or another editable scalar widget | A PHP element that emits one input with the supplied name and ID. |
| `FieldTypeInterface` | A control with its own submission protocol | Rendering, exact successful submissions, and optional identity/focus metadata. |

Use [themes](/themes) for markup or JavaScript enhancements to an existing
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

Inline `FieldConfig` builders are captured by `FieldSpec`; no `config()` call is
needed. Echoing a part renders it directly; explicit `render()` is only needed
when you want a string before output.

### Keep layout in PHP

The bundled composite element calls each child's `control()`. Replace it to
reorder children, add arbitrary wrappers, or render `label()`, `input()`, `help()`,
and `errors()` independently, as in the date-range element above. Each child also
supports its own variants, templates, attributes, and `templateData`.

`partTemplate('composite', ...)` selects the composite body; `template` or
`partTemplate('control', ...)` selects the entire outer control. Outer elements can use
`$field->inputs` to access children. Keep the parent legend and feedback appropriate
to your HTML structure. Preserve the supplied wrapper ID when rendering the body.
The composite element receives wrapper-ready `$attributes`: its ID, classes, ARIA
references, and integration attributes. Input-only attributes are filtered by
`CompositeType`; disabled and readonly state still reaches the child inputs.

Without an explicit element, the normal theme lookup applies to `composite`, for
example `date-range/composite.php` or `variants/compact/composite.php`. A type may
set `protected ?string $template = 'forms/fields/date-range';` to provide its own
default element. A local `partTemplate('composite', ...)` override wins. `$parent()` resumes
normal theme lookup. Presentation data remains available as `$templateData`.

### Accessibility

The parent uses a legend. Child labels target their actual inputs. Parent help
and errors are included in visible child ARIA descriptions, alongside each
child's own feedback. Group messages exclude nested child error trees, so summaries do not
duplicate those errors. The group summary targets the first declared child that
actually emitted an eligible control; omitted, hidden, disabled, and empty choice
controls do not become focus targets.

Parent disabled and readonly state applies to every child, including local
`$field->input()->attributes(['disabled' => true])` overrides. A child cannot re-enable a disabled
parent. Required status comes from each child's metadata or explicit settings: a required
parent does not make every child required. `errors` and `templateData` provide
inherited defaults that children can override. Child ARIA attributes can
explicitly remove inherited references with null. Render the referenced parent
feedback even when rendering its children independently.

## Submission protection

FormCompose's counterpart to a CakePHP widget's `secureFields()` is
`FieldTypeInterface::submissions()`. It returns `Submission` records describing the
actual submitted names, optional locked values, and explicit unlocking. These
records feed CakePHP's `FormProtector` when the application enables form protection;
FormCompose does not introduce a separate token-validation system.

| Authoring contract | Who describes the submissions? |
| --- | --- |
| `CompositeType` | Each rendered child; the composite parent contributes no submitted input of its own. |
| `SingleInputType` | The base class registers one editable name, excluding disabled controls and respecting `secure(false)`. |
| `FieldTypeInterface` | Your `submissions()` implementation must describe the inputs emitted by your renderer. |

### Composite children register themselves

Inside a composite element, rendering the date-range inputs registers their names:

```php
<?= $inputs['start']->input() ?>
<?= $inputs['end']->input() ?>
```

This protects `period[start]` and `period[end]`. The composite author does not need
to implement `secureFields()` or override `submissions()`. Child `control()` calls
also render and register their inputs.

| Child behavior | Registration result |
| --- | --- |
| Render an ordinary enabled input | Its name is expected; its editable value can change. |
| Render a native hidden input | Its name and rendered value are protected. |
| Render a disabled input | No submission is registered; its name is not automatically unlocked. |
| Declare or inspect a child without rendering it | No submission is registered and no name is unlocked. |
| Render a child configured with `secure(false)` | Its name is explicitly unlocked, allowing omission or changed values. |
| Fail during an enclosing composite layout | Pending registrations roll back together with feedback, focus targets, and reserved IDs. |

::: warning Only rendered children are protected
If the element renders only `start`, `period[end]` is not expected. Posting that
omitted name fails ordinary form protection unless the application explicitly
unlocks it. Declaring a child does not register it for submission.
:::

The parent's `secure` option supplies a default for its children; an explicit child
option wins. Native checkbox fallbacks, multiple selections, file/multipart checks,
and duplicate-ID checks retain their normal contracts.

Handwritten HTML is not inspected for input names. Register each actual child
using [`$inputs['start']->register()`](/field-reference#register) after emitting
its markup, passing the same local input attributes, especially `disabled`.
Registering only the composite parent does not register its children. Use either
`input()`/`control()` or handwritten markup plus `register()` for a given child.

### Low-level submission records

A `FieldTypeInterface` implementation returns records such as:

```php
use Form\Field\Submission;
use FormComposeSubmission;

new Submission('period[start]');
new Submission('record_id', value: '42');
new Submission('optional', locked: false);
```

The first protects the expected field name while allowing its value to change.
The second also locks the value to `'42'`, as required for a hidden record ID.
The third explicitly unlocks the name. The default `locked: true` protects the
field structure; it locks the value only when a non-null `value` is supplied.

Return no submissions for disabled inputs and report only the inputs actually
emitted. Registration happens after rendering succeeds; keep rendering free of
direct security mutations. Form protection checks the submitted structure and
locked values. Application validators still decide whether editable values, such
as arrival and departure dates, are valid.

## Example: amount and currency

An invoice amount is two submitted values, with application validation deciding
which currencies and amounts are allowed:

```php [src/Form/Field/MoneyType.php]
<?php
declare(strict_types=1);

namespace AppField;

use Attribute\AsFieldType;
use Configuration\FieldConfig;
use Form\Field;
use Form\Field\BoundField;
use Form\Field\CompositeType;
use Form\Field\FieldSpec;
use FormComposeAsFieldType;
use FormComposeField\BoundField;
use FormComposeField\CompositeType;
use FormComposeField\FieldSpec;
use FormComposeFieldConfig;
use I18n\__;
use function Cake__;

#[AsFieldType('money')]
class MoneyType extends CompositeType
{
    protected ?string $template = 'forms/fields/money';

    /**
     * @return list<FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        return [
            new FieldSpec(
                'amount',
                FieldConfig::builder()
                    ->type('number')
                    ->label(__('Amount'))
                    ->attributes(['step' => '0.01', 'min' => '0']),
            ),
            new FieldSpec(
                'currency',
                FieldConfig::builder()
                    ->type('select')
                    ->label(__('Currency'))
                    ->choices(['EUR' => __('Euro'), 'USD' => __('US dollar')]),
            ),
        ];
    }
}
```

```php [templates/element/forms/fields/money.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array<string, \FormCompose\Form\Field\BoundField> $inputs
 */
?>
<div<?= $attributes->merge(['class' => 'money-fields']) ?>>
    <div class="money-amount"><?= $inputs['amount']->control() ?></div>
    <div class="money-currency"><?= $inputs['currency']->control() ?></div>
</div>
```

```php
// Inside an open form; zero is a real value.
echo $form->control('price')->type('money')->value(['amount' => 0, 'currency' => 'EUR']);

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

namespace AppField;

use Attribute\AsFieldType;
use Form\Field;
use Form\Field\SingleInputType;
use FormComposeAsFieldType;
use FormComposeField\SingleInputType;

#[AsFieldType('markdown')]
class MarkdownType extends SingleInputType
{
    protected ?string $template = 'forms/fields/markdown';
}
```

```php [templates/element/forms/fields/markdown.php]
<?php
declare(strict_types=1);

use FormComposeHtml;
use Rendering\Html;

/**
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var string $value
 */
?>
<textarea<?= (string)$attributes->merge([
    'data-editor' => 'markdown',
    'rows' => 12,
]) ?>><?= Html::text($value) ?></textarea>
```

:::

```php
echo $form->control('body')->type('markdown')->label('Article body');
```

Your optional editor integration can find `data-editor="markdown"` and enhance
the textarea. It must keep that textarea's submitted value synchronized and retain
its identity and feedback associations. The form works before JavaScript loads.
The textarea value is escaped even when the source contains HTML or `</textarea>`.
Sanitize any rendered Markdown preview separately in the application.

`SingleInputType` supplies `$field`, `$attributes`, and the normalized scalar
`$value`. It registers the supplied name as editable, excludes disabled controls,
and respects explicit `secure(false)`. Its default body part is `widget`; local
`partTemplate('widget', ...)`, theme layers, variants, `$parent()`, and `templateData` work
like other parts. The bundled fallback emits a text input.

The element must emit exactly one scalar input with the prepared name and ID.
For files, locked hidden values, checkbox fallbacks, or several submitted names,
use native composite children or implement the lower-level contract.

## Custom submission protocols

Implement `FormCompose\Form\Field\FieldTypeInterface` when the base classes do not
represent your protocol:

- `render(BoundField $field, Attributes $attributes): string` returns escaped markup.
- `submissions(BoundField $field, Attributes $attributes): array` describes exactly
  the successful inputs as `Submission` objects, respecting disabled state.

The [submission protection section](#submission-protection) explains the records,
disabled inputs, and registration timing. Implement
`InputIdentityProviderInterface::inputIds()` to report additional emitted IDs, excluding the root field ID. Implement
`ErrorTargetProviderInterface::errorTarget()` to report the actual emitted focus
ID, or null for an unlinked summary entry. Neither method should render or register
anything. Hidden and disabled roots remain unlinked.

Use `$field->renderer->render($part, $node)` with a `RenderNode` when preparing
custom parts. Preserve its `templateData` and supplied identities. The
[template reference](/template-reference) describes lookup and prepared locals;
[service extensions](/extending) covers replacing rendering or binding services.
