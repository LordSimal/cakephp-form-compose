---
title: Custom field reference
description: Custom field type contracts, composite binding, submission records, and explicit type overrides.
---

# Custom field reference

For working examples, start with [a Markdown type](/custom-fields) or
[a composite date range](/custom-fields#create-a-date-range). This page describes their extension
contracts and the lower-level interfaces.

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

## Discovery and explicit overrides

`#[AsFieldType('date-range')]` selects an alias for a `FieldTypeInterface`
implementation. The plugin discovers PSR-4 autoloadable classes under the application's
and loaded plugins' `src/Form/` directories, including nested files. Aliases begin
with a lowercase letter and contain lowercase letters, digits, underscores, or
hyphens. Duplicate aliases fail.

Discovery reads metadata without constructing types. A discovered type is
constructed when rendering, registration, or composite children first require it,
and reused within that binding. Inspecting root state or attributes does not
construct it. Keep per-field state on bound fields; an instance may serve several
fields. Constructor services use the configured
[BlueprintFactory and application container](/blueprint-configuration).

To replace a discovered type for one binding, supply an explicit instance. This
is an override, not a required step in alias discovery:

```php
use App\Form\Field\DateRangeType;

$form = $this->Form
    ->form($booking)
    ->types(['date-range' => new DateRangeType()])
    ->bind();
```

The `DateRangeType` class is defined in the [date-range example](/custom-fields#create-a-date-range).
See [discovery configuration](/blueprint-configuration#discovery-and-cache-configuration)
for alternate paths and deployment cache operations.

## Single-input bodies

`SingleInputType` renders the `widget` part. Its element receives `$field`,
`$attributes`, normalized scalar `$value`, `$templateData`, and `$parent()`.
Emit exactly one editable scalar input, preserving the supplied name and ID and
escaping its value. The base class registers that name, excludes disabled inputs,
and respects `secure(false)`.

Set the type's protected `$template` for a default body. A local
`partTemplate('widget', ...)` override wins. Normal theme, variant, and parent
lookup still apply. Use native composite children for files, hidden values, and
checkbox fallbacks; these have submission semantics beyond an editable scalar.

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
and `errors()` independently, as in the [date-range guide](/custom-fields#arrange-the-children). Each child also
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
`attributes(['disabled' => true])` overrides on the input builder. A child cannot re-enable a disabled
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
use FormCompose\Form\Field\Submission;

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
