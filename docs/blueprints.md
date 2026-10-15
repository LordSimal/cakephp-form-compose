---
description: Declare reusable CakePHP forms with attributes, inject application services, and configure conditional fields without giving up PHP templates.
---

# Form blueprints

A blueprint is a dedicated PHP class describing a form's fields. Attributes hold
static configuration; an optional PHP hook supplies dynamic choices and
conditional fields. Values and validation metadata still come from the CakePHP
context you bind. Your PHP templates own the markup and layout.

Blueprints are optional. Existing `bind($user)`, inline `control()` calls, themes,
and [ordinary PHP definitions](./definitions.md) continue to work.

## Declare and use a blueprint

Place a PSR-4-autoloadable class in your application's `src/Form/` directory:

```php [src/Form/ProfileForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('profile')]
#[Field('name', label: 'Full name')]
#[Field('email', type: 'email', help: 'Use your work address.')]
class ProfileForm extends FormBlueprint
{
}
```

`Form` gives the class a discovery alias. Each `Field` declares a path and its
configuration, in presentation order. These declarations do not require PHP
properties or store the bound values.

There are two ways to select the blueprint. Render it at a particular location,
or select it during binding so individual field calls inherit its defaults:

::: code-group

```php [Render a collection here]
<?php
use App\Form\ProfileForm;

$form = $this->Form->bind($user);
?>
<?= $form->open() ?>
<?= $form->definition(ProfileForm::class) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

```php [Set binding defaults]
<?php
use App\Form\ProfileForm;

$form = $this->Form->bind($user, definition: ProfileForm::class);
?>
<?= $form->open() ?>
<?= $form->control('name') ?>
<?= $form->control('email') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

:::

Both entry points accept aliases after plugin bootstrap: use
`$form->definition('profile')` or `bind($user, definition: 'profile')`. A class name
works outside the discovery paths and does not require a `Form` attribute.

| Selection | Configuration lifetime | Useful for |
| --- | --- | --- |
| `definition('profile')` or `definition(ProfileForm::class)` | Compiled and rendered for this call; no defaults are installed. | Rendering a collection or composing multiple sections. |
| `bind($user, definition: 'profile')` | Compiled once at binding; defaults apply to subsequent field calls. | Manual layouts that use `field()` and `control()` independently. |
| `definition()` with no argument | Renders the collection already selected at binding. | Rendering the selected collection without resolving it again. |

Explicit local definitions can be used alongside binding defaults. Their field
options override matching defaults for that rendering, and the selected defaults
remain available to later calls. Without a selected definition, a no-argument
`definition()` renders nothing.

The `Field` shorthand accepts `path`, `type`, `label`, `help`, `template`,
`templates`, and `templateData`. Its `options` argument accepts the same
configuration as a `FieldDefinition`, including choices and attributes:

```php
#[Field('email', type: 'email', template: 'forms/email', options: [
    'attributes' => ['autocomplete' => 'email', 'data-controller' => 'email'],
])]
```

Non-null shorthand arguments override matching `options` keys. `label: false`
suppresses the label. Use `options` when an explicit null is needed, such as
`options: ['value' => null]`. Text is escaped; trusted markup requires an explicit
`FormCompose\Rendering\Html` object, just as with inline controls. Translate
request-dependent labels in the PHP hook using CakePHP's translation functions.

## Compose multiple definitions

Use several local definitions inside one form. For example, if
`BillingAddressForm` declares `billing.street` and `billing.city`:

```php
use App\Form\BillingAddressForm;

$form = $this->Form->bind($user);
echo $form->open();
echo $form->definition('profile');
echo '<section class="billing">';
echo $form->definition(BillingAddressForm::class);
echo '</section>';
echo $form->control('notes');
echo $form->submit('Save');
echo $form->close();
```

Every collection uses the session's context, request, theme, custom field types,
and submission protection. Each blueprint runs its configuration hook when its
`definition()` call is made, without changing later field defaults. Opening and
closing tags belong to the session, so render them once.

