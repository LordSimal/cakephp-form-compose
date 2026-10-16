---
description: Compose associated form definitions and arrange blueprint fields with ordinary PHP layout elements.
---

# Nested definitions and layouts

Use a definition layout when a reusable collection needs sections, grids, or
embedded child forms. This is different from a control template, which arranges
the label, input, help, and errors for one field.

Start with [field definitions](./definitions.md) and [blueprints](./blueprints.md).
The examples below build an author section, then embed it in a post form. An
ordinary immutable definition supports the same layout and composition features.

## Select a layout

Declare the fields and select an ordinary CakePHP element:

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

The element owns the wrapper and field placement:

```php [templates/element/forms/author.php]
<?php
/**
 * @var \FormCompose\DefinitionView $form
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<section<?= (string)$attributes ?>>
    <div class="grid grid-cols-2 gap-4">
        <?= $form->controls(['first_name', 'last_name']) ?>
    </div>
    <?php if ($form->has('email')): ?>
        <section class="contact-details">
            <?= $form->control('email') ?>
        </section>
    <?php endif; ?>
</section>
```

These classes are an optional Tailwind example. The plugin requires no CSS or
JavaScript framework. A semantic group can use an application-owned `<fieldset>`
and `<legend>` in the same way.

Render the definition inside one ordinary form session:

```php
use App\Form\AuthorForm;

$form = $this->Form->bind($author);
echo $form->open();
echo $form->definition(AuthorForm::class);
echo $form->submit('Save');
echo $form->close();
```

The layout renders the definition's contents. The calling view owns the form
lifecycle, either explicitly as above or through the complete-form shortcut:

```php
echo $this->Form->render(AuthorForm::class, $author, submit: 'Save');
```

Layout `controls()` accepts the same batch syntax as
[session controls](./usage.md#render-several-controls), including per-field option
arrays and `FieldDefinition` objects. Paths stay relative to the layout's mount;
removed fields are omitted. It adds no wrapper and participates in the enclosing
definition's input-registration transaction.

Without an explicit layout, a bundled default element renders fields and embeds
in declaration order, with no extra wrapper. Themes can override
`definition/control.php`; variants use
`definition/variants/{variant}/control.php`. Explicit layouts retain the usual
`$parent()` fallback to the theme. Render each input once: calling `$parent()`
after manually rendering the same controls triggers ordinary duplicate-ID checks.

A local override can add wrapper attributes for this rendering:

```php
echo $form->definition(AuthorForm::class, options: [
    'attributes' => ['class' => 'author-panel', 'data-section' => 'author'],
]);
```

Select another existing element with the local `template` option when needed.
The [presentation reference](./definition-reference.md#presentation-options) owns
the complete option list, replacement behavior, and layout-data scope.

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

Explicit indexed paths such as `comments.0.author` work too. The plugin does not
automatically expand collections or add a repeater lifecycle.

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
