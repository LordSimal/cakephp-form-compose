---
title: Field configuration
description: Fluent setters, immutable FieldConfig snapshots, merge rules, and native control behavior.
---

# Field configuration

Field behavior uses typed methods. HTML attributes and application-owned data
remain arrays because their keys are intentionally open-ended.

## Start with a control

```php
echo $form->control('email')
    ->type('email')
    ->label('Email address')
    ->help('Used for account notifications.')
    ->required()
    ->attributes(['autocomplete' => 'email'])
    ->partAttributes('container', ['class' => 'account-field']);
```

`control()` returns a mutable `ControlBuilder`. Echo or `render()` resolves and
renders it. `field()` returns a `FieldBuilder`; call `resolve()` for an immutable
`BoundField` whose parts can be arranged independently.

### Reuse configuration

```php
use FormCompose\Configuration\FieldConfig;

$emailConfig = FieldConfig::builder(new FieldConfig(type: 'email', label: 'Email address'))
    ->required()
    ->attributes(['autocomplete' => 'email'])
    ->config();

echo $form->control('email', config: $emailConfig);
echo $form->control('billing.email', config: $emailConfig)
    ->label('Billing email');
```

The constructor names common settings (`type`, `label`, `help`); the builder
exposes all settings. Start an empty builder with `FieldConfig::builder()`.
`apply()` overlays a configuration or another builder. `config()` is optional:
use it when you explicitly want to store an immutable snapshot.

Every public authoring configuration argument accepts its matching builder directly and captures
it immediately. Later edits to that builder cannot change an existing field
specification, control, or form configuration. For example:

```php
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldSpec;

$currency = new FieldSpec(
    'currency',
    FieldConfig::builder()
        ->label('Currency')
        ->choices(['EUR' => 'Euro', 'USD' => 'US dollar']),
);
```

Ordinary controls accept `choices()` directly on their fluent builder.
Parts render when echoed: `<?= $field->input() ?>`. Explicit `render()` is useful
when assigning HTML to a variable; it is unnecessary inside echo/short echo tags.
Immutable config `with…()` methods return new configs rather than mutating them.

## Identity and type

| Setter | Behavior |
| --- | --- |
| `type(string)` | Override inference: native input type, `textarea`, `select`, `radio`, `multicheckbox`, or custom alias. |
| `name(string)` | Override the submitted bracket name, keeping the original context path. |
| `id(string)` | Set a unique explicit input ID. |

Paths are nonempty dot-separated letters, digits, underscores, or hyphens.
`comments.0.body` submits `comments[0][body]`. Generated IDs include the binding ID;
dots remain dots and literal hyphens are encoded as `:2d` to avoid collisions.
Read IDs from `$field->state->id`; use `getElementById()` or `CSS.escape()` in JS.
All rendered input, choice, container, and feedback IDs must be unique.

### Type inference

An explicit type wins. Otherwise inference applies association selectors and
primary keys, password terminal names, choices (including schema enums), exact
email/telephone terminal names, then schema mappings. Password fields never
prefill, even if choices or a value are supplied.

| Schema type | Control |
| --- | --- |
| `boolean` | `checkbox` |
| `text` | `textarea` |
| Integer, decimal, float types | `number` |
| `date`, `time` | `date`, `time` |
| Datetime and timestamp types | `datetime-local` |
| `binary` | `file` |
| Other | `text` |

Foreign keys ending in `_id` infer selects; `tags._ids` infers a multiple select.
Terminal `email` infers email; `tel`, `telephone`, and `phone` infer tel. Names are
case-sensitive. Override schema mappings with the form builder's `typeMap()` and
register custom aliases with `types()`. Non-choice types do not consume choice
iterators or reserve choice IDs.

## Values

| Setter | Behavior |
| --- | --- |
| `value(mixed)` | Explicit override, including null, false, zero, and empty string. |
| `default(mixed)` | Fallback when no source supplies a value; also passed to context lookup. |
| `schemaDefault(bool = true)` | Allow context schema defaults. |
| `timezone(DateTimeZone\|string\|null)` | Convert temporal objects/timestamps for native rendering; null preserves object timezone. |

Default lookup is explicit value, submitted `data`, then `context`, then default.
A submitted null counts as found; a context null falls through. Change source
order on the form builder with `valueSources()`. File and password inputs never
prefill. Enums become scalar backing values; native controls accept scalar or
stringable values, choice arrays, and supported temporal objects.

Temporal strings pass through as local wall-clock values. Date/time objects use
native HTML formats; integer Unix timestamps use UTC unless a timezone is set.
Date-only and time-only objects reject timezone conversion. Time inputs default
to `step="1"`; fractional schemas use `step="0.001"`. Fractional steps and `any`
format milliseconds. Set or clear the step through `attributes(['step' => ...])`.