Choose distinct paths, such as `billing.street`, for independent sections, or
[mount relative definitions with `at` or `Embed`](./composition.md#compose-an-association). Overlapping definitions retain the
ordinary duplicate-input and ID checks. Merely selecting a definition does not
register its fields: successful input rendering does.

## Keep control of the layout

Select a default element with `#[Form('profile', template: 'forms/profile')]`,
or use the bundled sequential layout. The [layouts and composition guide](./composition.md)
shows grouping fields in PHP elements, nested definitions, and conditional `has()` checks.

You can select fields individually instead of rendering the whole collection:

```php
$form = $this->Form->bind($user, definition: ProfileForm::class);
echo $form->open();
echo $form->control('name');

$email = $form->field('email', ['label' => 'Contact email']);
echo '<section class="contact">';
echo $email->label(), $email->input(), $email->help(), $email->errors();
echo '</section>';

echo $form->submit('Save');
echo $form->close();
```

These calls use the same blueprint defaults, themes, binding, and submission
contracts as collection rendering. Fields outside the blueprint can still be
requested explicitly. Render each input presentation once with a unique ID.

## Configure choices and conditional fields

Override `configure()` when the field configuration depends on the current
binding. The following example assumes application services `CountryChoices`
and `AccountPermissions`, with methods returning a choice map and a permission
decision respectively:

```php [src/Form/ProfileForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use App\Service\AccountPermissions;
use App\Service\CountryChoices;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use function Cake\I18n\__;

#[Form('profile')]
#[Field('name', label: 'Full name')]
#[Field('email', type: 'email')]
#[Field('country_id')]
class ProfileForm extends FormBlueprint
{
    public function __construct(
        protected readonly CountryChoices $countries,
        protected readonly AccountPermissions $permissions,
    ) {
    }

    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->with('country_id', ['options' => $this->countries->all()]);
        $form->with('email', ['label' => __('Contact email')]);

        if ($this->permissions->canManageAccounts($context->request)) {
            $form->add(
                FieldDefinition::make('active', 'checkbox')->label(__('Account enabled')),
            );
        }
    }
}
```

`$context->context` is the current CakePHP `ContextInterface`; for example,
`$context->context->val('account_type')` reads a context value. The current
`ServerRequest` is available as `$context->request`. Runtime hooks receive the
same context and request that the form uses. Field values subsequently follow
the plugin's ordinary [value-source rules](./usage.md).

Each blueprint resolution gets a fresh instance and builder, including when the
blueprint has been registered as a shared service. Compilation clones the
blueprint seed so an object-valued registration is reusable too; constructor
dependencies retain their normal shared references. The hook runs once after
reading attributes: at `bind()` when selecting defaults, or on each explicit
`definition($aliasOrClass)` call when rendering locally. Rendering the already
selected collection with `definition()` does not rerun the hook. Exceptions
propagate; a failed hook renders no fields from its partial configuration.
Choices, permission decisions, and bound data are never cached by discovery.
Conditions run on the server; changing visibility immediately in the browser
requires an application-owned progressive-enhancement integration.

### Remove or hide a declared field

Attributes establish defaults; they do not make fields permanent. Remove a field
inside the hook with `$form->remove('email')`. To retain its submitted value as a
hidden control instead, use `$form->with('email', ['type' => 'hidden'])`.

| Builder operation | Result |
| --- | --- |
| `add($field)` | Append a `FieldDefinition`; duplicate paths fail. |
| `with($path, $options)` | Override top-level options without changing order. |
| `remove($path)` | Remove an existing field from the collection. |
| `build()` | Return an immutable `FormDefinition` snapshot. |

Unknown paths passed to `with()` or `remove()` fail clearly. Removing a field
excludes it from the rendered collection; an explicit `control('email')` still
requests an ordinary control. For a manual layout, inspect membership with
`$form->binding->definition->has('email')` before requesting an optional field.
Authorize submitted changes in the application independently of presentation.

For one-use customization outside the class, compile and customize its immutable
result before binding:

```php
use FormCompose\FormContext;

// $blueprints is the application factory; $context is the CakePHP form context
// that will also be passed to bind().
$definition = $blueprints->definition(
    ProfileForm::class,
    new FormContext($context, $this->getRequest()),
);
$form = $this->Form->bind($context, definition: $definition->remove('email'));
```

## Inject application services

Blueprints receive ordinary constructor dependencies. The plugin registers
`FormCompose\Blueprint\BlueprintFactory` with CakePHP's application container;
configure the helper with that factory when blueprints or discovered field types
need services. The session keeps the same factory for later `definition()` calls.
A dependency-free blueprint needs no container wiring.

For example, add a factory argument to a controller action and pass it alongside
your existing view data:

```php [Controller action]
use FormCompose\Blueprint\BlueprintFactory;

public function edit(BlueprintFactory $blueprints): void
{
    $this->set('blueprints', $blueprints);
    // Keep your existing record loading, authorization, and submission handling.
}
```

Then configure the helper in `AppView::initialize()`:

```php [src/View/AppView.php]
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'blueprintFactory' => $this->get('blueprints'),
]);
```

Alternatively, set the option on the existing helper before binding:
`$this->Form->setConfig('blueprintFactory', $blueprints)`.

CakePHP 6 autowires concrete dependencies. Register interfaces, scalar arguments,
and application-specific factories in `Application::services()` as usual.
Explicit application registrations for `BlueprintFactory` are preserved. The
factory uses `getNew()` to construct blueprints independently. Form classes never
receive or look up the container themselves. Missing dependencies and invalid
container substitutions fail instead of silently dropping fields.

## Configuration order

Configuration is applied in this order, with later options taking precedence:

1. Parent attribute declarations, followed by child declarations.
2. The concrete blueprint's `configure()` hook.
3. An explicit `FieldDefinition` passed to `field()` or `control()`.
4. Explicit per-call options.

A child declaration overrides matching parent options and retains the field's
original position. Duplicate paths on one class are rejected. Hook inheritance
uses ordinary PHP rules: call `parent::configure()` when extending a parent's
runtime configuration.

Nested option arrays are replaced as a whole. Blueprint defaults never override
explicit null, false, zero, or empty-string values. A plain immutable
`FormDefinition` can also be supplied to `bind()` and becomes binding defaults.
An explicit `$form->definition($other)` accepts an immutable collection, blueprint
alias, or class name, and renders it without replacing the selected defaults. Without a selected definition, `$form->definition()`
returns an empty string inside an open form.

## Discovery and cache configuration

`FormComposePlugin::bootstrap()` registers the `formCompose` resolver collection
only when the application has not already configured it. The defaults scan both
direct and nested PHP files under `src/Form/` and `src/Field/` in the application
and loaded plugins, using the standard `_cake_core_` cache and file validation.
Plugin blueprint aliases should be qualified, for example `#[Form('Blog.edit')]`.
Duplicate form aliases and duplicate custom-type aliases are rejected within
their respective registries.

Set your own collection configuration **before plugin bootstrap** to change paths
or cache settings:

```php
use Cake\AttributeResolver\AttributeResolver;

AttributeResolver::setConfig('formCompose', [
    'paths' => [
        'src/Form/*.php', 'src/Form/**/*.php',
        'src/Field/*.php', 'src/Field/**/*.php',
        'src/Admin/Form/*.php', 'src/Admin/Form/**/*.php',
    ],
    'cache' => '_cake_core_',
    'validateFiles' => true,
]);
```

Keep both direct and recursive patterns: the current resolver glob implementation
requires a directory below `**/`. Use `'cache' => false` in the configuration array
when explicitly disabling persistent metadata caching.

For a separately configured collection, inject
`new BlueprintFactory($container, collection: 'myForms')` as the helper option.
The factory consumes existing configuration and never creates it.

Clear discovery metadata after adding, removing, or renaming declarations during
deployment with `AttributeResolver::clear('formCompose')`; warming uses
`AttributeResolver::warm('formCompose')`. File validation alone does not discover
new files or guarantee invalidation in a running process. The factory retains no
alias index, so it observes the resolver's refreshed collection after clearing.

## Discover custom field types

Put a PSR-4-autoloadable `FieldTypeInterface` implementation in `src/Field/` and
mark it with `#[AsFieldType('date-range')]`:

```php [src/Field/DateRangeType.php]
<?php
declare(strict_types=1);

namespace App\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Field\Submission;
use FormCompose\Rendering\Attributes;
use function Cake\I18n\__;

#[AsFieldType('date-range')]
class DateRangeType implements FieldTypeInterface
{
    public function render(BoundField $field, Attributes $attributes): string
    {
        $values = is_array($field->state->value) ? $field->state->value : [];
        $start = $attributes->replace([
            'type' => 'date',
            'name' => $field->state->name . '[start]',
            'value' => $values['start'] ?? null,
        ]);
        $end = $attributes->replace([
            'type' => 'date',
            'name' => $field->state->name . '[end]',
            'id' => $field->state->id . '-end',
            'value' => $values['end'] ?? null,
            'aria-label' => __('End date'),
        ]);

        return '<input' . (string)$start . '><input' . (string)$end . '>';
    }

    /**
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return $attributes->has('disabled') ? [] : [
            new Submission($field->state->name . '[start]'),
            new Submission($field->state->name . '[end]'),
        ];
    }
}
```

The first input retains the field's ID and label association; the second gets a
unique ID and an accessible name. Both inherit disabled state and other input
attributes. See [custom field types](./extending.md#custom-field-types) for the
rendering and submission contracts.

Select it using a blueprint's `type: 'date-range'` or inline `['type' => 'date-range']`.
Discovered types use the same factory and constructor injection as blueprints;
explicit `bind(..., ['types' => ['date-range' => $instance]])` registrations win
without constructing the overridden discovered type.

Discovery and configuration register no submitted fields. Custom controls still
describe their real submitted inputs through `submissions()`, and registration
occurs after successful rendering. CSRF, method overrides, disabled controls, and
locked hidden values retain their existing behavior.
