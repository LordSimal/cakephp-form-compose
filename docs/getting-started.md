---
description: Install FormCompose, load its helper, and build your first CakePHP form.
---

# Getting started

Install the plugin, load its helper, and build a form with the bundled base theme.
If you want to understand how a control maps to a PHP file first, start with
[how rendering works](./rendering.md).

## Requirements

| CakePHP | PHP |
| --- | --- |
| 6 development builds | 8.5+ |

::: info Experimental API
FormCompose's API is still evolving. It provides a separate form-building API;
existing core FormHelper calls and widgets need to be ported.
:::

## Install the plugin

Start with a CakePHP 6 application. CakePHP 5.4 is no longer supported. Until
CakePHP 6 and its dependencies have stable releases, the host application's
Composer configuration must allow development dependencies with
`"minimum-stability": "dev"` and `"prefer-stable": true`.

The package is not published to Packagist yet. Add its GitHub repository to your
application's Composer configuration and install the development branch:

```sh
composer config repositories.form-compose vcs https://github.com/josbeir/cakephp-form-compose
composer require josbeir/cakephp-form-compose:dev-main
```

Register the plugin in your application's `bootstrap()`, after `parent::bootstrap()`:

```php [src/Application.php]
use FormCompose\FormComposePlugin;

$this->addPlugin(FormComposePlugin::class);
```

## Load the helper

Choose the name you want to use in your view templates:

::: code-group

```php [As Form]
// In AppView::initialize()
$this->addHelper('Form', ['className' => 'FormCompose.Form']);

// In a template
$form = $this->Form->bind($article);
```

```php [Alongside core FormHelper]
// In AppView::initialize()
$this->addHelper('Form');
$this->addHelper('Compose', ['className' => 'FormCompose.Form']);

// In a template
$form = $this->Compose->bind($article);
```

:::

The examples below use the `Form` alias. Configure your application's CSRF
middleware and FormProtectionComponent as needed.

## Build your first form

```php [templates/Articles/edit.php]
<?php $form = $this->Form->bind($article); ?>

<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('body', ['type' => 'textarea']) ?>
<?= $form->control('published', ['type' => 'checkbox']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

1. `bind()` selects the data context and creates a form session.
2. `open()` renders the opening form tag and initial method/CSRF fields.
3. `control()` resolves each field and renders its `control.php` element, which
   arranges the label, input, help, and errors.
4. `close()` renders the final protection fields and closing tag.

An existing record defaults to PUT; a new record defaults to POST. Pass a route
through `bind($article, ['url' => ['action' => 'edit', $article->id]])` to choose the
action explicitly.

## Choose how to customize the layout

For a reusable layout, [create a theme](./themes.md). Its elements determine what
`control()` renders across your forms.

For a layout used once, [compose field parts in your view](./usage.md#composing-field-parts)
with `$form->field('title')`, then call that field's `label()`, `input()`, `help()`,
and `errors()` methods where you need them.

Continue with [fields and form data](./usage.md) for values and feedback,
[reuse field definitions](./definitions.md) across screens,
[declare attributed blueprints](./blueprints.md), or
[create a theme](./themes.md) to write your first control element.
