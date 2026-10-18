---
title: Blueprint composition
description: Combine independent form sections or embed a reusable child at an association path.
---

# Blueprint composition

Combine blueprints when two reusable field sets belong to the same record. Embed
a child when its fields belong under a related property such as `author`.

## Combine blueprints in one form {#combine-blueprints}

Starting with the [article blueprint](/blueprints), add a second blueprint for
extra details, then select both in your view:

::: code-group

```php [src/Form/ArticleDetailsForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

#[Form('article_more')]
class ArticleDetailsForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('summary')->type('textarea');
    }
}
```

```php [templates/Articles/edit.php]
<?php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->blueprint('article_more')
    ->submit('Save article');
```

:::

This renders the article fields followed by the summary, with one form tag and
one submit button. Each blueprint keeps its own layout if it has one.
Their field paths must be distinct; declaring `title` in both would fail at binding.

## Nest a child blueprint

Use `embed()` for related data. Create the reusable author below, then add the
embed to your existing `ArticleForm::configure()` hook.

::: code-group

```php [src/Form/AuthorForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

#[Form('author')]
class AuthorForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('name');
        $form->add('email')->type('email');
    }
}
```

```php [Add to ArticleForm::configure()]
$form->embed('author', 'author');
```

:::

## Compose an association

In `embed('author', 'author')`, the first argument is the property path; the
second is the child's alias. Load the article's Author association before
rendering the article form. The child's `email` then submits `author[email]`.
The child shares its parent's form rather than opening another one.

The article blueprint already selects its own layout. Add the child to
`templates/element/forms/article.php` where it should appear:

```php
<?= $form->control('title') ?>
<?= $form->control('body') ?>
<?= $form->control('published') ?>
<?= $form->embedded('author') ?>
```

## Define a reusable child layout {#select-a-layout}

For a shared author section, add the first snippet to `AuthorForm::configure()`
and create the element in the second tab:

::: code-group

```php [Add to AuthorForm::configure()]
$form->template('forms/author');
```

```php [templates/element/forms/author.php]
<?php
declare(strict_types=1);

/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= $attributes->merge(['class' => 'article-author']) ?>>
    <h2>Author</h2>
    <?= $form->controls(['name', 'email']) ?>
</section>
```

:::

The layout uses names relative to the author, without repeating `author.`.

## Conditional fields and parent overrides

After declaring the embed in the parent hook, change a child field with its
qualified path:

```php
$form->field('author.email')->label('Contact email');
```

Use `remove('author.name')` to omit a declared child field. Custom layouts can
check `has('name')` before displaying its wrapper.

## Select a template for one embed

To override the child layout for one use, select a template on its declaration:

```php
$form->embed('author', 'author')->template('forms/compact-author');
```

Create that element using the same scoped layout API shown above. This replaces
the `embed()` declaration; do not declare the same path twice.

<span id="build-compositions-in-php"></span>
<span id="isolation-and-failures"></span>

For local occurrences inside an open session, ID rules, and collision handling,
see [blueprint rendering](/form-reference#blueprint) and the
[blueprint reference](/blueprint-reference#configuration-order).
