---
description: Look up field definitions, collections, blueprint declarations and hooks, builder methods, and scoped layout methods.
---

# Definitions and blueprints

Use [Field definitions](./definitions.md) to learn reusable configuration and
[Form blueprints](./blueprints.md) for attributed classes. Follow
[blueprint layouts](./blueprints.md#keep-control-of-the-layout) for field arrangement
and [Nested definitions](./composition.md) for embedding.
These APIs configure or render fields; validation and persistence stay in CakePHP.

## FieldDefinition

`FormCompose\Field\FieldDefinition` is immutable configuration for one path.

```php
use FormCompose\Field\FieldDefinition;

$title = FieldDefinition::make('title', 'text')
    ->label('Article title')
    ->help('Displayed above the article.')
    ->with(['attributes' => ['maxlength' => 160]]);
```

| Signature | Result |
| --- | --- |
| `new FieldDefinition(string $path, array $options = [])` | Configuration with a validated nonempty dot path. |
| `FieldDefinition::make(string $path, ?string $type = null): self` | Configuration with optional explicit type. |
| `with(array $options): self` | New configuration with matching top-level keys replaced. |
| `label(string\|Html\|false $text): self` | New configuration with label content or suppression. |
| `help(string\|Html $text): self` | New configuration with help content. Use `with(['help' => false])` to suppress it. |

Public readonly `path` and `options` expose configuration. See
[Field options](./field-options.md) for the complete option contract. A
`FieldDefinition` contains no value snapshot, HTML, or input registration.

## FormDefinition

`FormCompose\FormDefinition` is an immutable ordered collection of fields and
resolved embedded definitions. Save the return value of every change.

```php
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;

$shared = FormDefinition::make()->add(FieldDefinition::make('title'));
$editing = $shared->with('title', ['help' => 'Displayed above the article.']);
// $shared is unchanged.
```

| Signature | Result |
| --- | --- |
| `new FormDefinition(array $fields, array $options = [], array $removed = [])` | Collection of ordered `FieldDefinition`/`EmbeddedDefinition` entries; normally prefer the fluent API. |
| `FormDefinition::make(): self` | Empty collection. |
| `add(FieldDefinition $field): self` | New collection with appended field. |
| `with(string $path, array $options): self` | New collection with that field's top-level options replaced; order is unchanged. Qualified embedded paths are supported. |
| `remove(string $path): self` | New collection without that field or embedded subtree. |
| `has(string $path): bool` | Final membership check for a field or embed. |
| `field(string $path): FieldDefinition` | Existing field configuration; no binding or rendering. |
| `embed(string $path, FormDefinition $definition): self` | New collection with a relative child mounted at this path. |
| `embedded(string $path): FormDefinition` | Already compiled child, retaining its relative paths. |
| `presentation(array $options): self` | New collection with top-level definition presentation options replaced. |

Duplicate paths, invalid paths, and unknown lookup/override/removal targets throw
`InvalidArgumentException`. `with()` targets fields; use a child's `presentation()`
when constructing its layout configuration.

| Readonly property | Meaning |
| --- | --- |
| `fields` | Flattened ordered field definitions with qualified paths. |
| `entries` | Ordered field/embed rendering tree. |
| `options` | Definition layout presentation. |
| `removed` | Explicit removal paths, used to omit removed controls in layouts. |
| `index` | Derived lookup index; use `has()`, `field()`, and `embedded()` for application lookups. |

`EmbeddedDefinition` stores a mount `path` and child `definition`; normal
application construction uses `embed()`.

### Presentation options

These options apply to `presentation()` and local
`$form->definition(..., options: [...])` or layout `embedded(..., options: [...])`:

| Option | Type | Default and effect |
| --- | --- | --- |
| `template` | Nonempty `string` | Explicit layout element; shorthand for `templates['control']`. |
| `templates` | `array<string, string>` | `[]`. The `control` key selects the layout element. |
| `variant` | `string` | Omitted. Definition template variant. |
| `attributes` | `array<string, mixed>` | `[]`. Wrapper attributes supplied to the layout; the bundled layout has no wrapper. |
| `templateData` | `array<string, mixed>` | `[]`. Data scoped to this layout, not inherited by fields or children. |

Local options replace matching top-level definition options. Conflicting singular
and plural control template selections fail. Without an explicit layout, the theme
renders fields and embeds in order. See [definition templates](./template-reference.md#definition-layouts).

## Blueprint declarations

All attributes below are in `FormCompose\Attribute`.

### Form

```php
public function __construct(
    string $name,
    ?string $template = null,
    ?array $templates = null,
    ?array $templateData = null,
);
```

Class discovery alias and optional layout defaults. The alias must be nonempty
with no surrounding whitespace. Arrays use the
[definition presentation contracts](#presentation-options).

### Field

```php
public function __construct(
    string $path,
    ?string $type = null,
    string|Html|false|null $label = null,
    string|Html|null $help = null,
    array $options = [],
    ?string $template = null,
    ?array $templates = null,
    ?array $templateData = null,
);
```

Repeatable class attribute for ordered field configuration. Non-null shorthand
arguments override matching `options` keys. The options array accepts every
[built-in field option](./field-options.md).

### Embed

```php
public function __construct(string $path, string $definition);
```

Repeatable class attribute mounting a child blueprint class or discovered alias
at a relative path. See [embedding an association](./composition.md#compose-an-association).

### AsFieldType

```php
public function __construct(string $name);
```

Registers a custom `FieldTypeInterface` class under a discovered alias. See the
[custom field type example](./blueprint-configuration.md#discover-custom-field-types).

### Declaration behavior

```php
#[Field('title', label: 'Article title', options: [
    'attributes' => ['maxlength' => 160],
])]
```

Place field/embed attributes on `FormBlueprint` subclasses. No PHP properties are
needed for fields. `Form` is optional when resolving by class name; it supplies
an alias for discovery. Use `options` for explicit nulls, such as
`['value' => null]`, and for options without shorthand arguments.

`Field::definition(): FieldDefinition` exports the declaration;
`Form::options(): array` exports non-null layout defaults. Most application code
lets the blueprint factory handle these conversions.

## configure() and FormBuilder

```php
public function configure(FormBuilder $form, FormContext $context): void;
```

Override this hook on `FormCompose\FormBlueprint` for dynamic configuration.
Attributes establish the starting configuration; embedded children resolve before
the parent's hook. The compiler-supplied `FormBuilder` is mutable:

| Signature | Effect |
| --- | --- |
| `add(FieldDefinition $field): static` | Append a field. |
| `with(string $path, array $options): static` | Replace an existing field's matching top-level options. |
| `remove(string $path): static` | Remove an existing field or embed. |
| `embed(string $path, FormDefinition\|string $definition): static` | Append a resolved collection or resolve a blueprint class/alias in this scope. |
| `presentation(array $options): static` | Set definition layout options. |
| `build(): FormDefinition` | Immutable snapshot unaffected by subsequent builder changes. |

```php
// In a blueprint class; import FormCompose\FormBuilder and FormCompose\FormContext.
public function configure(FormBuilder $form, FormContext $context): void
{
    $form->with('title', ['help' => 'Displayed above the article.']);
    if (!$context->request->getAttribute('canPublish')) {
        $form->remove('published');
    }
}
```

Unknown targets and duplicates fail. A standalone `new FormBuilder()` supports
already built child definitions; string embedding requires a compiler-supplied
resolver. `withResolver(Closure $resolver): static` returns a clone with a scoped
`(string $mount, string $identifier): FormDefinition` resolver; this is an advanced
factory extension point.

## FormContext

The hook receives `FormCompose\FormContext`:

| API | Meaning |
| --- | --- |
| `context` | CakePHP `ContextInterface`: inspect values, schema, constraints, or errors. |
| `request` | Original `ServerRequest`, including application request attributes. |
| `at(string $path): self` | New context scoped to a relative mount; request is retained. |
| `ancestors` / `enter(string $class): self` | Compiler cycle tracking; entering a repeated ancestor throws `InvalidArgumentException`. |

Construct explicitly with `new FormContext($cakeContext, $request)` when using the
factory directly. Embedded hooks use relative context lookups, for example
`$context->context->val('email')`. This reads the context, not the field value
resolver's full request/context source sequence.

## Configuration order

Later options win, replacing matching top-level keys:

1. Parent attribute declarations, then child declarations.
2. The concrete blueprint's `configure()` hook.
3. Explicit `FieldDefinition` configuration at the field call.
4. Per-call field options.

Child redeclarations retain the original field position. Duplicate declarations
within one class fail. Hook inheritance follows PHP: call `parent::configure()`
when preserving inherited runtime configuration. Nested option arrays are replaced,
not recursively merged.

Compilation happens at binding when selecting defaults, or for each explicit
`definition($classOrAlias)` call. `definition()` with no argument reuses the selected
compiled collection and does not rerun its hook. Each resolution gets independent
blueprint state and a new builder; shared constructor dependencies keep their
usual references. Request-dependent configuration is not cached as discovery metadata.

## DefinitionView

Inside a definition layout, `$form` is a scoped `FormCompose\DefinitionView`,
not the outer `FormSession`. It cannot open or close a form.

| Signature | Behavior |
| --- | --- |
| `has(string $path): bool` | Inspect final field/embed membership without rendering. |
| `field(string\|FieldDefinition $field, array $options = []): BoundField` | Resolve an available relative field for manual parts. |
| `control(string\|FieldDefinition $field, array $options = []): string` | Render a configured relative field; explicitly removed fields return `''`. |
| `controls(array $fields): string` | Batch syntax from session `controls()`, using relative paths and no wrapper. |
| `embedded(string $path, array $options = []): string` | Render an already compiled child with optional presentation overrides; removed children return `''`. |

Readonly `definition` holds the local compiled collection; `path` holds its mount.
Unknown names throw `InvalidArgumentException`. `field()` also throws for removed
fields, so guard optional manual parts with `has()`. Outer session `control()` can
still explicitly request fields omitted from a blueprint: removal is presentation,
not authorization. See [conditional layouts](./composition.md#conditional-fields-and-parent-overrides).
For value checks and request access, see
[blueprint layout inspection](./blueprints.md#inspect-values-and-field-configuration)
and [request lookups](./blueprints.md#read-request-data-in-a-layout).

## BlueprintFactory

`FormCompose\Blueprint\BlueprintFactory` normally comes from the plugin's application
service registration. Its principal application methods are:

```php
public function definition(string $name, FormContext $context): FormDefinition;
public function types(array $overrides = []): array;
```

The first compiles a class/alias without rendering; the second resolves custom
field types with explicit instances taking precedence. See
[services and discovery](./blueprint-configuration.md) for construction and cache
configuration, and [service extensions](./extending.md#subclassing-services) for
factory overrides.
