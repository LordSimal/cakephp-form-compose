---
title: Error summaries
description: Summarize actual rendered controls with accessible links and buffered form layouts.
---

# Error summaries

Summaries gather context validation messages from successfully rendered controls.
They complement inline feedback and do not validate or persist data.

## Enable the complete-form shortcut

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary()
    ->submit('Save article');
```

The complete renderer buffers the blueprint body before building the summary,
then places it above the body. Summaries are opt-in and emit nothing without
eligible errors. Configure a reusable summary snapshot when needed:

```php
use FormCompose\Configuration\SummaryConfig;

$summary = SummaryConfig::builder()
    ->title('Please review these fields')
    ->attributes(['class' => 'validation-summary']);

echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary($summary);
```

## Keep custom layouts and actions without manual buffering

The blueprint layout arranges fields inside `$body`. The complete form's layout
arranges the prepared `$summary`, `$body`, and buttons. Configure that layout with
`template('forms/editor')` on the form builder or provide a theme `form/layout.php`.

```php
<?php

/**
 * @var \FormCompose\Form\FormSession $form
 * @var string $body
 * @var string $summary
 * @var string|\FormCompose\Rendering\Html|false $submit
 * @var \FormCompose\Configuration\ButtonConfig $submitConfig
 */
?>
<?= $summary ?>
<div class="form-body">
    <?= $body ?>
</div>
<div class="form-actions">
    <?= $form->submit('Check server validation')->attributes(['formnovalidate' => true]) ?>
    <?php if ($submit !== false): ?>
        <?= $form->submit($submit, config: $submitConfig) ?>
    <?php endif; ?>
</div>
```

Emit body and summary once. The form is open while action buttons render; the
plugin closes it afterward. Controls added by this outer layout after summary
preparation will not appear in that summary. See [layout variables](/template-reference#complete-form-layouts).

## Place a summary manually

Rendering, rather than merely creating a builder, records feedback:

```php
$form = $this->Form->form($article)->bind();
echo $form->open();
$body = $form->controls(['title', 'body'])->render();
echo $form->errorSummary()->title('Check your article');
echo $body;
echo $form->submit('Save');
echo $form->close();
```

The explicit `render()` on the assignment is essential: `$body` must contain
rendered HTML before the summary is built. A summary below controls can be echoed
after them without buffering. Field inspection alone contributes no feedback.

## Which errors appear?

Controls contribute errors in rendering order with their resolved labels. Hidden,
disabled, empty/all-disabled choice controls do not become focus targets.
`errors(false)` suppresses inline messages and summary entries. Collection errors
from repeated embeds are unlinked; row-field errors link to their actual controls.
Composite group errors target the first eligible rendered child and do not
duplicate nested child errors. Handwritten registrations contribute unlinked
messages because registration cannot prove which focusable element was emitted.

Suppressed or blank labels omit the label prefix. Text is escaped, and summary
links target only IDs actually recorded by the session. Failed rendering rolls
back feedback along with submitted names and IDs.

## Customize the summary element

```php
echo $form->errorSummary()
    ->title('Please check your entries')
    ->template('forms/summary')
    ->attributes(['class' => 'validation-summary']);
```

The element receives `$entries`, `$title`, `$attributes`, `$form`, and
`$templateData`; see the [template reference](/template-reference#error-summary-elements).
Custom types can provide focus/identity metadata through the documented
[extension contracts](/extending#custom-summary-targets).
