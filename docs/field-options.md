---
title: Field options
description: All built-in field options for control(), field(), and blueprint Field declarations.
---

# Field options

These options apply to `$form->control($field, $options)` and
`$form->field($field, $options)`. They also apply to `FormBuilder::add()` / `with()` and
the `options` argument of a blueprint's `Field` attribute. Custom field types may
define additional options.

## Start with a control

```php
// Inside an open form.
echo $form->control('title', [
    'type' => 'text',
    'label' => 'Article title',
    'help' => 'Displayed above the article.',
    'required' => true,
    'attributes' => [
        'class' => 'title-input',
        'placeholder' => 'Enter a title',
    ],
    'partAttributes' => ['container' => ['class' => 'article-field']],
]);
```

Field options configure behavior; `attributes` configures the input's HTML
attributes. For example, use top-level `id`, but put `class`, `placeholder`,
`data-*`, and `x-model` inside `attributes`. Arbitrary top-level keys are not
forwarded to the input as HTML attributes.

## Identity and type

| Option | Type | Default and behavior |
| --- | --- | --- |
| `type` | `string` | Inferred from conventions and context; overrides inference. Native HTML input types, `textarea`, `select`, `radio`, `multicheckbox`, or a registered custom type. |
| `name` | `string` | Bracket notation derived from the path: `comments.0.body` becomes `comments[0][body]`. Overrides the submitted name, not the context lookup path. |
| `id` | `string` | Binding ID plus the path; dots are retained and literal hyphens become `:2d`. Set a unique explicit ID for repeated presentations. |

Paths must be nonempty dot-separated segments containing letters, digits,
underscores, or hyphens. Names must use valid bracket notation. IDs must be
nonempty and unique among rendered elements in the session, including choices,
feedback, and configured container IDs. Type names must begin with a lowercase
letter and contain only lowercase letters, digits, underscores, or hyphens.

Generated field IDs distinguish `author.name` from `author-name` and reserve
hyphens for feedback and choice suffixes. Read IDs from `$field->state->id`;
use `document.getElementById()` or `CSS.escape()` with JavaScript selectors.
Explicit IDs remain application-owned and must be unique.

### Type inference

Explicit `type` wins. Before schema inference, conventional association selectors
(`tags._ids`) become multiple selects, context primary keys become hidden inputs,
and foreign-key paths ending in `_id` become selects. Other fields whose final
path segment is `password` or `passwd` infer a password input, including nested
paths. Password inference takes precedence over choice-list discovery and never
prefills the input from request, context, default, or explicit values. An explicit
`type` still wins. Configure helper or binding `passwordFields` to replace these
terminal names; include the defaults when adding application-specific names.
Native non-choice controls do not consume choice iterators or reserve choice IDs,
even when their configuration includes `options`.
For other fields, an explicit `options` list, or a discovered
conventional choice list, infers a select. Registered CakePHP `enum-*` schema types
discover their backed enum class before view-variable choices. Explicit `options`
and non-choice `type` overrides remain authoritative.

| Context type | Control type |
| --- | --- |
| `boolean` | `checkbox` |
| `text` | `textarea` |
| `integer`, `tinyinteger`, `smallinteger`, `biginteger`, `decimal`, `float` | `number` |
| `date`, `time` | `date`, `time` |
| `datetime`, `timestamp`, `datetimefractional`, `timestampfractional`, `timestamptimezone` | `datetime-local` |
| `binary` | `file` |
| Other or unknown | `text` |

## Values

| Option | Type | Default and behavior |
| --- | --- | --- |
| `value` | `mixed` | Absent: resolve from binding sources. When supplied, wins even when null, false, zero, or an empty string. |
| `default` | `mixed` | `null`. Used when no configured source supplies a value; also passed to context lookup. |
| `schemaDefault` | `bool` | `true`. Passed to the context to control schema defaults. |
| `timezone` | `string`, `DateTimeZone`, or `null` | `null`. For temporal inputs, convert date/time objects or integer Unix timestamps to this timezone without changing the original object. Date-only and time-only objects reject timezone conversion. |

