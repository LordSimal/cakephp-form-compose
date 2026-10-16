---
title: Form blueprints
description: Declare reusable form fields in a PHP class, render them, and configure choices or conditional fields for the current request.
---

# Form blueprints

A blueprint is a PHP class that declares reusable form configuration. Attributes
supply static field configuration; an optional `configure()` hook changes it for
the current binding. Values, validation, and saving stay in their existing layers.

Read [Reusable forms](./definitions.md#choose-the-right-layer) to choose between
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
use FormCompose\FormBlueprint;

#[Form('article')]
#[Field('title', label: 'Article title', help: 'Displayed above the article.')]
#[Field('body', type: 'textarea')]
#[Field('published', type: 'checkbox')]
class ArticleForm extends FormBlueprint
{
}
```

```php [templates/Articles/edit.php]
<?php
use App\Form\ArticleForm;
?>
<?= $this->Form->render(ArticleForm::class, $article, submit: 'Save article') ?>
```

:::

Each `Field` adds configuration in order. Fields are not PHP properties and do
not store entity values. `Form('article')` supplies a discovery alias; after plugin
bootstrap you can pass `'article'` instead of `ArticleForm::class`. Resolving by
class works outside discovery directories and does not require a `Form` attribute.
A dependency-free class needs no additional container wiring.

## Render a blueprint {#definition-selection}

Render the complete blueprint form with the helper's `render()` method. Bind
first when you need to configure a session, or use `definition()` when the
blueprint is one section of a larger form:

::: code-group

```php [Complete form]
<?php
use App\Form\ArticleForm;
?>
<?= $this->Form->render(ArticleForm::class, $article, submit: 'Save article') ?>
```

```php [From a bound session]
<?php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, definition: ArticleForm::class);
?>
<?= $form->render(submit: 'Save article') ?>
```

```php [Inside a larger form]
<?php
use App\Form\ArticleForm;

$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->definition(ArticleForm::class) ?>
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
`$form->definition()` does too, without rerunning the hook. Passing a blueprint
explicitly to `definition()` compiles and renders it locally, preserving any
existing binding defaults. Binding alone emits no markup. Individual `control()`
calls render only their requested fields; `definition()` renders the selected
blueprint through its collection layout.

