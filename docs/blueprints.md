---
description: Declare reusable form fields in a PHP class, render them, and configure choices or conditional fields for the current request.
---

# Form blueprints

A blueprint is a PHP class that compiles into a `FormDefinition`. Attributes
supply static field configuration; an optional `configure()` hook changes it for
the current binding. Values, validation, and saving stay in their existing layers.

Read [Field definitions](./definitions.md#choose-the-right-layer) for the distinction
between a field definition, collection, blueprint, and layout. Use blueprints when
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

## Definition selection

Choose the entry point according to what should happen:

| Call | Effect |
| --- | --- |
| `$this->Form->render(ArticleForm::class, $article)` | Render a complete form with its default submit button. |
| `$form->definition(ArticleForm::class)` | Compile and render this collection inside an open session. No binding defaults are installed. |
| `$this->Form->bind($article, definition: ArticleForm::class)` | Compile once and install field defaults; render individual fields or the collection later. |
| `$form->definition()` | Render the already selected collection without running its hook again. |

For example, use binding defaults while keeping the view's structure:

```php
use App\Form\ArticleForm;

$form = $this->Form->bind($article, definition: ArticleForm::class);
echo $form->open();
echo $form->control('title');
echo '<section class="article-content">';
echo $form->control('body');
echo '</section>';
echo $form->control('published');
echo $form->submit('Save article');
echo $form->close();
```

Individual calls can override options, such as
`control('title', ['label' => 'Working title'])`. A selected blueprint supplies
defaults, not a restriction on which fields the outer session may request.

## Complete-form shortcut

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

## Configure choices and conditional fields

Replace the earlier class with this variant when configuration depends on the
binding. Assume application middleware supplies a boolean `canPublish` request
attribute after checking the current user's permissions:

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

Inside a [definition layout](./composition.md#conditional-fields-and-parent-overrides),
use `$form->has('published')` to guard optional wrappers or manual parts. The outer
session can explicitly request an omitted field; when manually rendering binding
defaults, check `$form->binding->definition->has('published')` first.

## Keep control of the layout

Use `#[Form('article', template: 'forms/article')]` to select a PHP element that
arranges the collection. Blueprints describe configuration; the element places
fields, wrappers, and embedded children. Continue with
[Nested definitions and layouts](./composition.md) for connected class/element
examples and composition of associated forms.

## Configuration order

Inherited declarations establish defaults, then the concrete `configure()` hook
runs. Explicit field definitions and per-call options override those defaults.
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
