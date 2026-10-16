---
title: Nested definitions
description: Embed reusable child blueprints at association paths and compose their layouts within one form.
---

# Nested definitions

Use embedded definitions when a form includes a reusable child section, such as
an author inside a post. Each child keeps relative field paths and its own layout,
while sharing the enclosing form's session and submission protection.

For arranging the fields of one blueprint, start with
[blueprint layouts](./blueprints.md#keep-control-of-the-layout). This page focuses
on mounting child definitions and rendering their layouts together.

## Define a reusable child {#select-a-layout}

This child declares paths relative to an author. It can render on its own or be
embedded under a parent association:

```php [src/Form/AuthorForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('author', template: 'forms/author')]
#[Field('first_name', label: 'First name')]
#[Field('last_name', label: 'Last name')]
#[Field('email', type: 'email')]
class AuthorForm extends FormBlueprint
{
}
```

Its layout only arranges the child fields:

```php [templates/element/forms/author.php]
<?php
/**
 * @var \FormCompose\DefinitionView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes ?>>
    <?= $form->controls(['first_name', 'last_name']) ?>
    <?php if ($form->has('email')): ?>
        <section class="contact-details">
            <?= $form->control('email') ?>
        </section>
    <?php endif; ?>
</section>
```

## Compose an association

Mount the author definition inside a post definition:

```php [src/Form/PostForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('post', template: 'forms/post')]
#[Field('title')]
#[Embed('author', AuthorForm::class)]
#[Field('body', type: 'textarea')]
class PostForm extends FormBlueprint
{
}
```

The parent layout chooses where to render its compiled child:

```php [templates/element/forms/post.php]
<?php /** @var \FormCompose\DefinitionView $form */ ?>
<?= $form->control('title') ?>
<?= $form->embedded('author') ?>
<?= $form->control('body') ?>
```

Render the parent as one complete form, with `$post` supplied by your controller
and its author association loaded:

```php [templates/Posts/edit.php]
<?php
use App\Form\PostForm;
?>
<?= $this->Form->render(PostForm::class, $post, submit: 'Save post') ?>
```

`embedded('author')` renders the already configured child using its selected
layout. It preserves changes made by the parent's hook and does not run the
child's hook again. Calling `definition(AuthorForm::class, at: 'author')` on the
outer session instead resolves a new, independent occurrence.

When binding `$post`, mount its association at `author`. The child's relative
`email` becomes `author.email`, producing `author[email]` and a qualified input ID.
Use `post.author` only when the bound root actually has a `post` property.
Embeds can contain further embeds, and both class names and discovered aliases
are accepted.

For a standalone section, mount the same blueprint directly:

```php
echo $form->definition(AuthorForm::class, at: 'author');
```

Explicit indexed paths such as `comments.0.author` work too. For multiple rows, use [EmbedMany](./repeated-embeds.md), which expands row keys
without introducing a browser repeater lifecycle.

## Conditional fields and parent overrides

Children resolve before their parent's `configure()` hook. The parent can adjust
or remove qualified fields:

```php
public function configure(FormBuilder $form, FormContext $context): void
{
    $form->with('author.email', ['label' => 'Contact email']);

    if (!$context->request->getAttribute('canEditAuthorEmail')) {
        $form->remove('author.email');
    }
}
```

Import `FormCompose\FormBuilder` and `FormCompose\FormContext` in that class.
`remove('author')` removes the entire embedded subtree.

The element receives a scoped `DefinitionView` as `$form`. Its paths are relative
to this collection, and it can only render configured fields or embedded children:

```php
<!-- In templates/element/forms/author.php, replacing the email block: -->
<?php if ($form->has('email')): ?>
    <?php $email = $form->field('email'); ?>
    <section class="contact-details">
        <?= $email->label() ?>
        <?= $email->input() ?>
        <?= $email->help() ?>
        <?= $email->errors() ?>
    </section>
<?php endif; ?>
```

See the [scoped layout API](./definition-reference.md#definitionview) for all
methods and how removed fields differ from unknown paths.

Unknown names raise `InvalidArgumentException`, so typos are visible. Guard
optional field parts and static wrappers with `has()`. Removing a field does not
automatically remove static markup written around it. A field with type `hidden`
still renders and participates in submission; removing a field omits it.

Layout access is limited to the compiled definition. The outer `FormSession`
continues to support ordinary `field()` and `control()` calls for additional
fields. Selecting a blueprint at `bind()` supplies defaults but does not render
its layout. Calls on the outer session can still explicitly request fields that
a blueprint removed; omission is a presentation decision, not authorization.

An embedded hook uses relative context lookups, such as
`$context->context->val('email')`. Values, type information, requirements, and errors
are delegated to the original Cake context at the mount path. The request remains
the original request. Form-wide `isCreate()` and primary-key metadata remain those
of the root binding; `fieldNames()` lists loaded child fields and is empty for an
unloaded child.

## Build compositions in PHP

The builder can resolve an alias or class with the same container and scoped
context as attribute embeds:

```php
public function configure(FormBuilder $form, FormContext $context): void
{
    $form->embed('author', AuthorForm::class);
    $form->embed('comments.0.author', 'author');
}
```

This is an alternative to declaring those same mounts with attributes, so do not
declare them twice. String resolution is available on builders supplied by
`BlueprintFactory`; a standalone builder can embed an already built definition.
Use `$form->presentation(['template' => 'forms/post'])` when the hook should select
a layout; that element must arrange the fields and embeds in the resulting definition.

Ordinary immutable definitions support the same composition and presentation:

```php
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;

$author = FormDefinition::make()
    ->add(FieldDefinition::make('first_name'))
    ->add(FieldDefinition::make('last_name'))
    ->add(FieldDefinition::make('email', 'email'))
    ->presentation(['template' => 'forms/author']);

$post = FormDefinition::make()
    ->add(FieldDefinition::make('title'))
    ->embed('author', $author)
    ->add(FieldDefinition::make('body', 'textarea'))
    ->presentation(['template' => 'forms/post']);

echo $form->definition($post);
```

`$post->fields` remains the flattened, ordered list of fully qualified fields.
`$post->entries` retains fields and embeds for rendering.
`$post->embedded('author')` retrieves the relative compiled child.
`with('author.email', ...)` and `remove('author.email')` return new definitions
and update both representations.

## Isolation and failures

A child is compiled once for each occurrence and keeps its own relative layout.
Children resolve before the parent's hook, allowing parent overrides such as
`with('author.email', ...)`. Circular embeddings, duplicate paths, unknown
references, and incompatible field/embed declarations are errors.

All layouts share the enclosing form's lifecycle and submission registry.
Membership checks and configuration register nothing. If a layout fails, its
staged input registrations are rolled back; previously rendered controls remain
registered. See [lifecycle and failures](./form-reference.md#lifecycle-and-failures).

CakePHP owns association marshalling, validation, and persistence. Embedding a
form does not load associations or authorize submitted changes.

## Select a template for one embed

The normal `Embed` attribute accepts occurrence presentation options too:

```php
#[Embed('author', AuthorForm::class, options: [
    'template' => 'forms/post-author',
    'templateData' => ['heading' => 'Written by'],
])]
```

The selected element receives the scoped child as `$form`. It can call
`$form->render()` to wrap the child's own layout, or arrange its fields directly.
Occurrence options apply only at this mount; the child definition keeps its own
presentation. Options passed to `embedded('author', [...])` override those declared
on the attribute. `template`, `templates`, `templateData`, `variant`, and `attributes`
use the ordinary definition presentation contract.
