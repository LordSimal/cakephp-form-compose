---
title: Your first form
description: Install FormCompose and build an article editing form with values, validation feedback, and PHP templates.
---

# Your first form

This walkthrough assumes you already use CakePHP controllers, entities, and view
templates. You will render an article editing form, add field options, and see
how a control connects to an application-owned PHP element.

For the motivation and a side-by-side markup example, read
[Why FormCompose?](./why.md). For a method or option lookup, use the
[API reference](./form-reference.md).

## Requirements

| Dependency | Supported version |
| --- | --- |
| PHP | 8.5+ |
| CakePHP | 6 development builds |

::: info Experimental API
FormCompose provides its own form-building API. When adopting it, port existing
core FormHelper calls and widgets to this API. It is not a drop-in replacement
for every core method or option.
:::

## Install the plugin

The host application's Composer configuration must allow development dependencies
with `"minimum-stability": "dev"` and `"prefer-stable": true` for the current
CakePHP 6 dependency. CakePHP 5.4 is no longer supported.

```sh
composer require josbeir/cakephp-form-compose:dev-main
```

Register the plugin with either approach:

::: code-group

```sh [Cake CLI]
bin/cake plugin load FormCompose
```

```php [src/Application.php — inside bootstrap()]
use FormCompose\FormComposePlugin;

// After parent::bootstrap().
$this->addPlugin(FormComposePlugin::class);
```

:::

The CLI adds the plugin to `config/plugins.php`.

## Load the helper

Configure the helper in `AppView::initialize()`:

::: code-group

```php [Use the Form alias]
$this->addHelper('Form', ['className' => 'FormCompose.Form']);
```

```php [Keep core FormHelper alongside it]
$this->addHelper('Form');
$this->addHelper('Compose', ['className' => 'FormCompose.Form']);
// Use $this->Compose->bind(...) in templates.
```

:::

All documentation examples use the `Form` alias.

## Build your first form

Assume your existing controller passes an `$article` entity to the view. Keep
record loading, authorization, `patchEntity()`, validation, and saving in that
controller or your existing application services.

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->bind($article);
?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('body') ?>
<?= $form->control('published') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

1. `bind()` creates an isolated form session using the entity's CakePHP context.
2. `open()` renders the opening tag and initial method/CSRF inputs.
3. `control()` renders a complete field: label, input, help, and errors.
4. `submit()` renders a button. `close()` adds final protection fields and closes
   the form.

The context supplies current values, field types, and validation metadata. An
existing entity defaults to PUT, submitted through POST with a method override;
a new entity defaults to POST. The form submits to the current request URL.
The entity schema supplies control types, such as a textarea for `body` and a
checkbox for a boolean `published` field.

Configure CakePHP's CSRF middleware and FormProtectionComponent in your host
application as needed. FormCompose uses their request metadata and protection
APIs; loading the helper does not enable them.

## Add labels, help, and attributes

Replace the title control with:

```php
echo $form->control('title', [
    'label' => 'Article title',
    'help' => 'Choose a title readers can recognize.',
    'attributes' => [
        'class' => 'article-title',
        'placeholder' => 'For example: Our autumn release',
        'autocomplete' => 'off',
    ],
]);
```

`label` and `help` configure field presentation. `attributes` supplies HTML
attributes for the input. To style the wrapper or label, use `partAttributes`.
[Field options](./field-options.md) lists the supported options, defaults, and
where each applies.

## Show validation feedback

When your existing submission handler patches the entity and validation fails,
render the form with that entity again. Its CakePHP context supplies the errors;
`control()` renders them and connects them to the input with ARIA attributes.
Submitted request data takes priority over context values by default, so the
user's attempted values remain visible.

FormCompose does not validate or save the entity. Browser attributes such as
`required` complement your server-side validation rules.

## Choose your next step

| You want to… | Read |
| --- | --- |
| Set values, types, upload files, or build a search form | [Fields and values](./usage.md) |
| Render selects, radio buttons, or checkboxes | [Choice controls](./choices.md) |
| Understand which PHP element renders a control | [How rendering works](./rendering.md) |
| Arrange one field's parts in the view | [Compose field parts](./field-parts.md) |
| Reuse markup across forms | [Create a theme](./themes.md) |
| Reuse field configuration across screens | [Field definitions](./definitions.md) |

Start with ordinary controls. Definitions and blueprints are optional layers you
can add when configuration repeats.
