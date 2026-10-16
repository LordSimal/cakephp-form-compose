---
title: Blueprint services and discovery
description: Inject blueprint services, configure discovery, and manage declaration caches.
---

# Blueprint services and discovery

Start with [form blueprints](./blueprints.md) to declare and render fields. This
page covers application services and deployment configuration. Static blueprints
with no constructor dependencies need no additional container wiring.

## Inject application services

Blueprints receive ordinary constructor dependencies. The plugin registers
`FormCompose\Blueprint\BlueprintFactory` with CakePHP's application container;
configure the helper with that factory when blueprints or discovered field types
need services. The session keeps the same factory for later `definition()` calls.
A dependency-free blueprint needs no container wiring.

For example, suppose your application already has an `App\Service\StatusChoices`
service whose `all()` method returns a value/label map. Inject it into the blueprint:

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use App\Service\StatusChoices;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

#[Form('article')]
#[Field('title')]
#[Field('status', type: 'select')]
class ArticleForm extends FormBlueprint
{
    public function __construct(protected readonly StatusChoices $statuses)
    {
    }

    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->with('status', ['options' => $this->statuses->all()]);
    }
}
```

Then add a factory argument to a controller action and pass it alongside your
existing view data:

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

Now `$this->Form->render(ArticleForm::class, $article)` uses the injected service
when compiling that blueprint. Import `App\Form\ArticleForm` in the view.

CakePHP 6 autowires concrete dependencies. Register interfaces, scalar arguments,
and application-specific factories in `Application::services()` as usual.
Explicit application registrations for `BlueprintFactory` are preserved. The
factory uses `getNew()` to construct blueprints independently. Form classes never
receive or look up the container themselves. Missing dependencies and invalid
container substitutions fail instead of silently dropping fields.

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
