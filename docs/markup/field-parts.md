---
title: Compose field parts
description: Place a field's label, input, help, and errors independently in your PHP view.
---

# Compose field parts

Use `control()` for a complete field. When one view needs a different arrangement,
resolve a field and place its parts yourself.

## Arrange a field in the view

`templates/Articles/edit.php`

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
$title = $form->field('title')
    ->help('Displayed above the article.')
    ->resolve();
?>
<?= $form->open() ?>
<section class="article-title">
    <?= $title->label()->attributes(['class' => 'title-label']) ?>
    <div class="title-input-shell">
        <?= $title->input()->attributes(['class' => 'title-input']) ?>
    </div>
    <?= $title->help() ?>
    <?= $title->errors() ?>
</section>
<?= $form->control('body')->type('textarea') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

`field()` configures the field; `resolve()` captures its value, name, ID, and
feedback as a bound field. Its parts render when echoed. Configure the field
before resolving, and use each part's fluent methods for local presentation.

To reuse this arrangement across screens, move it into a [PHP element](/templates).

## Inspect state without rendering

For a conditional hint or other view logic, read the resolved state:

```php
$title = $form->field('title')->resolve();
$currentTitle = $title->state->value;
$messages = $title->state->messages;
```

This produces no HTML. The [bound-field reference](/field-reference) lists the
available state and part methods.

## Preserve feedback and accessible names

Render the enabled help and errors parts along with the input. Their generated
IDs are used by its `aria-describedby` attribute. To omit a part intentionally,
configure `help(false)` or `errors(false)` on the field before `resolve()`.

If hiding the label, provide another accessible name, for example
`attributes(['aria-label' => 'Article title'])`. For radios and checkbox groups,
keep the fieldset/legend structure used by the default control template.

::: tip Render the input once
Choose either `control()` or individual parts for a field. Rendering both the
whole control and its input repeats the input and its ID.
:::

## Write a native input by hand

Use `input()` for normal inputs: it handles formatted values, choice fallbacks,
and submission registration. If you must write the input tag yourself, follow
the [handwritten input and registration example](/field-reference#register).
Handwritten help/errors also need [feedback registration](/form-reference#register-feedback).
These are advanced alternatives to the parts shown above.
