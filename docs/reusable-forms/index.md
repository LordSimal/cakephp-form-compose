---
title: Reusable forms
description: Choose inline controls, reusable configuration snapshots, or PHP form blueprints.
---

# Reusable forms

Bound fields and application-owned templates are the foundation. Reuse only the
layer that repeats in your application.

## Choose the right layer

| Repeated concern | Use |
| --- | --- |
| One control's settings | `FieldConfig` snapshot |
| HTML structure and classes | PHP element or theme |
| A collection of fields and embeds | Form blueprint |
| A one-off page arrangement | Ordinary view HTML |

## Keep local configuration inline

```php
echo $form->control('email')
    ->label('Email address')
    ->required();
```

To share settings without a blueprint:

```php
use FormCompose\Configuration\FieldConfig;

$email = FieldConfig::builder()
    ->type('email')
    ->required()
    ->config();
echo $form->control('email', config: $email);
```

Builders are mutable authoring objects; configs are immutable snapshots. See
[field configuration](/field-options) for constructors, merging, and every setter.

## Extract a reusable form

Declare fields with `add()` in a `FormBlueprint::configure()` hook. Use
`#[Form('article')]` for an alias. Hooks can call translation functions, read the
current context, and use injected choice services. See [form blueprints](/blueprints)
for the complete class.

## Render all declared fields

```php
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->submit('Save');
```

The complete form includes opening/closing markup and the default action. Use
`withoutSubmit()` to omit that action. An optional blueprint layout arranges the
collection; field templates still arrange each individual control.

## Use blueprint defaults with a manual layout {#select-defaults-for-the-whole-binding}

```php
<?php
$form = $this->Form
    ->form($article)
    ->blueprint('article')
    ->bind();
?>
<?= $form->open() ?>
<section class="article-main">
    <?= $form->controls(['title', 'body']) ?>
</section>
<?= $form->control('published') ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

Selection supplies defaults; it does not force automatic rendering. Use
[nested blueprints](/composition) for associations and [repeated embeds](/repeated-embeds)
for keyed rows. Validation and persistence remain in the application.
