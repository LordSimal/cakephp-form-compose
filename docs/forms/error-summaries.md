---
title: Error summaries
description: Put linked validation errors above your form and customize its summary heading.
---

# Error summaries

A summary puts the form's validation messages in one place, with links to the
inputs. It complements the errors shown beside each field.

## Add a summary above the fields

Choose the version matching your form. The blueprint tab assumes the
[article blueprint](/blueprints#declare-and-use-a-blueprint) is already defined.

::: code-group

```php [Manual form]
<?php
$form = $this->Form->form($article)->bind();
?>
<?= $form->open() ?>
<?php $body = $form->controls(['title', 'body'])->render(); ?>
<?= $form->errorSummary()->title('Check your article') ?>
<?= $body ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

```php [Blueprint form]
<?php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary()
    ->submit('Save article');
```

:::

The manual form renders the fields into `$body` first so the summary knows their
errors, then outputs the summary before that HTML. This is why the assignment
uses `render()`. The blueprint form handles that ordering automatically.
With no eligible errors, the summary emits nothing.

## Change the heading

The manual example sets its heading directly. For a complete blueprint form,
pass a summary config:

```php
use FormCompose\Configuration\SummaryConfig;

echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary(
        SummaryConfig::builder()
            ->title('Please review these fields')
            ->attributes(['class' => 'validation-summary']),
    )
    ->submit('Save article');
```

## Keep custom layouts and actions without manual buffering

To position the prepared summary, body, and buttons yourself, select a complete-form
layout. The [form-layout example](/form-reference#application-owned-complete-form-layouts)
shows the builder call and corresponding PHP element. A blueprint's own layout
continues to arrange its fields inside the body.

## Place a summary manually

A summary can also appear below the fields. Render it after their `control()`
calls; buffering is only needed when you want the summary above them.

## Which errors appear?

Only rendered fields contribute errors. Their labels become the summary's link
text, and `errors(false)` suppresses both inline errors and the summary entry.
Hidden or disabled fields do not become focus targets.

See [summary behavior](/form-reference#summary-entries) for collection, composite,
and handwritten-input rules.

## Customize the summary element

Select your own summary template when its markup needs to change. The
[summary template example](/template-reference#error-summary-elements) shows the
call and complete element, including the supplied IDs and attributes.