You can also [use selected defaults for individual fields](./definitions.md#select-defaults-for-the-whole-binding).
For a reusable blueprint's field arrangement, prefer its own PHP layout below.

### Form and button options {#complete-form-shortcut}

Separate form options from button options:

```php
use App\Form\ArticleForm;

echo $this->Form->render(
    ArticleForm::class,
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
use FormCompose\FormBlueprint;

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
 * @var \FormCompose\DefinitionView $form
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
<?php
use App\Form\ArticleForm;
?>
<?= $this->Form->render(ArticleForm::class, $article, submit: 'Save article') ?>
```

:::

Inside the layout, `$form` is a scoped `DefinitionView`. `control()` and `controls()`
render the blueprint's configured fields using relative paths. `has()` guards
optional fields and their wrappers if a hook removes them. It does not open or
close the form: the outer `render()` call handles that lifecycle.

This is a layout for the collection. Each `control()` still uses its own field
control template for the label, input, help, and errors. Without a selected
layout, the bundled element renders fields in declaration order without a wrapper.

The [scoped layout API](./definition-reference.md#definitionview) lists available
methods. The [presentation reference](./definition-reference.md#presentation-options)
covers per-render overrides, wrapper attributes, variants, and template data.
To embed another blueprint and position its layout, continue with
[Nested definitions](./composition.md).

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
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use function Cake\I18n\__;

#[Form('article')]
#[Field('title')]
#[Field('body', type: 'textarea')]
#[Field('status', type: 'select')]
#[Field('published', type: 'checkbox')]
class ArticleForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
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

Explicit strings in `Field` attributes are used as supplied. Translate them with
CakePHP's I18n functions through deferred text closures or `configure()`. Choose
the approach according to when the text needs to be resolved.

### Deferred labels and help in attributes

PHP 8.5 allows static closures in attribute arguments. Use a zero-argument closure
for a translated label or help text:

```php [src/Form/ArticleForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use function Cake\I18n\__;

#[Field(
    'title',
    label: static function (): string {
        return __('Article title');
    },
    help: static function (): string {
        return __('Displayed above the article.');
    },
)]
class ArticleForm extends FormBlueprint
{
}
```

The closure runs once each time that field is resolved with `field()` or through
a rendering call such as `control()` or `definition()`. `bind()` alone keeps the
closures as configuration. The resolved field stores the returned text using the
locale active at resolution; reading state or rendering its parts does not run
the closure again. Resolve a new field to obtain text for a different locale.

Per-call options replace blueprint defaults before closures run, so an overridden
closure is never evaluated. Resolving text renders no markup and registers no
submitted inputs. Keep callbacks focused on producing text.

A text closure takes no arguments and must return `string`, trusted `Html`,
`false`, or `null`. Strings are escaped normally. `false` suppresses the label or
help; an empty string renders a blank label or omits help; `null` restores the
inferred label or omits help. Returning another type raises
`InvalidArgumentException`; exceptions thrown by the closure propagate.

PHP attribute closures must use `static function`, without captured variables.
Arrow functions and direct calls such as `label: __('Article title')` are invalid
in attribute arguments. Domain/context translation functions such as `__d()` and
`__x()` can be called inside the closure body. Use `configure()` when translation
needs request data or injected services.

### Translate during configure()

The runtime hook supports ordinary PHP expressions, including translated choice
maps and labels with request-dependent arguments:

```php
// In a blueprint class; import FormBuilder, FormContext, and Cake\I18n\__.
public function configure(FormBuilder $form, FormContext $context): void
{
    $form->with('title', [
        'label' => __('Article title'),
        'help' => __('Displayed above the article.'),
    ]);
    $form->add('status', ['options' => [
        'draft' => __('Draft'),
        'published' => __('Published'),
    ]]);
}
```

These translations run when the blueprint is compiled, using the locale active
at that time. Set the request's locale before binding or rendering the blueprint.
When text deliberately needs the locale at field resolution rather than at
blueprint compilation, the hook also accepts label/help closures. Prefer arrow
functions for simple runtime callbacks:

```php
// In configure(); import Cake\I18n\__.
$form->with('title', [
    'label' => static fn(): string => __('Article title'),
    'help' => static fn(): string => __('Displayed above the article.'),
]);
```

These callbacks defer translation until field resolution, following the same
snapshot rules as attribute closures. For ordinary template calls such as
`control()`, call `__()` directly in the options. Keep the full `static function`
syntax for callbacks declared in PHP attribute arguments.

### Translate enum choices

For enum-backed choices, implement CakePHP's `EnumLabelInterface` and call
translation functions in each case's `label()` method, or use CakePHP's
`EnumLabelTrait` with its `Label` attributes. Then a field can use
`options: ['options' => Status::class]` and obtain translated labels through that
interface. See [backed enum choices](./choices.md#backed-enums) for the complete
example.

Only field `label` and `help` support deferred text closures. Use `configure()`
for ordinary translated choice maps; closures inside `options` are not evaluated
as choice labels. Validation-message translations remain in CakePHP's validation
and application layers.

## Configuration order

Inherited declarations establish defaults, then the concrete `configure()` hook
runs. Per-call field options override those defaults.
Nested option arrays are replaced as whole values. See
[configuration precedence](./definition-reference.md#configuration-order) for
inheritance, hook lifetime, and failure contracts.

All ordinary [field options](./field-options.md) are available through
`#[Field(..., options: [...])]`. The
[attribute reference](./definition-reference.md#blueprint-declarations) lists
shorthand arguments and how explicit nulls work.

## Application services and discovery

When a blueprint needs repository or application services in its constructor,
configure the helper with the application's factory. Follow
[Blueprint services and discovery](./blueprint-configuration.md) for injection,
alias discovery, custom field types, and deployment cache management.
