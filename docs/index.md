---
layout: home

description: CakePHP forms rendered with PHP element templates and composable field parts.

hero:
  name: FormCompose
  text: CakePHP forms with PHP templates.
  tagline: Keep the data binding. Write the markup in ordinary CakePHP elements.
  actions:
    - theme: brand
      text: Get started
      link: /getting-started
    - theme: alt
      text: How it works
      link: /rendering
---

## See it in code

A `control()` call renders a PHP element with a bound field object. These examples
show a form, its email-control template, and an alternative using individual parts.

::: code-group

```php [Build a form]
<?php
use FormCompose\Rendering\Theme;

$form = $this->Form->bind(
    $user,
    theme: new Theme(layers: ['forms/application']),
);
?>
<?= $form->open() ?>
<?= $form->control('email', ['type' => 'email']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

```php [PHP control template]
<?php
// templates/element/forms/application/email/control.php
/** @var \FormCompose\Field\BoundField $field */
?>
<div class="contact-field">
    <?= $field->label() ?>
    <div class="input-with-icon">
        <span aria-hidden="true">@</span>
        <?= $field->input() ?>
    </div>
    <?= $field->help() ?>
    <?= $field->errors() ?>
</div>
```

```php [Compose field parts]
<?php
// Inside an open form, instead of the email control() call.
$email = $form->field('email', ['type' => 'email']);
?>
<div class="checkout-contact">
    <?= $email->label() ?>
    <div class="checkout-input"><?= $email->input() ?></div>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</div>
```

:::

[Compare with core FormHelper](./why.md) ·
[Follow the rendering flow](./rendering.md) ·
[Create a theme](./themes.md)
