---
title: Fields and values
description: Configure fluent controls, source values, validation feedback, uploads, and search forms.
---

# Fields and values

Examples using `$form` assume an open session. Start with [your first form](/getting-started)
for setup and [field configuration](/field-options) for every method.

## Complete controls

```php
echo $form->control('title')->label('Article title');
echo $form->control('body')
    ->type('textarea')
    ->attributes(['rows' => 12]);
echo $form->control('published')->type('checkbox');
```

Schema and path conventions infer native types unless overridden. See
[type inference](/field-options#type-inference). A control uses a PHP element to
arrange its label, input, help, and errors.

### Render several controls

```php
echo $form->controls([
    'title',
    $form->control('body')
        ->type('textarea')
        ->attributes(['rows' => 12]),
    $form->control('published')->type('checkbox'),
]);
```

The batch has no wrapper and registers all inputs in one transaction. Use
application HTML around separate calls for sections and grids.

### Opening and closing

Open a bound session once, render inputs, then close once. Rendered IDs must be
unique. Field resolution and inspection can happen before opening; input rendering
requires an open form. See [lifecycle and failures](/form-reference#lifecycle-and-failures).

## Data and value sources

`$this->Form->form($data)` accepts ORM entities, CakePHP `Form` objects, array
contexts, `ContextInterface`, or null. It leaves those objects unchanged.
`bind()` captures configuration and creates the isolated form session.

Default value lookup is explicit `value()`, submitted request data, context, then
`default()`. Explicit null, false, zero, and empty string are preserved. A submitted
null counts as found; a context null falls through, following CakePHP semantics.

```php
echo $form->control('title')->value('Preview title');
echo $form->control('summary')
    ->default('No summary yet')
    ->schemaDefault(false);
```

Use `value()` for deliberate overrides; ordinary edit fields should use binding.
Configure source order before binding, using `valueSources()`.

### Search forms

```php
<?php
$form = $this->Form
    ->form()
    ->method('get')
    ->url(['action' => 'index'])
    ->valueSources(['query', 'context'])
    ->bind();
?>
<?= $form->open() ?>
<?= $form->control('q')
    ->type('search')
    ->label('Search articles') ?>
<?= $form->submit('Search') ?>
<?= $form->close() ?>
```

### Upload files

```php
<?php
$form = $this->Form
    ->form($article)
    ->multipart()
    ->bind();
?>
<?= $form->open() ?>
<?= $form->control('attachment')
    ->type('file')
    ->attributes(['accept' => 'application/pdf']) ?>
<?= $form->submit('Upload') ?>
<?= $form->close() ?>
```

`multipart()` changes encoding while preserving the context's HTTP method.
A file input in a non-multipart form throws. Use field `multiple()` for several
files; submitted names gain `[]`. File and password values never prefill.

## Labels, feedback, and escaping

```php
echo $form->control('title')
    ->label('Article title')
    ->help('Visible in article lists.')
    ->partAttributes('container', ['class' => 'article-field'])
    ->partAttributes('label', ['class' => 'article-label']);
```

Text and attributes are escaped. Use `label(false)`, `help(false)`, or
`errors(false)` to suppress a part. Supply another accessible name when hiding a
label. Render enabled help/errors so generated ARIA references have targets.
Trusted markup requires an explicit [`Html` object](/template-reference#trusted-markup).
Validation, authorization, and persistence remain in the application.

## Choose a template for one control

```php
echo $form->control('title')->template('forms/article-title');
```

This selects `templates/element/forms/article-title.php`. See
[templates](/templates), [themes](/themes), and [variants](/variants).

### Pass data to a template

`templateData(['icon' => 'mail'])` supplies application-owned variables in
`$templateData`, separate from HTML attributes and field extension data.
See [template data](/template-reference#template-data) for scope and merge rules.

## Association fields

Use paths such as `author_id`, `tags._ids`, and `comments.0.body`. See
[associations](/associations) for discovery and nested errors, and
[choices](/choices) for selects and checkbox groups.

## Composing field parts

Configure a field builder, then call `resolve()` for an immutable `BoundField`.
Render its parts with fluent local presentation:

```php
$title = $form->field('title')
    ->help('Use a descriptive title.')
    ->resolve();
echo $title->input()->attributes(['class' => 'title-input']);
```

See [field parts](/field-parts).

## Reusable forms

Share settings with an immutable `FieldConfig`, or extract a [blueprint](/blueprints)
when a collection repeats. Both use the same bound fields and PHP templates.
