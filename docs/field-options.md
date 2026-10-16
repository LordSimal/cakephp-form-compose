---
title: Field options
description: All built-in field options for control(), field(), FieldDefinition, and blueprint Field declarations.
---

# Field options

These options apply to `$form->control($field, $options)` and
`$form->field($field, $options)`. They also apply to `FieldDefinition::with()` and
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
| `id` | `string` | Binding ID plus the path with dots replaced by hyphens. Set a unique explicit ID for repeated presentations. |

Paths must be nonempty dot-separated segments containing letters, digits,
underscores, or hyphens. Names must use valid bracket notation. IDs must be
nonempty and unique among rendered elements in the session, including choices,
feedback, and configured container IDs. Type names must begin with a lowercase
letter and contain only lowercase letters, digits, underscores, or hyphens.

### Type inference

Explicit `type` wins. Before schema inference, conventional association selectors
(`tags._ids`) become multiple selects, context primary keys become hidden inputs,
and foreign-key paths ending in `_id` become selects. An explicit `options` list,
or a discovered conventional choice list, also infers a select.

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

Source order belongs to binding [`valueSources`](./form-reference.md#binding-options),
not to individual fields. The default order is request data, then context.
A request key containing null counts as found; a context's null result falls
through. Backed enums become their scalar values. Native controls accept scalar
or stringable values, with arrays for multiple choices and date/time objects for
native date/time formatting. Composite values need a custom field type.

```php
// Inside an open form; these are three independent fields.
echo $form->control('title', ['default' => 'Untitled', 'schemaDefault' => false]);
echo $form->control('summary', ['value' => '']);
echo $form->control('reviewed_at', ['value' => null]);
```

`default` fills missing values; `value` deliberately overrides found values.
Password and file inputs never prefill values. Date/time objects use native HTML
formats; false becomes `'0'` when rendered as a native scalar.

## Labels and feedback

| Option | Type | Default and behavior |
| --- | --- | --- |
| `label` | `string`, `Html`, or `false` | Humanized field path; association selectors use the related name. `false` suppresses the label or group legend. |
| `help` | `string`, `Html`, or `false` | Omitted. Nonempty help renders and adds an ARIA description reference. `false` or an empty string suppresses it. |
| `errors` | `bool` | `true`. Renders available context messages. `false` suppresses inline errors, summary entries, and generated error ARIA references. |

`Html` means `FormCompose\Rendering\Html`, the explicit
[trusted-markup wrapper](./template-reference.md#trusted-markup). Ordinary strings
are escaped. `errors` toggles presentation; it does not replace validation messages.
For radios and multi-checkboxes, the label is a group legend.

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
| `options` | `iterable` | Conventional view-variable choices when available, otherwise `[]`. Accepts value/label maps, nested groups, and extended entries. |
| `empty` | `bool`, `string`, or `null` | `null`/omitted: prepend a blank option for optional single selects. `true`: prepend a blank label; a string: use that label; `false`: do not prepend. Required and multiple selects have no automatic blank option. |
| `checkedValue` | Scalar or stringable | `'1'`. Submitted value for a checked single checkbox. |
| `uncheckedValue` | Scalar or stringable | `'0'`. Hidden fallback value for an unchecked single checkbox. |
| `hiddenField` | `bool` | `true`. Enables fallback inputs for checkboxes, radios, multi-checkboxes, and multiple selects. |

Radio and multiple-choice fallbacks submit an empty string when nothing is
selected. An existing empty-value option, including one in an optgroup, is kept
instead of adding another. Null and empty-string values select the automatic
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
2. The explicit `FieldDefinition` passed as the field argument.
3. Per-call options.

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
