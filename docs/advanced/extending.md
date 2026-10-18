---
title: Custom controls and service extensions
description: Add custom field types, renderers, and service subclasses to FormCompose.
---

# Custom controls and service extensions

Use [a theme](/themes) to change markup, or consult the
[template reference](/template-reference) for element contracts. This guide covers custom
submission behavior, renderer implementations, and service subclasses.

## Custom field types

Start with [custom field types](/custom-fields) for a scalar editor or
[composite fields](/custom-fields#create-a-date-range) for a group of native inputs. The
[custom field reference](/custom-field-reference) covers lower-level submission
contracts.

## Custom renderers

Custom renderers implement `RendererInterface::render(string $part, RenderNode $node)`.
A node exposes its `scope`, `type`, prepared `variables`, and typed `presentation` (`PresentationConfig`).
Its `variables['templateData']` always contains the validated custom data bag,
defaulting to `[]`. Preserve it when forwarding nodes or preparing template locals.
Fields, forms, buttons, and hidden inputs all use this same contract. Native
rendering performs no binding or security mutations; the lifecycle commits state
only after the relevant rendering succeeds.

## Subclassing services

Use a service subclass when binding behavior needs an application convention.
For example, the following helper selects a session whose resolver infers URL
controls for `website`. Create all three classes, then load the helper below.

::: code-group

```php [src/View/Helper/AppFormHelper.php]
<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Form\AppFormSession;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\FormSession;
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
use FormCompose\Form\FormSession;

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
use FormCompose\Configuration\FieldConfig;

class AppFieldResolver extends FieldResolver
{
    protected function inferType(string $path, FieldConfig $config): string
    {
        return $path === 'website' ? 'url' : parent::inferType($path, $config);
    }
}
```

:::

Load the helper in `AppView::initialize()`:

```php
use App\View\Helper\AppFormHelper;

$this->addHelper('Form', ['className' => AppFormHelper::class]);
```

Resolving `website` through this helper now infers `url`. Explicit field types
still take priority over type inference.

### Other extension points

| Class | Extension points |
| --- | --- |
| `FormHelper` | `createSession()` selects the session returned by `bind()` |
| `FormSession` | `createFieldResolver()` and `createLifecycle()` supply collaborators |
| `FieldResolver` | `inferType()` and factories for bound fields, field types, child resolution, values, and field conventions |
| `FieldConventions` | `choices()` and `resolve()` specialize choice discovery and association defaults; `ContextFeedback` handles messages through `FieldResolver::createContextFeedback()` |
| `FormHelper` | `createBlueprintFactory()` supplies blueprint construction and discovery |
| `BlueprintFactory` | `createCompiler()`, `createInstance()`, and `attributes()` specialize construction and discovery |
| `BlueprintCompiler` | `createBuilder()` supplies the hook builder; `createRepeatedCompiler()` supplies row compilation |
| `BlueprintRenderer` | `createView()` supplies the scoped layout API |
| `BlueprintView` | `control()`, `field()`, `embedded()`, and `qualify()` customize layout behavior |
| `ValueResolver` | `fromSources()` specializes request/context lookup |
| `BoundField` | Field-part methods and `createFieldAttributes()` customize field presentation |
| `NativeType` | `choice()` and `createChoices()` specialize standard controls |
| `PhpRenderer` | `layered()` and `element()` customize template lookup and execution |

When overriding `createSession()`, accept and forward its third `BlueprintFactory`
argument. This keeps application dependencies and custom discovery configuration
available to local `$form->blueprint('profile')` calls in the session subclass.
Direct `new FormSession($binding, $rendering)` construction supports
dependency-free blueprints; pass the factory as the optional third constructor argument when needed.

Override `FieldResolver::createFieldConventions()`
to supply application-specific association or choice-list conventions. The default
`FieldConventions` uses the binding and the rendering context's optional live
`viewVariable` lookup. `Choices`, `ChoiceAttributes`, and
`FieldAttributes` also support subclassing. `FormLifecycle` accepts custom
`FormConstruction` and `FormProtection` instances through its constructor;
override the session's `createLifecycle()` to supply them. Preserve lifecycle
checks and register the inputs actually rendered when specializing these services.

`FormSession` accepts an optional fourth `BlueprintRenderer` constructor argument.
Supply a subclass from the helper's `createSession()` override to customize its
protected `createView()` factory. The default view delegates to the original
session's field and control methods, retaining application resolver and field
subclasses. It does not construct another lifecycle.

`FormLifecycle` accepts an optional fourth `InputRegistry` dependency. Its registry
shares the lifecycle's `FormProtection` service and stages nested blueprint inputs
until the whole layout succeeds. Preserve duplicate-ID checks and rollback when
replacing that collaborator.

The factory constructs `BlueprintCompiler` with its scoped blueprint resolver.
Override `createBuilder(FormDefinition $definition, FormContext $context)` to
supply a builder subclass. Its constructor requires the initial compiled tree,
a `(string $path, string $identifier): FormDefinition` child resolver, and a
`(RepeatedDefinition $entry): RepeatedDefinition` occurrence compiler. Both
callbacks must retain the current context and the owning factory's resolution.

`BlueprintCompiler::createRepeatedCompiler()` supplies the row compiler used by
blueprint embeds and session `embedMany()` calls.
Override it to supply a `RepeatedCompiler` subclass, such as one specializing
`keys()`. Inferred keys are validated before any child hooks run. Each occurrence
compiles independently; adding an embed never recompiles existing sections.

::: details Which classes support inheritance?

Service dependencies use readonly properties, while subclasses can declare their
own mutable state. Immutable configuration and data objects, including `Theme`,
`FieldDefinition`, `FieldState`, `Attributes`, and `RenderNode`, remain final so their
value and escaping contracts stay consistent. Small stateless utilities such as
`InputNames` and `NativeValue` are also final.
:::

## Custom summary targets

The date-range type normally links group errors to its first eligible input.
To link them to the departure date instead, extend the
[documented date-range type](/custom-fields#create-a-date-range) and select
`type('departure-date-range')`:

`src/Form/Field/DepartureFirstDateRangeType.php`

```php [src/Form/Field/DepartureFirstDateRangeType.php]
<?php
declare(strict_types=1);

namespace App\Form\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Form\Field\BoundField;
use FormCompose\Rendering\Attributes;

#[AsFieldType('departure-date-range')]
class DepartureFirstDateRangeType extends DateRangeType
{
    public function errorTarget(BoundField $field, Attributes $attributes): ?string
    {
        return $field->inputs['end']->focusTarget;
    }
}
```

The child's `focusTarget` is set only after an eligible input renders; it is null
when that child did not emit a focusable control. `CompositeType` already implements
`ErrorTargetProviderInterface`, so this overrides its default target selection.
For a lower-level type, implement that interface alongside `FieldTypeInterface`.

Return an actual emitted control ID without `#`, or null to show an unlinked
message. An empty string is rejected. The method must not render, register, or
consume choice iterators. Types without this interface use their main
field ID; hidden and disabled controls remain unlinked. Discovered lazy types
forward the contract through the same resolved instance.

Native choice types retain prepared targets after rendering, so summaries do not
consume single-pass option iterators again. Use `InputIdentityProviderInterface`
to report additional emitted IDs for duplicate checks too.

`FormSession` accepts an optional fifth `ErrorSummaryRenderer` dependency. Its
protected `attributes()` prepares the summary/heading bags; inject a subclass
through the helper's `createSession()` to specialize this behavior. The
`InputRegistry` constructor accepts an optional second `FeedbackRegistry` and
third `FeedbackReferences`, which share its existing snapshot/rollback transaction.
The latter checks generated help/error references against rendered or registered
IDs at form closing. Preserve that coordination in
application subclasses. Summary metadata stays separate from Cake form protection.
