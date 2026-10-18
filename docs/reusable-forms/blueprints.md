---
title: Form blueprints
description: Declare reusable fields with fluent PHP hooks, translations, choices, and optional layouts.
---

# Form blueprints

A blueprint declares reusable field configuration. Values, validation, and saving
remain in the application and CakePHP context. Start with inline controls and
extract a blueprint when the same fields repeat.

## Declare and use a blueprint

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
use I18n\__;
use function Cake__;

#[Form('article')]
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title')->label(__('Article title'));
        $form->add('body')->type('textarea');
        $form->add('published')->type('checkbox');
    }
}
```

`#[Form]` declares the discovery alias only. Field, embed, and presentation
settings live in `configure()`; there are no `Field`, `Embed`, or `EmbedMany`
attributes. PHP hooks support translation calls, injected services, and normal
conditional logic without attribute-expression restrictions.

### Aliases and class references

Prefer the declared alias, such as `'article'`. A fully qualified class reference
is an explicit alternative: `blueprint(ArticleForm::class)`. Application and
loaded-plugin discovery is described in [services and discovery](/blueprint-configuration).

## Render a blueprint {#blueprint-selection}

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->submit('Save article');
```

Or use the selected defaults while arranging your own view:

```php
<?php $form = $this->Form->form($article)->blueprint('article')->bind(); ?>
<?= $form->open() ?>
<?= $form->control('title') ?>
<?= $form->control('body') ?>
<?= $form->control('published') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

`$form->blueprint()` renders the selected collection inside an already open
session. Explicit `blueprint('other', at: 'author')` selects a local occurrence
without changing binding defaults.

### Form and button configuration {#complete-form-shortcut}

Configure the complete form on its builder before binding. Use `url()`,
`attributes()`, `errorSummary()`, `submit()`, and `withoutSubmit()`. Reuse a typed
`ButtonConfig` for the default action's settings. See the [form reference](/form-reference#render).

## Keep control of the layout {#keep-control-of-the-layout}

Add this at the end of the blueprint's `configure()` hook:

```php
$form->presentation()->template('forms/article');
```

The layout receives a scoped `BlueprintView`:

```php
<?php

/** @var \FormCompose\Blueprint\BlueprintView $form */
/** @var \FormCompose\Rendering\Attributes $attributes */
?>
<section<?= (string)$attributes ?>>
    <div class="article-content">
        <?= $form->controls(['title', 'body']) ?>
    </div>
    <?php if ($form->has('published')): ?>
        <section class="publication-settings">
            <?= $form->control('published') ?>
        </section>
    <?php endif; ?>
</section>
```

The outer session owns opening, closing, and protection. The layout uses relative
paths and never opens another form. Without a layout, fields render in declaration
order. Each field still uses its own control template.

### Inspect values and field configuration

```php
$title = $form->field('title')->resolve();
$value = $title->state->value;
$showHint = $title->state->config->presentation->templateData['showHint'] ?? false;
$disabled = $title->attributes()->has('disabled');
```

Resolution and inspection render nothing. For a local change, configure a new
field builder before resolving or rendering it. Use `control()` once to emit the
whole field; use parts when arranging its internal HTML.

### Read request data in a layout

A layout is a CakePHP element. `$this->getRequest()` exposes full request paths;
`$form->field('email')` uses the layout's relative field path. For an embedded
author, raw request lookup is `getData('author.email')`. Read `state->value` for
the effective value after all configured sources and overrides.

## Configure choices and conditional fields

```php
public function configure(BlueprintBuilder $form, FormContext $context): void
{
    $form->add('title')->label(__('Article title'));
    $form->add('status')->choices([
        'draft' => __('Draft'),
        'review' => __('Ready for review'),
    ]);

    if ($context->request->getAttribute('canPublish')) {
        $form->add('published')->type('checkbox');
    }
}
```

The snippet belongs inside a blueprint with the imports shown above. Application
services can supply choice maps using `$this->statuses->all()` after constructor
injection. Declare a field with `add()`; edit an existing/inherited field with
`field()`. Neither uses an option array.

### Remove or hide a declared field

Use `remove('published')` after a field is declared, or avoid adding it when the
condition is false. To hide it while retaining submission, use
`field('published')->type('hidden')`. Hidden values remain untrusted submissions.
In layouts, `has()` guards optional wrappers and parts. `control()` for a known
removed field emits an empty string; unknown paths still fail.

## Translate a blueprint

Call CakePHP's translation functions directly in `configure()` for labels, help,
and choices. Set the request locale before binding or compiling the blueprint.
These literal translation calls remain discoverable by CakePHP's extractor.

### Resolution timing and overrides

Ordinary translated strings capture the compilation locale. For a reusable config
that must translate later, use `new Translated('Email address')` or a zero-argument
label/help closure. They resolve after overrides merge, once per `BoundField`.
Overridden callbacks are not evaluated. An existing resolved field keeps its text
if the locale later changes; resolve a fresh field for the new locale.

### Computed text with PHP

```php
$form->add('title')->label(
    $context->request->getAttribute('isAdmin') ? __('Internal title') : __('Article title'),
);
```

### Translate in runtime configuration and templates

```php
$form->add('attachments')->help(
    static fn(): string => __n('One attachment', '{0} attachments', $count, $count),
);
```

Import `Cake\I18n\__n` for this hook fragment; `$count` comes from your application. Closures return string, trusted
`Html`, `Translated`, false, or null. Ordinary strings are escaped. `Translated`
and closures apply to label/help; translate ordinary choice labels directly or
implement CakePHP's `EnumLabelInterface`.

### Translation catalog extraction

CakePHP's extractor recognizes its translation function calls. It does not
recognize `new Translated(...)`; maintain those message entries manually.
`Translated` supports `arguments`, `domain`, and `context` named parameters and
never marks a message as trusted HTML.

## Configuration order

Inheritance uses normal PHP: call `parent::configure($form, $context)`, then edit,
remove, or add declarations. The parent occurrence finishes authoring before
children compile, so repeated `keys()` selects which row hooks run. Qualified
edits such as `field('author.email')->label(...)` overlay compiled child defaults.
Duplicate declarations fail; use `field()` for edits. Remove an inherited
declaration before intentionally replacing it with `add()` or `embed()`.
Per-call configs and fluent edits overlay these compiled defaults.

## Application services and discovery

Use constructor injection for application services; no container lookup belongs
in the hook. Each occurrence gets fresh blueprint authoring state. Follow
[Blueprint services and discovery](/blueprint-configuration) for wiring and caches,
and [Nested blueprints](/composition) for composition.
