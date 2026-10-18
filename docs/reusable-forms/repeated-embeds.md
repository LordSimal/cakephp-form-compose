---
title: Repeated embeds
description: Compile and render keyed association rows using fluent blueprint declarations.
---

# Repeated embeds

A repeated embed compiles one child occurrence per selected key. It preserves
original collection keys and nested submission names while sharing one form.

## Define a row and its parent

```php
<?php
declare(strict_types=1);

namespace App\Form;

use Attribute\Form;
use Blueprint\BlueprintBuilder;
use Blueprint\FormBlueprint;
use Blueprint\FormContext;
use FormComposeBlueprintBuilder;
use FormComposeForm;
use FormComposeFormBlueprint;
use FormComposeFormContext;

#[Form('comment')]
class CommentForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('id')->type('hidden');
        $form->add('body')->type('textarea');
    }
}
```

In the parent article blueprint's hook:

```php
$form->add('title');
$form->embedMany('comments', 'comment')->label('Comments');
```

Null keys infer rows from the configured binding value sources. A submitted
collection, including an explicit empty collection, is authoritative over entity
rows. Each row hook sees its own scoped context; paths submit as
`comments[2][body]`, for example.

## Select rows or add blank presentations

```php
// Parent blueprint hook: explicit order, preserving sparse keys.
$form->embedMany('comments', 'comment')->keys([2, 7, 'new_row']);
```

`keys([])` compiles no rows. Keys must be unique valid path segments. Selecting
keys happens before row hooks run; excluded rows are not compiled. A blank row
only supplies a presentation—it does not create or save an entity.

## Arrange rows with a PHP element

Select a collection layout in the parent hook:

```php
$form->embedMany('comments', 'comment')->template('forms/comments');
```

```php
<?php

use FormComposeHtml;
use Rendering\Html;

/** @var \FormCompose\Blueprint\RepeatedView $embed */
/** @var array<int|string, \FormCompose\Blueprint\BlueprintView> $rows */
/** @var \FormCompose\Rendering\Attributes $attributes */
?>
<section<?= (string)$attributes ?>>
    <h2><?= Html::text($embed->label()) ?></h2>
    <?= $embed->errors() ?>
    <?php foreach ($rows as $key => $row): ?>
        <article class="comment-row">
            <?= $row->render() ?>
        </article>
    <?php endforeach; ?>
</section>
```

Save this as `templates/element/forms/comments.php`. `$row` is a scoped layout
view. Use `$row->controls(['id', 'body'])` instead of `render()` when arranging row
fields manually. Do not render both. Collection errors belong to `$embed`;
row errors remain on their bound fields.

## Configure and render in PHP

Inside an open session, a new repeated occurrence can be configured directly:

```php
echo $form->embedMany('comments', 'comment')->keys([2, 7])->label('Comments');
```

Inside a blueprint layout, render the already compiled occurrence:

```php
<?= $form->embeddedMany('comments')->attributes(['class' => 'comments']) ?>
```

Layout presentation does not recompile rows. Configure keys in the declaration
before binding; selecting different keys on a compiled layout occurrence fails.
Use `errors(false)` to suppress collection-level errors and `partAttributes('errors', ...)`
for their attributes. Custom collection layouts must render enabled feedback.
