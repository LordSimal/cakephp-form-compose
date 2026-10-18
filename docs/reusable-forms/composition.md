---
title: Blueprint composition
description: Combine reusable field collections in one form or nest them at association paths with scoped layouts.
---

# Blueprint composition

Combine blueprints at the form level when their fields belong to the same data
context. Embed a child when its fields belong to an association.

## Combine blueprints in one form {#combine-blueprints}

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->blueprint('article_more')
    ->errorSummary()
    ->submit('Save article');
```

Each `blueprint()` call appends a selection. The complete form renders the
blueprints in that order, retaining each blueprint's optional layout. All share
one form tag, binding context, error summary, submit action, and submission
protection. Set `template('forms/editor')` on the form builder when the complete
form needs its own [outer layout](/form-reference#application-owned-complete-form-layouts).

Binding compiles every selected occurrence once. Their combined field defaults
also apply when you use `bind()` and arrange individual controls yourself.
Inside that open session, `$form->blueprint()` renders all selected blueprints;
`$form->blueprint('other')` renders only that local occurrence and leaves the
binding's selection unchanged.

The selections must declare distinct field and embed paths. Collisions,
including selecting the same nonempty blueprint twice, fail during binding
before the form opens. Use nested embeds for independent copies under different
association paths. If a later layout fails during `$form->blueprint()`, input
and feedback registrations from the entire composition roll back together.

On a form builder or immutable `FormConfig`, `blueprint(null)` clears the
selection before binding. A captured session retains its original selection.

## Nest a child blueprint

Embed a child blueprint when a related collection has its own reusable settings
or layout. A child shares its enclosing form session and submission protection.

## Define a reusable child {#select-a-layout}

```php
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__;

#[Form('author')]
class AuthorForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('name')->label(__('Author name'));
        $form->add('email')->type('email');
        $form->template('forms/author');
    }
}
```

```php
<?php

/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes ?>>
    <?= $form->controls(['name', 'email']) ?>
</section>
```

Save the layout as `templates/element/forms/author.php`.

## Compose an association

In the parent article blueprint's hook:

```php
$form->add('title');
$form->embed('author', 'author')->attributes(['class' => 'article-author']);
```

The first argument is the context mount; the second is the child's declared
alias. `AuthorForm::class` is an explicit alternative. The child uses relative
paths such as `email`; its input submits `author[email]`. No child opens a form.

In a parent layout, position the occurrence with:

```php
<?= $form->control('title') ?>
<?= $form->embedded('author') ?>
```

## Conditional fields and parent overrides

```php
$form->embed('author', 'author');
$form->field('author.email')->label(__('Contact email'));

if (!$context->request->getAttribute('canEditAuthorName')) {
    $form->remove('author.name');
}
```

These are parent-hook statements. Qualified edits apply after child compilation,
so the child must declare the target. Misspellings fail during compilation.
For optional parts in a layout, check `has()` before resolving a field. A removed
control emits nothing, but removing it does not remove application authorization
requirements.

## Build compositions in PHP

Use `embed()` in hooks for reusable structure. For an occurrence placed directly
in a view, render a blueprint inside an open session:

```php
echo $form->blueprint('author', at: 'author')->attributes(['class' => 'article-author']);
```

This local occurrence does not replace the binding's selected defaults. Do not
render it twice at the same input IDs. Repeated collections use
[`embedMany()`](/repeated-embeds).

## Isolation and failures

Each occurrence has its own hook/context scope. Parent hooks run before child
compilation; no request-dependent compiled tree enters discovery caches.
Cycles and duplicate field/embed identities fail. A failed layout rolls back
input registrations, preserving the enclosing session for a corrected retry.

## Select a template for one embed

```php
// Parent blueprint hook.
$form->embed('author', 'author')->template('forms/compact-author');
```

Or override presentation in the parent layout:

```php
<?= $form->embedded('author')->template('forms/compact-author') ?>
```

Presentation changes do not rerun the child's hook. Use the common
`variant()`, `templateData()`, and `partAttributes()` methods as needed.
