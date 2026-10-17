---
title: Form blueprints
description: Declare reusable form fields in a PHP class, render them, and configure choices or conditional fields for the current request.
---

# Form blueprints

A blueprint is a PHP class that declares reusable form configuration. Attributes
supply static field configuration; an optional `configure()` hook changes it for
the current binding. Values, validation, and saving stay in their existing layers.

Read [Reusable forms](./reusable-forms.md#choose-the-right-layer) to choose between
inline controls, a blueprint, and a layout. Use blueprints when
a reusable named class helps; inline controls remain a complete form-building API.

## Declare and use a blueprint

These two files render the same article fields as the introductory inline form:

::: code-group

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

#[Form('article')]
#[Field('title', label: 'Article title', help: 'Displayed above the article.')]
#[Field('body', type: 'textarea')]
#[Field('published', type: 'checkbox')]
class ArticleForm extends FormBlueprint
{
}
```

```php [templates/Articles/edit.php]
<?= $this->Form->render('article', $article, submit: 'Save article') ?>
```

:::

Each `Field` adds configuration in order. Fields are not PHP properties and do
not store entity values. `#[Form('article')]` registers the discovery alias used by
the examples after plugin bootstrap. A dependency-free class needs no additional
container wiring.

### Aliases and class references

Use the declared alias to select a discovered blueprint:

```php
$form = $this->Form->bind($article, blueprint: 'article');
```

A class reference is also supported when you prefer IDE navigation and refactoring,
or when the class lives outside discovery directories:

```php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, blueprint: ArticleForm::class);
```

Direct class selection does not require a `Form` attribute. Alias selection requires
a matching `#[Form('article')]` declaration in the configured discovery paths.
The alias and class resolve through the same compiler and constructor injection.

A **blueprint** is the class or alias the application selects. Its compiled
**definition** is a `FormDefinition` snapshot containing field and layout metadata;
values and submission state belong to the form session.

## Render a blueprint {#blueprint-selection}

Render the complete blueprint form with the helper's `render()` method. Bind
first when you need to configure a session, or use `blueprint()` when the
blueprint is one section of a larger form:

::: code-group

```php [Complete form]
<?= $this->Form->render('article', $article, submit: 'Save article') ?>
```

```php [From a bound session]
<?php
$form = $this->Form->bind($article, blueprint: 'article');
?>
<?= $form->render(submit: 'Save article') ?>
```

```php [Inside a larger form]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->blueprint('article') ?>
<?= $form->control('editor_notes', ['type' => 'textarea']) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

:::

The first two approaches render the blueprint's entire collection and selected
layout, plus the opening tag, button, and closing protection fields. The third
renders the blueprint inside an already open form and adds a separate notes field.

Selecting a blueprint at `bind()` compiles it once and installs its field defaults.
`$form->render()` uses that selected collection; inside an open session,
`$form->blueprint()` does too, without rerunning the hook. Passing a blueprint
explicitly to `blueprint()` compiles and renders it locally, preserving any
existing binding defaults. Binding alone emits no markup. Individual `control()`
calls render only their requested fields; `blueprint()` renders the selected
blueprint through its collection layout.

You can also [use selected defaults for individual fields](./reusable-forms.md#select-defaults-for-the-whole-binding).
For a reusable blueprint's field arrangement, prefer its own PHP layout below.

### Form and button options {#complete-form-shortcut}

Separate form options from button options:

```php
echo $this->Form->render(
    'article',
    $article,
    options: ['url' => ['action' => 'edit', $article->id], 'class' => 'article-form'],
    submit: 'Save article',
    submitOptions: ['class' => 'button-primary'],
);
```

Omitted `submit` uses a translated **Submit** label; `submit: false` omits the
button. For a custom theme or renderer, bind first, then call `$form->render()`.
File controls still require a multipart form. Both signatures and retry behavior
are in the [complete-form reference](./form-reference.md#render).

## Keep control of the layout {#keep-control-of-the-layout}

Select a PHP element with the `Form` attribute's `template` argument. Rendering
the blueprint now uses that element to arrange its fields:

::: code-group

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

#[Form('article', template: 'forms/article')]
#[Field('title', label: 'Article title', help: 'Displayed above the article.')]
#[Field('body', type: 'textarea')]
#[Field('published', type: 'checkbox')]
class ArticleForm extends FormBlueprint
{
}
```

```php [templates/element/forms/article.php]
<?php
/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes ?>>
    <div class="article-content">
        <?= $form->controls(['title', 'body']) ?>
    </div>
    <?php if ($form->has('published')): ?>
        <section class="publication-settings">
            <?= $form->control('published') ?>
        </section>
    <?php endif; ?>
</section>
```

```php [templates/Articles/edit.php]
<?= $this->Form->render('article', $article, submit: 'Save article') ?>
```

:::

Inside the layout, `$form` is a scoped `BlueprintView`. `control()` and `controls()`
render the blueprint's configured fields using relative paths. `has()` guards
optional fields and their wrappers if a hook removes them. It does not open or
close the form: the outer `render()` call handles that lifecycle.

This is a layout for the collection. Each `control()` still uses its own field
control template for the label, input, help, and errors. Without a selected
layout, the bundled element renders fields in declaration order without a wrapper.

The [scoped layout API](./blueprint-reference.md#blueprintview) lists available
methods. The [presentation reference](./blueprint-reference.md#presentation-options)
covers per-render overrides, wrapper attributes, variants, and template data.
To embed another blueprint and position its layout, continue with
[Nested blueprints](./composition.md).

### Inspect values and field configuration

`has()` checks membership. Resolve the field with `field()` to inspect its current
value, configured options, or effective input attributes. For example, replace the
title declaration in `ArticleForm` with this version to enable an empty-title hint:

```php
#[Field('title', label: 'Article title', templateData: ['showHint' => true])]
```

In the layout above, replace `controls(['title', 'body'])` with this fragment:

```php [templates/element/forms/article.php — title and body block]
<?php if ($form->has('title')): ?>
    <?php
    $title = $form->field('title');
    $value = $title->state->value;
    $isEmpty = $value === null || $value === '';
    $showHint = $title->state->options['templateData']['showHint'] ?? false;
    $disabled = $title->attributes()->has('disabled');

    if ($showHint && $isEmpty && !$disabled) {
        $title = $form->field('title', [
            'help' => 'Give this article a title before publishing.',
        ]);
    }
    ?>
    <?= $title->control() ?>
<?php endif; ?>
<?= $form->control('body') ?>
```

The value follows the form's configured [value-source rules](./usage.md#data-and-value-sources).
The explicit empty check preserves valid values such as zero. `state->options`
contains merged field configuration; `attributes()` includes theme defaults and
resolved input metadata too. Read a custom attribute with, for example,
`$title->attributes()->values['data-editor'] ?? null`.

Resolving the field and inspecting its state or attributes renders nothing and
registers no submitted inputs. Here `control()` renders the title once, after
inspection. Using the `help` option keeps the conditional hint associated with
its input through the normal feedback markup and ARIA attributes.
See [bound-field state](./field-reference.md#state) and
[attribute inspection](./field-reference.md#attributes) for the full APIs.

### Read request data in a layout

A layout is a CakePHP element, so `$this` is the view and provides `getRequest()`:

```php [Inside the layout element]
<?php
$request = $this->getRequest();
$submittedTitle = $request->getData('title');
$preview = $request->getQuery('preview') === '1';
$canPublish = $request->getAttribute('canPublish', false);
?>
<?php if ($preview): ?>
    <p>Preview mode</p>
<?php endif; ?>
```

`getData()` reads submitted request data directly, `getQuery()` reads query parameters, and
`getAttribute()` reads request attributes supplied by your application or
middleware. Use `state->value` when checking the value the form actually presents;
it also accounts for context values, explicit overrides, and defaults.

In an embedded layout, field paths are relative to that definition, while request
lookups still use the original request's full paths, such as `author.email`.
For conditions that add or remove fields, use the blueprint's `configure()` hook
below, where the same request is available as `$context->request`.

## Configure choices and conditional fields

This variant uses the default sequential layout and adds a status field. If you
keep the custom layout above, add a `control('status')` call there too. Assume
application middleware supplies a boolean `canPublish` request attribute after
checking the current user's permissions:

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__;

#[Form('article')]
#[Field('title')]
#[Field('body', type: 'textarea')]
#[Field('status', type: 'select')]
#[Field('published', type: 'checkbox')]
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->with('title', ['label' => __('Article title')]);
        $form->with('status', ['options' => [
            'draft' => __('Draft'),
            'review' => __('Ready for review'),
        ]]);

        if (!$context->request->getAttribute('canPublish')) {
            $form->remove('published');
        }
    }
}
```

The builder changes configuration, not the entity. `$context->context` exposes
the CakePHP context; `$context->request` is the current request. Hooks run on the
server when a blueprint is resolved. They do not create browser-side conditions.

### Remove or hide a declared field

`remove('published')` excludes that field from collection rendering. To render a
hidden field instead, use `with('published', ['type' => 'hidden'])`. Hidden values
remain user-submitted values; their presentation does not authorize changes.

Inside a [blueprint layout](#keep-control-of-the-layout),
use `$form->has('published')` to guard optional wrappers or manual parts. The outer
session can explicitly request an omitted field; when manually rendering binding
defaults, check `$form->binding->definition->has('published')` first.

## Translate a blueprint

::: info Future core integration
`FormCompose\Form\Field\Translated` will likely be replaced by a CakePHP-owned equivalent
if this functionality is added to core.
:::

Explicit strings in `Field` attributes are used as supplied. Use a `Translated`
value for declarative label/help translations. CakePHP resolves the message when
the field is resolved, using the current locale:

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Form\Field\Translated;

#[Form('article')]
#[Field(
    'title',
    label: new Translated('Article title'),
    help: new Translated('Displayed above the article.'),
)]
class ArticleForm extends FormBlueprint
{
}
```

`Translated` stores the source message and optional CakePHP formatting arguments,
domain, and translation context. It does not translate during construction:

```php
new Translated(
    'Title for {name}',
    arguments: ['name' => 'Articles'],
    domain: 'forms',
    context: 'heading',
);
```

The default domain is `default` and the default context is an empty string.
Arguments use CakePHP's message formatting, including numbered or named
placeholders. Translated text and replacement values are escaped when rendered;
a translated message never marks HTML as trusted.

### Resolution timing and overrides

`bind()` keeps message objects and closures as configuration. Each `field()`
resolution, including those performed by `control()` or `blueprint()`, merges
blueprint defaults with per-call options before resolving label/help text.
Overridden messages and closures are never evaluated. Resolving text emits no
markup and registers no submitted inputs.

The resolved field stores a text snapshot. Reading its state, rendering parts,
and recording an error-summary label all use that result. Changing the locale
after resolution does not change an existing field; resolve a new field to get
text for the new locale. A `Translated` value can be reused across resolutions
because it does not cache a locale-specific result.

### Computed text with PHP

Use ordinary PHP expressions in `configure()` when text depends on the current
request, entity context, or injected services:

```php
// In a blueprint class; import BlueprintBuilder, FormContext, and Cake\I18n\__.
public function configure(BlueprintBuilder $form, FormContext $context): void
{
    $form->with('title', [
        'label' => $context->request->getAttribute('isAdmin')
            ? __('Internal title')
            : __('Article title'),
    ]);
}
```

Label/help closures remain available for computed text. They take **no arguments**
and run once per field resolution. Return `string`, trusted `Html`, `Translated`,
`false`, or `null`. A returned `Translated` value is translated in the same
resolution. Strings are escaped normally; false suppresses the part; an empty
string renders a blank label or omits help; null restores the inferred label or
omits help. Other results raise `InvalidArgumentException`; callback exceptions
propagate. Other option bags retain their own contracts.

Prefer arrow functions for simple runtime callbacks. For example, when `$count`
is supplied by application configuration, a deferred pluralized help message can
use CakePHP's normal plural helper:

```php
// In configure(); import Cake\I18n\__n.
$form->with('title', [
    'help' => static fn(): string => __n('One attachment', '{0} attachments', $count, $count),
]);
```

PHP 8.5 also permits closures directly in attributes, using the full
`static function` syntax without captured variables:

```php
// Import Cake\I18n\__.
#[Field(
    'title',
    label: static function (): string {
        return __('Article title');
    },
)]
```

Use `new Translated('Article title')` for a static translated message. Attribute
closures are available when the text needs executable PHP. Arrow functions and
direct function calls such as `label: __('Article title')` are invalid in PHP
attribute arguments. Runtime configuration can use ordinary PHP expressions.

### Translate in runtime configuration and templates

Call CakePHP translation functions directly in `configure()` when text should be
translated during blueprint compilation:

```php
// In configure(); import Cake\I18n\__.
$form->with('title', [
    'label' => __('Article title'),
    'help' => __('Displayed above the article.'),
]);
$form->add('status', ['options' => [
    'draft' => __('Draft'),
    'published' => __('Published'),
]]);
```

These translations use the locale active when the blueprint is compiled. Set the
request's locale before binding or rendering it. For ordinary template calls such
as `control()`, call `__()` directly in the options too; a closure is unnecessary
for an immediate control call.

For enum choices, implement CakePHP's `EnumLabelInterface` and translate in
`label()`, or use `EnumLabelTrait` with CakePHP's `Label` attributes. See
[backed enum choices](./choices.md#backed-enums). `Translated` and text closures
are resolved only for field `label` and `help`, not as ordinary choice labels.
Validation messages stay in CakePHP's validation and application layers.

### Translation catalog extraction

CakePHP's standard `i18n extract` command does not currently recognize
`new Translated(...)` values. Maintain their source messages, domains, and contexts
in your translation catalogs manually. FormCompose does not provide an extraction
command.

When automatic extraction is needed, use literal messages with CakePHP's
translation functions in `configure()`, templates, or attribute closures. Those
calls remain discoverable by CakePHP's normal extractor.

## Configuration order

Inherited declarations establish defaults, then the concrete `configure()` hook
runs. Per-call field options override those defaults.
Nested option arrays are replaced as whole values. See
[configuration precedence](./blueprint-reference.md#configuration-order) for
inheritance, hook lifetime, and failure contracts.

All ordinary [field options](./field-options.md) are available through
`#[Field(..., options: [...])]`. The
[attribute reference](./blueprint-reference.md#blueprint-declarations) lists
shorthand arguments and how explicit nulls work.

## Application services and discovery

When a blueprint needs repository or application services in its constructor,
configure the helper with the application's factory. Follow
[Blueprint services and discovery](./blueprint-configuration.md) for injection,
alias discovery, custom field types, and deployment cache management.