## Labels and feedback

| Setter | Behavior |
| --- | --- |
| `label(string\|Html\|Translated\|Closure\|false\|null)` | Null infers a humanized label; false suppresses it. Groups use a legend. |
| `help(string\|Html\|Translated\|Closure\|false\|null)` | Nonempty text renders associated help; false/null omit it. |
| `errors(bool = true)` | Enable inline feedback, summary entries, and generated error descriptions. |

Ordinary text is escaped; trusted HTML requires `FormCompose\Rendering\Html`.
Call CakePHP translation functions in templates or blueprint hooks:

```php
use function Cake\I18n\__;

echo $form->control('email')
    ->label(__('Email address'))
    ->help(__('We keep this private.'));
```

Deferred `Translated` values and zero-argument closures are available for reusable
configs. They resolve once per field snapshot after overrides merge. Closures
return string, Html, Translated, false, or null. See [translation timing](/blueprints#translate-a-blueprint).

## Constraints and state

| Setter | Behavior |
| --- | --- |
| `required(?bool = true)` | Override or restore inferred required state. |
| `disabled(bool = true)` | Disable inputs, choices, fallbacks, and native submission registration. |
| `readonly(bool = true)` | Read-only where supported by native HTML. |
| `multiple(?bool = true)` | Override or restore inferred multiple selection. |

CakePHP validation supplies browser constraints where possible. Configure
additional native constraints with input attributes. Required checkbox groups do
not make every box required; validate collection cardinality in the application.

## Choices and fallbacks

| Setter | Behavior |
| --- | --- |
| `choices(iterable\|string\|null)` | Map, typed choices/groups, enum cases, or backed enum class. Explicit null/[] opts out of discovery. |
| `placeholder(bool\|string\|null)` | Select placeholder; false disables, true requests blank, null uses default behavior. |
| `checkedValue(mixed)` | Single checkbox submitted value; default `'1'`. |
| `uncheckedValue(mixed)` | Its hidden fallback value; default `'0'`. |
| `fallback(bool = true)` | Enable hidden fallbacks for unchecked/empty choices. |

Optional single selects get a blank placeholder unless a real empty-string
choice exists. Other choice fallbacks submit an empty string under the base
name. Multiple names gain `[]`. Disabled controls disable their fallbacks too.
See [choices](/choices) for `ChoiceConfig`, `ChoiceGroupConfig`, and enums.

## Attributes

`attributes(array)` configures the input's HTML attributes; `partAttributes(string, array)`
configures a named part such as `container`, `label`, `help`, or `errors`.
Arbitrary `data-*`, Alpine, HTMX, and other integration attributes are supported.
Native identity/state belongs in typed setters. A null attribute removes an
inherited value; false omits a boolean attribute. Values are escaped.

Attribute layers merge by key, combine classes, and deduplicate ARIA ID lists.
`replaceAttributes()` and `replacePartAttributes()` explicitly discard inherited
bags. Generated feedback identities are still managed for accessibility.

## Templates

All configuration and rendering builders share:

| Setter | Behavior |
| --- | --- |
| `template(string)` | Select the primary element: control, field part, blueprint layout, or complete form layout. |
| `partTemplate(string, string)` | Select an explicit element for one named part. |
| `variant(?string)` | Select or clear a theme variant. |
| `templateData(array)` | Merge application-owned variables, available in `$templateData`. |
| `replaceTemplates(array)` | Replace the complete part-to-element map. |
| `replaceTemplateData(array)` | Replace the entire template data bag. |

A field's template data is inherited by its parts. Local part settings stay local.
Templates use [lookup precedence](/template-reference#lookup-order). A config's
`presentation` property is a typed `PresentationConfig`, keeping these settings
separate from field behavior and bound state.

## Submission protection

`secure(bool = true)` controls CakePHP tamper protection for the field. Keep it
enabled for normal inputs. Disabled controls submit nothing. Rendering registers
the actual submitted names; inspecting a field registers nothing. Custom types
must describe their real submission contract. See [custom fields](/custom-fields).

## Configuration precedence

Blueprint defaults are overlaid by an explicit per-call config and then fluent
edits. A builder's `config()` captures its authored settings;
`resolve()->state->config` exposes the final merged field settings. Only explicitly
supplied settings override earlier values. Explicit null
is preserved where accepted. Presentation maps merge by key, with class/ARIA
combining; replacement setters preserve their intent across config snapshots.
`extensionData(array)` is an explicit application/custom-type configuration bag
read through `$field->state->config->extensionData`; it replaces its previous bag.
Use `templateData()` for presentation variables instead.
