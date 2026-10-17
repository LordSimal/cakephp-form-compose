---
title: Repeat embedded forms
description: Render a child blueprint for each associated row while retaining CakePHP paths, validation feedback, and form protection.
---

# Repeat embedded forms

`Embed` mounts one child blueprint. `EmbedMany` repeats a child blueprint for
multiple associated records. Each row keeps relative field paths and its own
configuration and layout, sharing the parent form's session and protection.

## Define a row and its parent

```php [src/Form/CommentForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('comment')]
#[Field('id')]
#[Field('body', type: 'textarea', label: 'Comment')]
class CommentForm extends FormBlueprint
{
}
```

```php [src/Form/PostForm.php]
<?php
declare(strict_types=1);

namespace App\Form;

use FormCompose\Attribute\EmbedMany;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('post')]
#[Field('title')]
#[EmbedMany('comments', 'comment')]
class PostForm extends FormBlueprint
{
}
```

With `$post->comments` loaded by the application, render the complete form:

```php
echo $this->Form->render('post', $post, submit: 'Save post');
```

Rows keyed `0` and `2` render fields such as `comments.0.body` and
`comments.2.body`, submitted as `comments[0][body]` and `comments[2][body]`.
Keys are preserved rather than renumbered. Primary keys inferred by CakePHP's
entity context remain hidden and locked. The plugin does not load or save rows.

## Select rows or add blank presentations

By default, keys follow the binding's `valueSources`, normally submitted data
before context. An explicitly cleared collection does not restore loaded rows.
Missing or empty collections render no rows; scalar submitted values also infer
no rows, leaving validation of that value to CakePHP.

Use `keys` to select an explicit order or include an unused key for a blank row:

```php
#[EmbedMany('comments', 'comment', keys: [0, 2, 3])]
```

An explicit empty list renders no rows. Keys can be integers or strings matching
one field-path segment; dots and bracket notation are not valid row keys.
Duplicate keys are rejected before row hooks run. Keys control presentation,
not authorization or record identity. Creating or deleting associated records
remains part of the application's normal CakePHP workflow.

## Arrange rows with a PHP element

Select an occurrence template on the attribute:

```php
#[EmbedMany(
    'comments',
    'comment',
    options: [
        'template' => 'forms/comments',
        'templateData' => ['heading' => 'Comments'],
        'attributes' => ['class' => 'comments'],
    ],
)]
```

The element receives keyed, scoped `BlueprintView` instances as `$rows` and a
`RepeatedView` as `$embed` for collection-owned errors:

```php [templates/element/forms/comments.php]
<?php
use FormCompose\Rendering\Html;
/**
 * @var array<int|string, \FormCompose\BlueprintView> $rows
 * @var \FormCompose\RepeatedView $embed
 * @var \FormCompose\Rendering\Attributes $attributes
 * @var array{heading: string} $templateData
 */
?>
<section<?= (string)$attributes ?>>
    <h2><?= Html::text($templateData['heading']) ?></h2>
    <?php foreach ($rows as $row): ?>
        <article class="comment-row">
            <?= $row->render() ?>
        </article>
    <?php endforeach; ?>
    <?= $embed->errors() ?>
</section>
```

`$row->render()` uses the child's own layout. For a table or a different field
arrangement, use `$row->control('body')` or its independently renderable parts.
Guard conditional fields with `$row->has('body')`. Extract a wrapper into an
ordinary CakePHP element when needed; there is no separate row-template layer.

The base element renders rows in order followed by direct collection errors,
without a wrapper. Wrapper `attributes` require a custom control template, selected
on the occurrence or supplied by a theme layer. The base element rejects emitted
wrapper attributes instead of discarding them or inventing markup. Null and false
attribute values are omitted and do not require a wrapper. Preserve `$attributes`
on your custom wrapper so configured IDs appear in the markup and remain subject
to duplicate-ID checks.

Theme lookup uses `embed-many/control.php` and `embed-many/errors.php`, including
normal type and variant lookup. Explicit `templates['errors']` can replace the
error element.

Collection errors are direct messages on the collection property. Nested row
error trees are excluded even when submitted data clears the collection or
replaces its keys. `errors => false` suppresses them; `partAttributes['errors']`
customizes their attributes. `$embed->hasErrors()`, `$embed->messages`,
`$embed->label()`, and `$embed->rows()` are available without rendering inputs.

## Configure and render in PHP

A parent blueprint hook can add rows and then adjust their qualified fields:

```php
public function configure(BlueprintBuilder $form, FormContext $context): void
{
    $form->embedMany('comments', 'comment', keys: [0, 3]);
    $form->with('comments.3.body', ['label' => 'New comment']);
    $form->remove('comments.0.id');
}
```

Import `FormCompose\BlueprintBuilder` and `FormCompose\FormContext` in that class.
Row hooks run once before the parent hook, including rows declared with
attributes. They can read relative values using `$context->value('body')`, which
honors binding value sources; `$context->path` exposes the absolute row mount.
The public `$context->request` remains the original request.

In a parent blueprint layout, use `<?= $form->embeddedMany('comments') ?>` to
render already compiled rows. Repeated embeds can contain ordinary embeds and
further repeated embeds. Removing a collection or row removes its subtree;
removed fields retain the existing `has()` and omission behavior.

For an ordinary open session, without a parent blueprint:

```php
echo $form->embedMany('comments', 'comment');
```

Each blueprint occurrence compiles into an independent configuration snapshot.
Rendered inputs, IDs, and
feedback IDs are committed only when the enclosing repeated layout succeeds;
a failed row or wrapper rolls back its pending registrations. Dynamic browser
add/remove controls and a repeater submission protocol are outside this API.

Rendered row errors and direct collection errors also participate in optional
[error summaries](./error-summaries.md), respecting `errors => false`.
