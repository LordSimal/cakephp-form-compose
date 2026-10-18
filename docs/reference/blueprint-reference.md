---
title: Blueprint reference
description: Blueprint declarations, runtime builder operations, configuration order, and scoped layouts.
---

# Blueprint reference

Author local forms with inline controls and reusable forms with
[blueprints](/blueprints). Use [Reusable forms](/reusable-forms) to choose a
starting point and [Nested blueprints](/composition) for embedded sections.
Validation and persistence stay in CakePHP.

## Presentation options

These options apply to `presentation()` and local
`$form->blueprint(..., options: [...])` or layout `embedded(..., options: [...])`:

| Option | Type | Default and effect |
| --- | --- | --- |
| `template` | Nonempty `string` | Explicit layout element; shorthand for `templates['control']`. |
| `templates` | `array<string, string>` | `[]`. The `control` key selects the layout element. |
| `variant` | `string` | Omitted. Requests `blueprint/variants/{name}/control.php`; scoped to this layout. See [Use variants](/variants). |
| `attributes` | `array<string, mixed>` | `[]`. Wrapper attributes supplied to the layout; the bundled layout has no wrapper. |
| `templateData` | `array<string, mixed>` | `[]`. Data scoped to this layout, not inherited by fields or children. |

Local options replace matching top-level definition options. Conflicting singular
and plural control template selections fail. Without an explicit layout, the theme
renders fields and embeds in order. See [blueprint templates](/template-reference#blueprint-layouts).

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
[blueprint presentation contracts](#presentation-options).

### Field

```php
public function __construct(
    string $path,
    ?string $type = null,
    string|Html|Translated|Closure|false|null $label = null,
    string|Html|Translated|Closure|false|null $help = null,
    array $options = [],
    ?string $template = null,
    ?array $templates = null,
    ?array $templateData = null,
);
```

Repeatable class attribute for ordered field configuration. Non-null shorthand
arguments override matching `options` keys. The options array accepts every
[built-in field option](/field-options). Label/help closures take no arguments
and return `string`, `Html`, `Translated`, `false`, or `null`; they run once per
field resolution after configuration merging. See [translation options](/blueprints#translate-a-blueprint)
for PHP 8.5 attribute syntax and locale timing.

### Embed

```php
public function __construct(string $path, string $blueprint, array $options = []);
```

Repeatable class attribute mounting a child blueprint class or discovered alias
at a relative path. See [embedding an association](/composition#compose-an-association).

### AsFieldType

```php
public function __construct(string $name);
```

Registers a custom `FieldTypeInterface` class under a discovered alias. See the
[custom field type example](/blueprint-configuration#discover-custom-field-types).

### Declaration behavior

```php
#[Field(
    'title',
    label: 'Article title',
    options: [
        'attributes' => ['maxlength' => 160],
    ],
)]
```

Place field/embed attributes on `FormBlueprint` subclasses. No PHP properties are
needed for fields. `Form` is optional when resolving by class name; it supplies
an alias for discovery. Use `options` for explicit nulls, such as
`['value' => null]`, and for options without shorthand arguments.

The factory converts attributes into internal configuration records. Application
code supplies blueprint class names or discovered aliases to binding and rendering.

## Translated text

`FormCompose\Form\Field\Translated` is an immutable message value for field labels/help:

```php
public function __construct(
    string $message,
    array $arguments = [],
    string $domain = 'default',
    string $context = '',
);

public function translate(): string;
```

Construction stores metadata without translating. Field resolution calls
`translate()` through CakePHP's domain/context translation API using the active
locale. Formatting arguments use CakePHP's message formatting. Results remain
ordinary text and are escaped when rendered. The object does not cache translated
results or implement implicit string conversion. See
[translation behavior and extraction limitations](/blueprints#translate-a-blueprint).

## configure() and BlueprintBuilder

```php
public function configure(BlueprintBuilder $form, FormContext $context): void;
```

Override this hook on `FormCompose\Blueprint\FormBlueprint` for dynamic configuration.
Attributes establish the starting configuration; embedded children resolve before
the parent's hook. The compiler-supplied `BlueprintBuilder` is mutable:

| Signature | Effect |
| --- | --- |
| `add(string $path, array $options = []): static` | Append a field. |
| `with(string $path, array $options): static` | Replace an existing field's matching top-level options. |
| `remove(string $path): static` | Remove an existing field or embed. |
| `embed(string $path, string $blueprint, array $options = []): static` | Resolve and append a blueprint class/alias in this scope. |
| `presentation(array $options): static` | Set blueprint layout options. |

```php
// In a blueprint class; import FormCompose\Blueprint\BlueprintBuilder and FormCompose\Blueprint\FormContext.
public function configure(BlueprintBuilder $form, FormContext $context): void
{
    $form->with('title', ['help' => 'Displayed above the article.']);
    if (!$context->request->getAttribute('canPublish')) {
        $form->remove('published');
    }
}
```

Unknown targets and duplicates fail. Use the builder supplied to `configure()` so
embeds resolve through the application factory with the correct scoped context.
The builder's required collaborators and `build()` snapshot belong to
[compiler integration](/extending#subclassing-services).

## FormContext

The hook receives `FormCompose\Blueprint\FormContext`:

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
3. Per-call field options.

Child redeclarations retain the original field position. Duplicate declarations
within one class fail. Hook inheritance follows PHP: call `parent::configure()`
when preserving inherited runtime configuration. Nested option arrays are replaced,
not recursively merged.

Compilation happens at binding when selecting defaults, or for each explicit
`blueprint($classOrAlias)` call. `blueprint()` with no argument reuses the selected
compiled collection and does not rerun its hook. Each resolution gets independent
blueprint state and a new builder; shared constructor dependencies keep their
usual references. Request-dependent configuration is not cached as discovery metadata.

## BlueprintView

Inside a blueprint layout, `$form` is a scoped `FormCompose\Blueprint\BlueprintView`,
not the outer `FormSession`. It cannot open or close a form.

| Signature | Behavior |
| --- | --- |
| `has(string $path): bool` | Inspect final field/embed membership without rendering. |
| `field(string $field, array $options = []): BoundField` | Resolve an available relative field for manual parts. |
| `control(string $field, array $options = []): string` | Render a configured relative field; explicitly removed fields return `''`. |
| `controls(array $fields): string` | Batch syntax from session `controls()`, using relative paths and no wrapper. |
| `embedded(string $path, array $options = []): string` | Render an already compiled child with optional presentation overrides; removed children return `''`. |

Readonly `definition` holds the local compiled collection; `path` holds its mount.
Unknown names throw `InvalidArgumentException`. `field()` also throws for removed
fields, so guard optional manual parts with `has()`. Outer session `control()` can
still explicitly request fields omitted from a blueprint: removal is presentation,
not authorization. See [conditional layouts](/composition#conditional-fields-and-parent-overrides).
For value checks and request access, see
[blueprint layout inspection](/blueprints#inspect-values-and-field-configuration)
and [request lookups](/blueprints#read-request-data-in-a-layout).

## BlueprintFactory

`FormCompose\Blueprint\BlueprintFactory` normally comes from the plugin's application
service registration. Its principal application methods are:

```php
public function compile(string $blueprint, FormContext $context): FormDefinition;
public function types(array $overrides = []): array;
```

The first compiles a class/alias without rendering; the second resolves custom
field types with explicit instances taking precedence. See
[services and discovery](/blueprint-configuration) for construction and cache
configuration, and [service extensions](/extending#subclassing-services) for
factory overrides.

## Repeated blueprints and occurrence options

`#[Embed($path, $blueprint, options: [...])]` accepts occurrence presentation.
`#[EmbedMany($path, $blueprint, keys: null, options: [...])]` repeats a blueprint.
Both support `template`, `templates`, `templateData`, and `variant` in `options`.
A child blueprint's own layout is retained for `BlueprintView::render()`.

`BlueprintBuilder::embedMany(string $path, string $blueprint,
?array $keys = null, array $options = []): static` compiles rows immediately using
the factory's scoped resolver, allowing parent `with()` and `remove()` calls.

`BlueprintView::embeddedMany(string $path, array $options = []): string` renders
compiled rows; removed mounts return `''` and unknown mounts throw.
`BlueprintView::render(array $options = []): string` renders that child's own
layout at its existing path without re-running hooks or opening another form.

`FormContext::value(string $path): mixed` reads relative values using the binding's
source ordering. `path` is the absolute mount; `valueSources` retains that ordering.
`context` keeps Cake's metadata provider and `request` remains the original request.
`BlueprintRenderer::createView()` remains the factory for every repeated row;
`createRepeatedView()` supplies the collection feedback facade. Override those
protected factories through the injected blueprint renderer to specialize behavior.

## Internal compiled configuration

`FieldDefinition`, `FormDefinition`, `EmbeddedDefinition`, and `RepeatedDefinition`
are internal configuration records. They contain no rendering or submission state.
They are not accepted by application-facing field, binding, or embedding calls.
Use inline option arrays or blueprint declarations and hooks to author forms.

Compiler and renderer subclasses may encounter these records through service
extension points. `BlueprintBuilder::build()` returns an immutable snapshot for the
compiler; scoped layouts should use `has()`, `field()`, `embedded()`, and
`embeddedMany()` instead of manipulating the compiled tree. Session
`renderDefinition()` and `renderRepeated()` are internal bridges for those layouts.
