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
<?php $form = $this->Form->bind($article); ?>
<?= $form->open() ?>
<?= $form->control('title', ['template' => 'forms/article-title']) ?>
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

```php [Arrange parts directly in the view]
<?php
$form = $this->Form->bind($article);
$title = $form->field('title');
?>
<?= $form->open() ?>
<section class="article-title">
    <?= $title->label() ?>
    <?= $title->input() ?>
    <?= $title->help() ?>
    <?= $title->errors() ?>
</section>
<?= $form->submit('Save article') ?>
<?= $form->close() ?>
```

:::

Use a [theme](./themes.md) to share markup or optional
[reusable forms](./reusable-forms.md) to share field configuration.
CakePHP continues to supply values, validation metadata, routing, and submission
protection. No CSS framework or JavaScript runtime is required.

[Why FormCompose?](./why.md) · [Your first form](./getting-started.md) ·
[All control options](./field-options.md)
