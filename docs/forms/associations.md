---
title: Association fields
description: Render belongsTo, hasOne, hasMany, and belongsToMany fields with CakePHP association conventions and PHP templates.
---

# Association fields

FormCompose uses CakePHP's entity context to resolve associated values, schemas,
and validation metadata. The usual field paths work with both `control()` and
`field()`, including inside [reusable blueprints](/reusable-forms).

For basic select, radio, and checkbox syntax, start with [Choice controls](/choices).

::: info Your application owns association data
Load the associated records you want to edit before binding the entity. Supply
choice lists from your application; FormCompose can discover matching view
variables, but it does not query the ORM for choices or load and save associations.
:::

## Choose related records

For an article with `belongsTo('Authors')` and `belongsToMany('Tags')`, expose the
option lists in your controller:

```php [src/Controller/ArticlesController.php]
$article = $this->Articles->get($id, contain: ['Tags']);
$authors = $this->Articles->Authors->find('list');
$tags = $this->Articles->Tags->find('list');

$this->set(compact('article', 'authors', 'tags'));
```

Then render the fields without repeating conventional choice configuration:

```php [templates/Articles/edit.php]
<?php $form = $this->Form->bind($article); ?>

<?= $form->open() ?>
<?= $form->control('author_id') ?>
<?= $form->control('tags._ids') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

| Field | Default presentation | Choice variable | Submitted name |
| --- | --- | --- | --- |
| `author_id` | Single select labeled “Author” | `$authors` | `author_id` |
| `tags._ids` | Multiple select labeled “Tags” | `$tags` | `tags[_ids][]` |
| `comments.0.author_id` | Single select labeled “Author” | `$authors` | `comments[0][author_id]` |
| `comments.0.tags._ids` | Multiple select labeled “Tags” | `$tags` | `comments[0][tags][_ids][]` |

Selecting author 7 and tags 2 and 5 produces request data shaped like:

```php
[
    'author_id' => '7',
    'tags' => ['_ids' => ['2', '5']],
]
```

Your normal `patchEntity()` call applies this data according to the application's
association and accessibility configuration.

`._ids` also works with `hasMany` associations. Loaded associated entities supply
their selected identifiers through CakePHP's context, including custom single-column
primary keys such as UUIDs or string keys. Custom association properties use their
property name: `topic_tags._ids` looks for `$topicTags` and gets the label “Topic Tags”.

### Choice discovery and explicit overrides

Field names ending in `_id` use the pluralized name for their choice variable.
Collection paths ending in `._ids` use the collection property without further
pluralization. Other inferred choice fields use the pluralized final property,
following CakePHP's convention; for example `status` can use `$statuses`.

View variables must be iterable. Discovery reads the current view when resolving
each field, so lists set after `bind()` are available too. Explicit `options` wins,
including an empty array. Pass lists directly when using nonconventional foreign
key names or when you want to avoid name-based discovery:

```php
echo $form->control('tags._ids', [
    'options' => $availableTags,
    'label' => 'Choose topics',
]);
```

Here `$availableTags` is an application-supplied iterable choice list. The explicit
list replaces discovery for this field. All ordinary [field options](/field-options)
and [template overrides](/usage#choose-a-template-for-one-control) still apply.

Explicit types are preserved. An inferred `._ids` control defaults to a multiple
select; if you specify `type => 'select'` yourself, also specify `multiple => true`
when needed. `multiple => false` overrides the inferred multiple-selection default.

For a checkbox group, use the plugin's explicit field type:

```php
echo $form->control('tags._ids', ['type' => 'multicheckbox']);
```

The group still discovers `$tags`, selects the loaded IDs, and uses “Tags” as its
legend. Radio fields can also discover option lists when explicitly requested.

## Edit associated records

For an article with a `hasOne` details association and `hasMany` comments, use
property paths and numeric indexes after loading those records:

```php
// Inside the open article form, with these associations loaded.
echo $form->control('details.id');
echo $form->control('details.summary');

echo $form->control('comments.0.id');
echo $form->control('comments.0.body');
echo $form->control('comments.0.author_id');
```

The context resolves each associated table's schema, validator, and errors.
For example, `comments.0.body` can become a textarea with the comment validator's
required and maximum-length constraints. Request data takes precedence over the
loaded record by default, so submitted edits survive validation failures.

Primary keys identified by the context default to hidden inputs, including nested
keys. Their rendered values are locked when form protection is enabled. Explicit
field types can override that presentation. Associated schema metadata is available
even when a new parent has no related entity yet.

For editable `belongsToMany` junction data, use CakePHP's `_joinData` property.
For example, if the tags table uses `tag_key` as its primary key:

```php
echo $form->control('tags.0.tag_key');
echo $form->control('tags.0._joinData.priority');
```

Use nested records when editing individual links and their junction fields. Use
`tags._ids` when choosing a set of related records. CakePHP's configured
associations and marshalling options determine how the submitted data is applied.

## Validation feedback and empty selections

An error attached to the `tags` property appears on its `tags._ids` selector.
Nested collection errors similarly appear on controls such as
`comments.0.tags._ids`. The selector reads required-state metadata from that
collection property, and the normal templates associate escaped feedback with
the input through its ID and ARIA attributes.

Multiple selects and checkbox groups include a hidden empty-selection fallback.
Clearing a selection therefore submits `tags[_ids]` as an empty string instead of
leaving the field absent. Submitted empty arrays, empty strings, and null values
do not restore previously selected IDs. Explicit `value` and configured
[value-source ordering](/usage#data-and-value-sources) keep their usual precedence.

Form protection tracks the rendered nested names and hidden primary keys.
Inspecting a field registers no submission, and disabled inputs do not become
expected submitted fields. Normal CSRF and form-protection setup still belongs
to the host application.

Pass the resulting request data to CakePHP's normal `patchEntity()` workflow with
the appropriate `associated` configuration, then validate and save through the
ORM. Definitions, templates, and form sessions do not load or persist associations.

## Reuse associated field configuration

For a blueprint declared with `#[Form('author')]`, use `#[Embed('author', 'author')]`
to compose it, or render it with `$form->blueprint('author', at: 'author')`.
The child keeps its own PHP layout and relative field names. See
[nested blueprints and layouts](/composition#compose-an-association).

Use [repeated embeds](/repeated-embeds) to render a reusable child blueprint
for every row of a loaded or submitted association.
