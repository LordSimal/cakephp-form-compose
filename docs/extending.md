---
title: Custom controls and service extensions
description: Add custom field types, renderers, and service subclasses to FormCompose.
---

# Custom controls and service extensions

Use [a theme](./themes.md) to change markup, or consult the
[template reference](./template-reference.md) for element contracts. This guide covers custom
submission behavior, renderer implementations, and service subclasses.

## Custom field types

Implement `FieldTypeInterface` to render a custom control and return its submitted
names as `Submission` objects. Register it using
`bind($entity, ['types' => ['date-range' => $rangeType]])`, then request
`['type' => 'date-range']` on a field. Alternatively, mark the implementation with
`#[AsFieldType('date-range')]` for [automatic discovery](./blueprint-configuration.md#discover-custom-field-types).
Explicit `types` registrations override discovered types. A complete
[date-range implementation](./blueprint-configuration.md#discover-custom-field-types)
shows rendering, disabled state, accessible names, and both submitted inputs.

Discovered types are constructed lazily when first rendered or explicitly
registered. Each binding reuses its own type instance. Ordinary binding and field
inspection do not instantiate unused types or require their constructor services.

::: info Describe the inputs your control submits
`render()` must return escaped markup without changing security state.
`submissions()` must describe exactly the successful inputs emitted, respecting
`disabled` and other submission behavior. Use `Submission($name, value: $value)`
for a hidden value that must remain locked. Explicit unlocking is available with
`locked: false`. Presentation templates cannot infer these semantics from HTML.
:::

Composite controls can also implement `InputIdentityProviderInterface`:

```php
public function inputIds(BoundField $field, Attributes $attributes): array
{
    return [$field->state->id . '-end'];
}
```

Return additional DOM IDs emitted by `render()`, excluding the bound field's main
ID, which is already tracked. This method must not render or register anything.
The session checks these identities alongside native choice and feedback IDs;
a collision rolls back the complete control's pending registrations. Existing
`FieldTypeInterface` implementations remain valid without this optional interface.
Templates must preserve the supplied identities; arbitrary handwritten HTML is
the application's responsibility.

## Custom renderers

Custom renderers implement `RendererInterface::render(string $part, RenderNode $node)`.
A node exposes its `scope`, `type`, prepared `variables`, and presentation `options`.
Its `variables['templateData']` always contains the validated custom data bag,
defaulting to `[]`. Preserve it when forwarding nodes or preparing template locals.
Fields, forms, buttons, and hidden inputs all use this same contract. Native
rendering performs no binding or security mutations; the lifecycle commits state
only after the relevant rendering succeeds.

### CakePHP form protection

The `Security\FormProtection` integration directly uses Cake's `FormProtector`.
Cake marks this class internal, so compatibility tests against supported Cake
versions are required when upgrading that integration.

Dynamic collection protocols, reactive validation, and frontend state
synchronization can be implemented as application integrations.

## Subclassing services

Service classes support subclassing, with protected methods and dependencies for
specialization. Use theme elements for markup changes and `FieldTypeInterface` for
new control semantics. To adjust built-in behavior, these hooks connect subclasses
to the normal form-building API:

| Class | Extension points |
| --- | --- |
| `FormHelper` | `createSession()` selects the session returned by `bind()` |
| `FormSession` | `createFieldResolver()` and `createLifecycle()` supply collaborators |
| `FieldResolver` | `inferType()` and factories for bound fields, field types, value resolution, and field conventions |
| `FieldConventions` | `choices()`, `resolve()`, and `messages()` specialize choice discovery and association defaults |
| `FormHelper` | `createBlueprintFactory()` supplies blueprint construction and discovery |
| `BlueprintFactory` | `createCompiler()`, `createInstance()`, and `attributes()` specialize construction and discovery |
| `BlueprintCompiler` | `createBuilder()` supplies the builder passed to blueprint hooks |
| `DefinitionRenderer` | `createView()` supplies the scoped layout API |
| `DefinitionView` | `control()`, `field()`, `embedded()`, and `qualify()` customize layout behavior |
| `ValueResolver` | `fromSources()` specializes request/context lookup |
| `BoundField` | Field-part methods and `createFieldAttributes()` customize field presentation |
| `NativeType` | `choice()` and `createChoices()` specialize standard controls |
| `PhpRenderer` | `layered()` and `element()` customize template lookup and execution |

For example, an application helper can select a session that uses a custom resolver.
These three files connect a website-field convention to the public `bind()` API:

::: code-group

```php [src/View/Helper/AppFormHelper.php]
<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Form\AppFormSession;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormSession;
use FormCompose\Rendering\RenderContext;
use FormCompose\View\Helper\FormHelper;

class AppFormHelper extends FormHelper
{
    protected function createSession(
        FormBinding $binding,
        RenderContext $rendering,
        BlueprintFactory $blueprints,
    ): FormSession {
        return new AppFormSession($binding, $rendering, $blueprints);
    }
}
```

```php [src/Form/AppFormSession.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Binding\FieldResolver;
use FormCompose\FormSession;

class AppFormSession extends FormSession
{
    protected function createFieldResolver(): FieldResolver
    {
        return new AppFieldResolver($this->binding, $this->rendering);
    }
}
```

```php [src/Form/AppFieldResolver.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Binding\FieldResolver;

class AppFieldResolver extends FieldResolver
{
    protected function inferType(string $path, array $options): string
    {
        return $path === 'website' ? 'url' : parent::inferType($path, $options);
    }
}
```

:::

When overriding `createSession()`, accept and forward its third `BlueprintFactory`
argument. This keeps application dependencies and custom discovery configuration
available to local `$form->definition('profile')` calls in the session subclass.
Overrides of the earlier two-argument factory must update their signature. Direct
`new FormSession($binding, $rendering)` construction still supports dependency-free
blueprints; pass the factory as the optional third constructor argument when needed.

Load the helper in `AppView::initialize()`:

```php
use App\View\Helper\AppFormHelper;

$this->addHelper('Form', ['className' => AppFormHelper::class]);
```

Now `$this->Form->bind($entity)->field('website')` infers `url`. Explicit field types
still take priority over type inference. Override `FieldResolver::createFieldConventions()`
to supply application-specific association or choice-list conventions. The default
`FieldConventions` uses the binding and the rendering context's optional live
`viewVariable` lookup. `Choices`, `ChoiceAttributes`, and
`FieldAttributes` also support subclassing. `FormLifecycle` accepts custom
`FormConstruction` and `FormProtection` instances through its constructor;
override the session's `createLifecycle()` to supply them. Preserve lifecycle
checks and register the inputs actually rendered when specializing these services.

`FormSession` accepts an optional fourth `DefinitionRenderer` constructor argument.
Supply a subclass from the helper's `createSession()` override to customize its
protected `createView()` factory. The default view delegates to the original
session's field and control methods, retaining application resolver and field
subclasses. It does not construct another lifecycle.

`FormLifecycle` accepts an optional fourth `InputRegistry` dependency. Its registry
shares the lifecycle's `FormProtection` service and stages nested definition inputs
until the whole layout succeeds. Preserve duplicate-ID checks and rollback when
replacing that collaborator.

The factory injects a scoped resolver through `BlueprintCompiler::withResolver()`
and `FormBuilder::withResolver()`. Custom compiler and builder factories retain
string embedding support without changing their existing factory signatures.

::: details Which classes support inheritance?

Service dependencies use readonly properties, while subclasses can declare their
own mutable state. Immutable configuration and data objects, including `Theme`,
`FieldDefinition`, `FieldState`, `Attributes`, and `RenderNode`, remain final so their
value and escaping contracts stay consistent. Small stateless utilities such as
`InputNames` and `NativeValue` are also final.
:::