Source order belongs to binding [`valueSources`](./form-reference.md#binding-options),
not to individual fields. The default order is request data, then context.
A request key containing null counts as found; a context's null result falls
through. Backed enums become their scalar values. Native controls accept scalar
or stringable values, with arrays of scalars or backed enum cases for multiple
choices and date/time objects for native date/time formatting. Composite values need a custom field type.

```php
// Inside an open form; these are three independent fields.
echo $form->control('title', ['default' => 'Untitled', 'schemaDefault' => false]);
echo $form->control('summary', ['value' => '']);
echo $form->control('reviewed_at', ['value' => null]);
```

`default` fills missing values; `value` deliberately overrides found values.
Password and file inputs never prefill values. Date/time objects use native HTML
formats; false becomes `'0'` when rendered as a native scalar.

Native `date`, `month`, `week`, `time`, and `datetime-local` inputs format PHP
date/time objects and CakePHP `Date` and `Time` objects without localized strings.
Week values use the ISO week year. Date-only objects support date/month/week;
time-only objects support time. Integer Unix timestamps use UTC unless `timezone`
is configured. Strings, including submitted values, must already use the native
input format and pass through unchanged: they represent local wall-clock values
and are not parsed or converted again. Invalid strings are never replaced with
the current date.

Time and datetime inputs default to `step="1"` so nonzero seconds remain valid.
Fractional datetime schema types default to `step="0.001"`. Positive non-integer
steps, including intervals above one second such as `step="1.5"`, and `step="any"`
format object values with milliseconds; precision beyond milliseconds
cannot be represented by native browser inputs. Override `attributes['step']`
to choose another interval or remove the attribute with null.

## Labels and feedback

| Option | Type | Default and behavior |
| --- | --- | --- |
| `label` | `string`, `Html`, `Translated`, `false`, `null`, or `Closure` | Humanized field path; association selectors use the related name. `false` suppresses the label or group legend. |
| `help` | `string`, `Html`, `Translated`, `false`, `null`, or `Closure` | Omitted. Nonempty help renders and adds an ARIA description reference. `false` or an empty string suppresses it. |
| `errors` | `bool` | `true`. Renders available context messages. `false` suppresses inline errors, summary entries, and generated error ARIA references. |

`Html` means `FormCompose\Rendering\Html`, the explicit
[trusted-markup wrapper](./template-reference.md#trusted-markup). Ordinary strings
are escaped. `errors` toggles presentation; it does not replace validation messages.
For radios and multi-checkboxes, the label is a group legend.

`FormCompose\Translated` stores a source message, formatting arguments, domain,
and context, then translates through CakePHP when the field is resolved. Use it
for translated text in attributes:

```php
#[Field(
    'title',
    label: new Translated('Article title'),
    help: new Translated('Displayed above the article.'),
)]
```

Import `FormCompose\Attribute\Field` and `FormCompose\Translated` for this example.
Translated text is escaped normally. Plain strings are used as supplied.

Label/help closures take no arguments and return `string`, `Html`, `Translated`,
`false`, or `null`. They run once per resolved field after blueprint defaults
and per-call options merge; a returned message is translated in that resolution. Returned strings are escaped; false suppresses the part; null uses
the inferred label or omits help. Other return types throw
`InvalidArgumentException`. Reading or rendering an already resolved field reuses
its text. See [translating blueprints](./blueprints.md#translate-a-blueprint) for
attribute syntax, locale timing, and runtime configuration examples.

In a template, call translation functions directly:

```php
// Inside an open form; import Cake\I18n\__.
echo $form->control('title', [
    'label' => __('Article title'),
    'help' => __('Displayed above the article.'),
]);
```

Use deferred text when declaring translations in PHP attributes or when storing
configuration whose text should be evaluated at field resolution.

## Constraints and state

| Option | Type | Default and behavior |
| --- | --- | --- |
| `required` | `bool` | Inferred from context validation metadata, otherwise `false`. Forced off for hidden or disabled fields. |
| `disabled` | `bool` | Omitted. `true` disables the input, its choices, and fallback; disabled native controls register no submitted names. |
| `readonly` | `bool` | Omitted. Sets the native attribute; browser support depends on the control type. |
| `multiple` | `bool` | Omitted, except inferred `_ids` selectors. Multiple select/file names gain `[]`; multi-checkbox names always gain `[]`. |

Context text-length limits supply `maxlength`. Numeric schema types supply `step`
(`any` for decimals/floats, `1` for integers). Put additional browser constraints,
such as `min`, `max`, `step`, `maxlength`, or `pattern`, in `attributes` to override
or extend the prepared input attributes. Server-side validation remains in CakePHP.

For `multicheckbox`, `required` describes the group requirement in field state;
it does not emit `required` on each checkbox, which would require every choice.
Validate an “at least one” rule in CakePHP. Radio groups retain native `required`.

```php
echo $form->control('reading_minutes', [
    'type' => 'number',
    'required' => false,
    'attributes' => ['min' => 1, 'max' => 120, 'step' => 1],
]);
```

## Choices and fallbacks

| Option | Type | Default and behavior |
| --- | --- | --- |
| `options` | `iterable` or backed enum class name | Discovered CakePHP schema enum choices, then conventional view-variable choices, otherwise `[]`. Explicit `options` overrides discovery. Accepts value/label maps, backed enum cases, nested groups, and extended entries. See [enum choices](./choices.md#backed-enums) for labels and selection. |
| `empty` | `bool`, `string`, or `null` | `null`/omitted: prepend a blank option for optional single selects. `true`: prepend a blank label; a string: use that label; `false`: do not prepend. Required and multiple selects have no automatic blank option. |
| `checkedValue` | Scalar or stringable | `'1'`. Submitted value for a checked single checkbox. |
| `uncheckedValue` | Scalar or stringable | `'0'`. Hidden fallback value for an unchecked single checkbox. |
| `hiddenField` | `bool` | `true`. Enables fallback inputs for checkboxes, radios, multi-checkboxes, and multiple selects. |

Use `empty` to configure a select placeholder. Empty-string choice values remain
supported for application data and backed enums. An explicit empty-value option,
including one in an optgroup, keeps its label and prevents an additional
placeholder. `empty => false` does not remove explicit choices. A placeholder and
an empty-string choice both submit `''`.

Radio and multiple-choice fallbacks submit an empty string when nothing is
selected. Null and empty-string values select the automatic
blank option; zero and false retain their own selection. The blank value is an
empty string in the HTTP request; CakePHP's normal marshalling handles nullable
associations. A select with no enabled options and no fallback registers no
submitted name, matching the browser. Group and per-choice data shapes are
demonstrated in [Choice controls](./choices.md).

Checkbox selection compares the bound scalar value with `checkedValue`. To
explicitly override its checked attribute, use `attributes => ['checked' => true]`
or `false`. Radio and multi-checkbox selection follows the bound field value.

## Attributes

| Option | Type | Default and behavior |
| --- | --- | --- |
| `attributes` | `array<string, mixed>` | `[]`. Attributes for the input part. |
| `partAttributes` | Map of part names to attribute arrays | `[]`. Configure `container`, `input`, `label`, `help`, or `errors`. |

Input attributes are assembled from theme defaults, resolved metadata,
`partAttributes['input']`, `attributes`, and local `$field->input([...])`
attributes, in that order. Classes and ARIA ID references merge; ordinary
attributes are overwritten. Null removes an attribute, false omits it, and true
emits its name alone.

```php
echo $form->control('title', [
    'id' => 'article-title',
    'attributes' => ['class' => 'wide', 'data-controller' => 'title'],
    'partAttributes' => [
        'container' => ['class' => 'field'],
        'label' => ['class' => 'caption'],
        'help' => ['class' => 'hint'],
        'errors' => ['class' => 'feedback'],
    ],
]);
```

Input attribute bags reject `name`, `id`, `type`, `value`, `escape`, `secure`,
`options`, and `val`. Configure supported identity/behavior options at the field
level. Use `value` for values and `Html` for trusted text markup. Preserve generated
label, help, and error associations when customizing other parts.

Configure feedback identities through `partAttributes`, for example
`'help' => ['id' => 'email-hint']`. Generated `aria-describedby` references use
these IDs. Setting an ID to null or false omits that reference. Passing `id` to
`$field->help([...])` or `$field->errors([...])` is rejected so a later rendering
call cannot invalidate the input's already resolved association.

## Templates

| Option | Type | Default and behavior |
| --- | --- | --- |
| `template` | Nonempty `string` | Omitted. Explicit CakePHP element for the complete control. Shorthand for `templates['control']`. |
| `templates` | `array<string, string>` | `[]`. Part-to-element overrides, such as `input`, `help`, or `native`. |
| `variant` | `string` | Omitted. Selects a named variant before type-specific and generic templates within each layer. |
| `templateData` | `array<string, mixed>` | `[]`. Application data available to this field's control and all its parts. |

Element names omit `.php`. Conflicting `template` and `templates['control']`
values throw `InvalidArgumentException`. Hidden controls render their input part
instead of an outer control layout. See the [template reference](./template-reference.md)
for all part names, variables, and lookup rules.

## Submission protection

| Option | Type | Default and behavior |
| --- | --- | --- |
| `secure` | `bool` | `true`. Includes the native input in tamper protection; hidden values are also locked to their rendered value. `false` explicitly unlocks that submitted field. |

Use the default for ordinary controls. A custom control describes its actual
submitted names through its [field type](./extending.md#custom-field-types).
Inspecting fields registers nothing; successful input rendering performs
registration. Handwritten inputs must use [`register()`](./field-reference.md#register).

## Configuration precedence

For each field, later configuration wins:

1. Field defaults selected at `bind(..., definition: ...)`.
2. Per-call options.

Matching top-level option keys are replaced. Nested arrays such as `attributes`,
`templates`, and `templateData` are replaced as whole values at this stage. This
is separate from the subsequent attribute-bag merging described above.
Blueprint declaration and hook precedence is documented in the
[definitions reference](./definition-reference.md#configuration-order).

Malformed core `attributes`, `partAttributes`, `templates`, and choice `options`
bags fail during field resolution with `InvalidArgumentException`. Attribute and
part keys must be nonempty strings; template values must be nonempty element
names. Unknown option keys and arbitrary integration attributes remain available
to application controls and templates.
