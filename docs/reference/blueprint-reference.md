---
title: Blueprint reference
description: Blueprint authoring, scoped views, context, discovery, and compiled configuration.
---

# Blueprint reference

Blueprints author reusable configuration through PHP hooks. Rendering and
submission state belong to the enclosing form session.

## Presentation options

`$form->presentation()` in a blueprint hook returns an attached
`PresentationBuilder`: `template()`, `partTemplate()`, `variant()`, `attributes()`,
`partAttributes()`, `templateData()`, and explicit replacement methods.
Embed declaration builders and renderable scoped occurrences use the same methods.

## Blueprint declarations

### Form

`#[Form('article')]` supplies an optional discovery alias. It accepts no field,
layout, template, or data settings. Class references remain supported explicitly.

### Field

Declare fields with `BlueprintBuilder::add(string $path, FieldConfig|FieldConfigBuilder|null $config = null)`.
A builder argument is captured immediately. The returned attached `FieldConfigBuilder` exposes all [field setters](/field-options).
There is no `Field` attribute.

### Embed

`embed(string $path, string $blueprint)` declares one child; `embedMany(...)`
declares keyed rows. A single declaration returns `EmbeddedBlueprintBuilder`
with layout presentation methods. Repeated declarations return
`RepeatedBlueprintBuilder`, adding `keys(?array)`, `label()`, and `errors()`.
Configure occurrence presentation on either builder.
There are no `Embed` or `EmbedMany` attributes.

### AsFieldType

`#[AsFieldType('date-range')]` remains the discovery alias for a
`FieldTypeInterface` implementation. See [custom field types](/custom-fields).

### Declaration behavior

Hooks use normal PHP inheritance. Call `parent::configure()` explicitly to retain
parent declarations. Duplicate field/embed paths fail; use `field()` to edit or
`remove()` followed by a new declaration for an intentional replacement.
Cycles and collisions across embedded children also fail.

## Translated text

`FormCompose\Form\Field\Translated` retains message, arguments, domain, and context
until field resolution. Label/help closures accept no arguments and return
string, Html, Translated, false, or null. Ordinary runtime translation calls in
hooks are the usual choice for labels and choices. See
[translating blueprints](/blueprints#translate-a-blueprint).

## configure() and BlueprintBuilder

```php
public function configure(BlueprintBuilder $form, FormContext $context): void
{
    $form->add('title')->required();
    $form->embed('author', 'author');
    $form->field('author.email')->label(__('Contact email'));
    $form->presentation()->template('forms/article');
}
```

This is a class method; import the blueprint classes and CakePHP's `__` function.

| Method | Result |
| --- | --- |
| `add(string, ?FieldConfig)` | Attached field builder for a new declaration. |
| `field(string)` | Edit an existing field or stage a qualified child override. Unknown targets fail at build. |
| `embed(string, string)` | Deferred single-child declaration builder. |
| `embedMany(string, string)` | Deferred repeated declaration builder; select keys before compilation. |
| `remove(string)` | Remove a field or entire embed; retains omission history. |
| `presentation()` | Attached collection presentation builder. |
| `build()` | Immutable compiled `FormDefinition`; normally called by the compiler. |

## FormContext

| Member | Meaning |
| --- | --- |
| `request` | Original CakePHP request. |
| `context` | CakePHP context scoped to the occurrence. |
| `path` | Absolute mount within the root form, or empty at root. |
| `at(string)` | New context scoped below this occurrence. |

Request lookups retain full paths; context lookups are relative. Services belong
in blueprint constructor dependencies. Hooks change configuration, never values,
validation, or persistence.

## Configuration order

A fresh blueprint is configured once for each occurrence. Parent authoring
finishes before children compile; repeated keys therefore exclude unnecessary
row hooks. Qualified parent edits overlay compiled child fields. Per-call configs
and fluent edits overlay those defaults at resolution. Only explicitly configured
settings override prior values. Presentation maps merge; explicit replacement
setters retain their intent through snapshots.

## BlueprintView

A blueprint PHP layout receives a scoped `BlueprintView` as `$form`.

| Method | Contract |
| --- | --- |
| `has(string): bool` | Final field/embed membership without side effects. |
| `field(string, ?FieldConfig): FieldBuilder` | Relative field authoring; `resolve()` captures state. |
| `control(string, ?FieldConfig): ControlBuilder` | Relative control; removed fields emit nothing, unknown paths fail. |
| `controls(iterable): ControlBatch` | Relative batch with atomic registration rollback. |
| `embedded(string): PartBuilder` | Render an already compiled single child. |
| `embeddedMany(string): EmbedBuilder` | Render compiled rows with local presentation; cannot change selected keys. |
| `render(?PresentationConfig = null): string` | Render this occurrence's layout without opening another form. |

Common fluent presentation setters apply to returned renderables. Layouts cannot
open/close the session. Field inspection remains free of rendering/registration.
Removed controls retain the full fluent API and emit nothing; resolving a removed
field is an error, so guard manual parts with `has()`.

## BlueprintFactory

`compile(string $blueprint, FormContext $context): FormDefinition` resolves aliases
or class names and compiles an isolated tree. `repeated()` compiles a repeated
occurrence. Protected compiler/builder factories are extension boundaries; use
the configured factory so DI, cycle detection, and custom services are preserved.
See [services and discovery](/blueprint-configuration).

## Repeated blueprints and occurrence options

`EmbedConfig::builder()` exposes `label(string|Html|null)`,
`errors(bool = true)`, and shared presentation methods. `config()` captures a
snapshot; `apply(EmbedConfig)` overlays one. Native constructors accept label
and errors by name. Row selection belongs to `keys(?array)` on the
`RepeatedBlueprintBuilder` returned by blueprint `embedMany()` and the
`RepeatedEmbedBuilder` returned by session `embedMany()`. Null keys infer rows;
empty keys render no rows. Each selected row has its own scoped blueprint hook.
`embeddedMany()` on a compiled layout returns `EmbedBuilder`, which only changes
occurrence presentation and exposes no row-selection method.

`RepeatedView` exposes keyed `rows`, `rows()`, direct `messages`, `hasErrors()`,
`label()`, and `errors()`. Layouts render collection errors separately from row
field feedback. See [repeated embeds](/repeated-embeds).

## Internal compiled configuration

`FormDefinition`, `FieldDefinition`, `EmbeddedDefinition`, and `RepeatedDefinition`
are immutable compiled records, not the application authoring API. Field records
hold `FieldConfig`; collection records hold `PresentationConfig` or `EmbedConfig`.
`FieldSpec(path, config)` is a public reusable declaration for control batches
and composite children. No record accepts legacy option arrays.
