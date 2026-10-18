---
title: FormCompose for CakePHP
titleTemplate: false
layout: home

description: Build composable CakePHP forms with bound fields and application-owned PHP templates. Learn through practical examples, guides, and a complete API reference.

hero:
  name: FormCompose
  text: Composable CakePHP forms.
  tagline: Bind CakePHP data, render controls with PHP elements, and keep your application in charge of the HTML.
  image:
    src: /branding/formcompose-mark.svg
    alt: FormCompose logo
    width: 320
    height: 320
  actions:
    - theme: brand
      text: Build your first form
      link: /getting-started
    - theme: alt
      text: API reference
      link: /form-reference
---

## A control call connected to your markup

When a field needs its own wrappers, icons, or feedback placement, put that markup
in a PHP element. `control()` binds the data; the element arranges its parts.

::: code-group

```php [templates/Articles/edit.php]
<?php
$form = $this->Form->form($article)->bind();
?>
<?= $form->open() ?>
<?= $form->control('title')->template('forms/article-title') ?>
<?= $form->control('body') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

```php [templates/element/forms/article-title.php]
<?php

/**
 * @var \FormCompose\Form\Field\BoundField $field
 * @var \FormCompose\Rendering\Attributes $attributes
 */
?>
<div<?= (string)$attributes ?>>
    <?= $field->label() ?>
    <div class="title-input-shell"><?= $field->input() ?></div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

:::

Render labels, inputs, help, and errors independently, or share the markup through
[templates](/templates), [themes](/themes), and [variants](/variants).

## Start inline. Reuse what repeats.

Configure individual controls, share immutable settings, or render a whole
[blueprint](/blueprints) through its declared alias. Nested paths bind association
fields using the same API.

::: code-group

```php [Fluent controls and shared settings]
<?php
use FormCompose\Configuration\FieldConfig;

$email = FieldConfig::builder()
    ->type('email')
    ->required()
    ->attributes(['autocomplete' => 'email']);

$form = $this->Form->form($article)->bind();
?>
<?= $form->open() ?>
<?= $form->control('author.email', config: $email)->label('Author email') ?>
<?= $form->control('editor.email', config: $email)->label('Editor email') ?>
<?= $form->control('status')
    ->choices(['draft' => 'Draft', 'published' => 'Published'])
    ->help('Only published articles appear on the site.') ?>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

```php [Reusable blueprint forms]
<?php
// The application declares its fields under the 'article' blueprint alias.
echo $this->Form
    ->form($article)
    ->blueprint('article')
    ->errorSummary()
    ->submit('Save article');
```

```php [Protected actions]
<?php
// A standalone form, for example in an article listing.
echo $this->Form
    ->postButton(label: 'Delete', url: ['action' => 'delete', $article->id])
    ->method('delete')
    ->attributes(['class' => 'button-danger']);
```

:::

Explore [choices and enums](/choices), [repeated association rows](/repeated-embeds),
[custom field types](/custom-fields), and [POST buttons and links](/post-actions)
when your form needs more.

CakePHP continues to supply values, validation metadata, routing, and submission
protection. Forms and native action buttons need no CSS framework or JavaScript
runtime.

[Why FormCompose?](/why) · [Your first form](/getting-started) ·
[Field configuration](/field-options)
