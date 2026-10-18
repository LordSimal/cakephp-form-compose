---
title: Fields and values
description: Render fields, keep submitted values, and configure labels, defaults, and HTML attributes.
---

# Fields and values

Bind the record you are editing, then render a control for each property. This
example assumes your controller passes an `$article` entity to the view.

## Render fields

`templates/Articles/edit.php`

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('body')->type('textarea') ?>
<?= $form->control('published')->type('checkbox') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

Each control includes a label, input, and any help or validation errors. The
entity supplies values and validation metadata. You can leave out `type()` when
the schema already describes the control you want: a boolean becomes a checkbox,
for example.

## Set labels and HTML attributes

Replace the title control inside the open form with:

```php
echo $form->control('title')
    ->label('Article title')
    ->help('Shown at the top of the article.')
    ->attributes(['maxlength' => 120, 'class' => 'title-input']);
```

`attributes()` applies to the input. Text and attribute values are escaped.
For label, help, error, or outer-wrapper attributes, see
[attribute targets](/field-options#attributes).

## Where does the displayed value come from? {#data-and-value-sources}

For an ordinary edit form, you do not need to set values yourself:

| Situation | What appears in the title input |
| --- | --- |
| Opening an existing article | The article's current title. |
| Redisplaying a failed submission | The submitted title, including an empty string. |

Keep your existing CakePHP submission handler. When validation fails, render the
form again with the entity containing its errors. The user's attempted values
remain visible, and controls show the validation messages.

A **value source** is somewhere the form looks for a field's value. The defaults
check submitted request data (`data`), then the entity or other bound object
(`context`). You normally leave this alone. A [search form](/search-forms) uses
URL parameters (`query`) instead.

## Supply a default

For a new article, suggest a summary when binding has no summary to display:

```php
echo $form->control('summary')->default('Read the full article below.');
```

A default does not replace a submitted empty string. If your table has a schema
default that should be ignored for this field, add `schemaDefault(false)`.

Use `value()` when you deliberately want to replace the bound value:

```php
echo $form->control('title')->value('Preview title');
```

This always displays “Preview title”, even after a submission. Use it for an
intentional override, rather than ordinary edit fields. See the
[value reference](/field-options#values) for exact lookup and formatting rules.

## Render several controls

When no extra HTML is needed between fields:

```php
echo $form->controls([
    'title',
    $form->control('body')->type('textarea'),
    $form->control('published')->type('checkbox'),
]);
```

The batch adds no wrapper. Separate `control()` calls work better when arranging
fields in sections or a grid.

## Choose a template for one control

Use `template('forms/email')` to select your own PHP element. The
[template guide](/templates) shows the call together with the element it renders.
For a one-off arrangement in the view, [render field parts](/field-parts) directly.

## Next steps

- [Choice controls](/choices) for selects, radios, and checkboxes.
- [Search forms](/search-forms) for values from the URL.
- [File uploads](/file-uploads) for multipart forms.
- [Association fields](/associations) for paths such as `tags._ids` and `comments.0.body`.
- [Field configuration](/field-options) for every setter.
