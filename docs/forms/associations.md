---
title: Association fields
description: Choose related records and edit associated data using CakePHP property paths.
---

# Association fields

Use CakePHP's property paths to choose related records or edit nested data.
Load the associations you want to edit before binding the entity.

## Choose related records

For an article with Authors and Tags associations, supply option lists from your
controller. These snippets belong to your existing edit action and view.

::: code-group

```php [ArticlesController::edit() — view data]
$article = $this->Articles->get($id, contain: ['Tags']);
$authors = $this->Articles->Authors->find('list');
$tags = $this->Articles->Tags->find('list');

$this->set(compact('article', 'authors', 'tags'));
```

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->control('author_id')
    ->choices($authors)
    ->placeholder('No author') ?>
<?= $form->control('tags._ids')->choices($tags) ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

:::

`author_id` is a single select. `tags._ids` is a multiple select, with the article's
loaded tags selected. Choosing author 7 and tags 2 and 5 submits:

```php
[
    'author_id' => '7',
    'tags' => ['_ids' => ['2', '5']],
];
```

Pass this data to your normal CakePHP `patchEntity()` and save workflow with the
appropriate association configuration.

### Choice discovery and explicit overrides

With conventional field and view-variable names, you can omit `choices()`:

```php
// Uses the $authors and $tags view variables set above.
echo $form->control('author_id');
echo $form->control('tags._ids');
```

An `_id` field looks for its pluralized view variable. A `._ids` selector looks
for the collection's variable. FormCompose reads those lists; it does not query
the database for them. Explicit `choices()` takes priority, including an empty
list. Use it for nonconventional names or to make the source visible at the call.

For a checkbox presentation:

```php
echo $form->control('tags._ids')
    ->type('multicheckbox')
    ->choices($tags);
```

## Edit associated records

Load `Details` and `Comments` in the controller's existing article query, then
render their property paths inside the open article form:

```php
echo $form->control('details.id');
echo $form->control('details.summary');
echo $form->control('comments.0.id');
echo $form->control('comments.0.body');
```

`comments.0.body` submits `comments[0][body]`. Associated schemas supply types,
validation constraints, and errors. Known primary keys render as hidden inputs.
Use [repeated blueprints](/repeated-embeds) when every comment needs the same set
of fields.

::: details Edit values on a join record
For editable `belongsToMany` junction data, use CakePHP's `_joinData` property.
With loaded tags whose primary key is `id`, render:

```php
echo $form->control('tags.0.id');
echo $form->control('tags.0._joinData.priority');
```

Use this form of nested record editing when editing each link's data. Use
`tags._ids` when choosing a set of related records.
:::

## Validation feedback and empty selections

An error on `tags` appears on its `tags._ids` control. Nested errors appear next
to their corresponding fields. Clearing the tags selection submits an empty
value, allowing your application to remove the old selection.

Your application still controls association accessibility, validation, and saving.
See [association conventions](/field-options#association-conventions) for custom
property names, primary keys, inferred types, and empty-value behavior.

## Reuse associated field configuration

A parent blueprint can embed a child at `author`, or repeat a child at `comments`.
Follow [blueprint composition](/composition#compose-an-association) and
[repeated embeds](/repeated-embeds) for complete declarations and views.
