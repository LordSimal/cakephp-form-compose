---
title: Blueprint services and discovery
description: Inject blueprint services, configure discovery, and manage declaration caches.
---

# Blueprint services and discovery

Start with [form blueprints](/blueprints) to declare and render fields. This
page covers application services and deployment configuration. Static blueprints
with no constructor dependencies need no additional container wiring.

## Inject application services

Suppose your application has a `StatusChoices` service whose `all()` method returns
a value/label map. Inject it into the blueprint, pass the container's blueprint
factory from your existing controller action, and configure the helper with that
factory. The three tabs show the cooperating pieces.

::: code-group

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use App\Service\StatusChoices;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

#[Form('article')]
class ArticleForm extends FormBlueprint
{
    public function __construct(protected readonly StatusChoices $statuses)
    {
    }

    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title');
        $form->add('status')->choices($this->statuses->all());
    }
}
```

```php [ArticlesController — import and edit() method]
use FormCompose\Blueprint\BlueprintFactory;

public function edit(BlueprintFactory $blueprints): void
{
    $this->set('blueprints', $blueprints);

    // Keep your existing record loading, authorization, and submission handling.
}
```

```php [Inside AppView::initialize()]
$this->addHelper('Form', [
    'className' => 'FormCompose.Form',
    'blueprintFactory' => $this->get('blueprints'),
]);
```

:::

Add the controller import at file scope and retain your existing action body.
Now `blueprint('article')` uses the injected service. The plugin registers
`BlueprintFactory` in CakePHP's application container. Passing that factory to the
helper also makes it available to local blueprint calls inside a bound session.

CakePHP 6 autowires concrete dependencies. Register interfaces, scalar arguments,
and application-specific factories in `Application::services()` as usual.
Explicit application registrations for `BlueprintFactory` are preserved. The
factory uses `getNew()` to construct blueprints independently. Missing constructor dependencies or invalid container substitutions fail at
construction.

## Discovery and cache configuration

`FormComposePlugin::bootstrap()` registers the `formCompose` resolver collection
only when the application has not already configured it. The defaults scan both
direct and nested PHP files under `src/Form/`, including `src/Form/Field/`, in the
application and loaded plugins. In production, metadata uses CakePHP 6's `_cake_attributes_`
cache without source-file validation. In debug mode, persistent caching is
disabled so new and changed declarations are discovered on the next request.
Plugin blueprint aliases should be qualified, for example `#[Form('Blog.edit')]`.
Duplicate form aliases and duplicate custom-type aliases are rejected within
their respective registries.

::: warning Configure discovery before plugin bootstrap
Set your own resolver collection before the plugin bootstraps. Changing it later
does not reconfigure the collection that the plugin already registered.
:::

For example, change the paths or cache settings like this:

```php
use Cake\AttributeResolver\AttributeResolver;
use Cake\Core\Configure;

AttributeResolver::setConfig('formCompose', [
    'paths' => [
        'src/Form/*.php',
        'src/Form/**/*.php',
        'src/Admin/Form/*.php',
        'src/Admin/Form/**/*.php',
    ],
    'cache' => Configure::read('debug') ? false : '_cake_attributes_',
    'validateFiles' => false,
]);
```

Keep both direct and recursive patterns: the current resolver glob implementation
requires a directory below `**/`. Use `'cache' => false` in the configuration array
when explicitly disabling persistent metadata caching.

For a separately configured collection, inject
`new BlueprintFactory($container, collection: 'myForms')` as the helper option.
The factory consumes existing configuration and never creates it.

Clear discovery metadata after changing declarations during deployment with
`AttributeResolver::clear('formCompose')`; warming uses
`AttributeResolver::warm('formCompose')`. File validation alone does not discover
new files or guarantee invalidation in a running process. The factory retains no
alias index, so it observes the resolver's refreshed collection after clearing.

## Discover custom field types

Put application controls in `src/Form/Field/` under `App\Form\Field`, and mark them
with `#[AsFieldType('your-type')]`. The dedicated [custom field types guide](/custom-fields)
shows a complete Markdown type. [Composite fields](/custom-fields#create-a-date-range) builds a
date range; the [custom field reference](/custom-field-reference) covers lower-level
contracts and explicit instance overrides.

Custom types share the factory's constructor injection and discovery configuration.
Explicit binding-level type instances override discovered aliases. Discovery and
field inspection register no submitted names; successful rendering or explicit
registration determines the actual protected inputs.
