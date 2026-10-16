---
layout: home

description: A replacement for CakePHP's FormHelper, with PHP element templates and composable bound fields.

hero:
  name: FormCompose
  text: A replacement for FormHelper.
  tagline: Build CakePHP forms with bound fields and ordinary PHP element templates.
  image:
    src: /branding/formcompose-mark.svg
    alt: FormCompose logo
    width: 320
    height: 320
  actions:
    - theme: brand
      text: Get started
      link: /getting-started
    - theme: alt
      text: How it works
      link: /rendering
---

## See it in code

Bind your data and render fields, reuse a blueprint, or arrange field parts in
your own markup. The blueprint tabs show its class and the view that renders it.

::: code-group

```php [Build a form]
<?php
$form = $this->Form->bind($user);
?>
<?= $form->open() ?>
<?= $form->control('name', ['label' => 'Full name']) ?>
<?= $form->control('email', ['type' => 'email']) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

```php [Blueprint class]
<?php
declare(strict_types=1);

// src/Form/ProfileForm.php
namespace App\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

#[Form('profile')]
#[Field('name', label: 'Full name')]
#[Field('email', type: 'email')]
class ProfileForm extends FormBlueprint
{
}
```

```php [Render a blueprint]
<?php
use App\Form\ProfileForm;

$form = $this->Form->bind($user);
?>
<?= $form->open() ?>
<?= $form->definition(ProfileForm::class) ?>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

```php [Compose field parts]
<?php
$form = $this->Form->bind($user);
$email = $form->field('email', ['type' => 'email']);
?>
<?= $form->open() ?>
<?= $form->control('name', ['label' => 'Full name']) ?>
<div class="checkout-contact">
    <?= $email->label() ?>
    <div class="checkout-input"><?= $email->input() ?></div>
    <?= $email->help() ?>
    <?= $email->errors() ?>
</div>
<?= $form->submit('Save') ?>
<?= $form->close() ?>
```

:::

[Explore form blueprints](./blueprints.md) ·
[See how rendering works](./rendering.md) ·
[Compare with core FormHelper](./why.md)
