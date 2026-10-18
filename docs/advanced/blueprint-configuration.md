---
title: Blueprint services and discovery
description: Inject blueprint services, configure discovery, and manage declaration caches.
---

# Blueprint services and discovery

Start with [form blueprints](/blueprints) to declare and render fields. This
page covers application services and deployment configuration.

## Inject application services

Use constructor injection when a blueprint needs application data. For example,
a workflow service can supply the statuses allowed by your publishing rules,
keeping that logic out of the form. In this example, `StatusChoices::all()` returns
the value/label map for the select field.

With the plugin loaded and the Form helper configured, CakePHP autowires the
blueprint's concrete dependencies during normal controller rendering. Your
controller needs no factory argument or extra view variables.

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

```php [View]
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->submit('Save article');
```

:::

Register interfaces, scalar arguments, and application-specific factories in
`Application::services()` as usual. The same container resolves dependencies for
[custom field types](/custom-fields), including those used by local blueprint
calls inside a bound session.

::: details Manually created views and custom factories
The plugin supplies its container-backed `BlueprintFactory` before a controller
creates its view. It uses view configuration, so services do not become template
variables. This requires the plugin's event hooks to be enabled (the default).

If you create a view outside that lifecycle, dependency-free forms still work.
For constructor injection, pass a factory using your application's existing
`$container` when loading the helper:

```php
use Cake\View\View;
use FormCompose\Blueprint\BlueprintFactory;

$view = new View();
$view->loadHelper('Form', [
    'className' => 'FormCompose.Form',
    'blueprintFactory' => new BlueprintFactory($container),
]);
```

An explicit helper `blueprintFactory` overrides the automatic factory. To replace
the factory throughout the application, register `BlueprintFactory::class` in
`Application::services()`; the plugin preserves that registration. For a separate
discovery collection, construct it with
`new BlueprintFactory($container, collection: 'myForms')`. Configure that collection
before using it.

Blueprints are constructed independently through the container's `getNew()` method.
Missing dependencies and invalid container substitutions fail at construction.
:::

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
